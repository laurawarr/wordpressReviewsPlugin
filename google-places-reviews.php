<?php
/**
 * Plugin Name: Review Reader
 * Description: This plugin reads reviews from a given place and displays them, with star ratings on the page.
 * Version: 1.0.0
 * Author: Laura Warr
 * Author URI: http://laurawarr.ca
 */

	defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

	add_action('init', 'read_data');

	function read_data(){

		global $wpdb;
		$table_name = $wpdb->prefix."reviews";
		// Creates new reviews table if one does not exist
		if ($wpdb->get_var('SHOW TABLES LIKE '.$table_name) != $table_name) {
			$sql = 'CREATE TABLE '.$table_name.'(		        
		        rating VARCHAR(1),
		        author_name VARCHAR(30),
		        text TEXT,
		        hash VARCHAR(25))';
			$wpdb -> query( $sql );
		};

		// Reviews will be added to content
		global $content;
		$content = "";

		// Grabs existing reviews from database and adds elements to content
		$reviewList = $wpdb->get_results("SELECT * FROM wp_reviews", "ARRAY_A");
		foreach($reviewList as $r){
			add_to_content($r);
		};

		// Gets JSON file from Google Places API
		// Ideally the API would not be accessed with each user visit but with a routine background job to avoid rate limiting
		$json = file_get_contents('https://maps.googleapis.com/maps/api/place/details/json?placeid= [ PLACE ID ] &key= [ YOUR APPLICATION KEY ] ');
		$obj = json_decode($json, true);

		// Grabs list of reviews from API
		global $result;
		$result = $obj['result'];
		$reviews = $result['reviews'];

		foreach($reviews as $r){
			// Takes unique hash from author url
			$hash = explode("/", $r['author_url'])[5];
			// If review is not listed in database, add to content and insert into database
			if ($wpdb->get_var('SELECT hash FROM wp_reviews WHERE hash = '.$hash) != $hash){
				try{
					add_to_content($r);
					$wpdb->insert( wp_reviews, array("rating" => $rating, "author_name" => $r['author_name'], "text" => $r['text'], "hash" => $hash), array( '%d', '%s', '%s','%s' ));
			 	} catch(PDOException $ex){
					echo "Error Occured: ";
					echo $ex -> getMessage();
				};
			}; //end if not in table
		};
	};



	add_filter( 'the_title', 'filter_the_title' );

	// Replaces title with place name
	function filter_the_title( $title ) {
		global $result;
	    $title = $result['name'];

	    return $title;
	}


	add_filter('the_content', 'display_on_page');

	// Displays reviews in content 
	function display_on_page($content){
		global $content;
		return $content;
	}

	// Appends review rating, author, and text to content string
	function add_to_content($r){
		global $content;
		// Prints star rating
		$rating = $r['rating'];
		$stars = "";
		for ($i = 0; $i < $rating; $i++){
			$stars .= " &#9733;";
		};

		//Prints reviewer
		$content .= "<h3>".$r['author_name']."<br/>".$stars."</h3>";

		// Prints review content
		$content .= $r['text']."<br/>";

	}



?>
