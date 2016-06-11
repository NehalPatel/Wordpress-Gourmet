<?php
/*
Plugin Name: Wordpress Gourmet
Plugin URI: http://www.nehalpatel.com/wp-plugins
Version: 1.0
Author: Nehal Patel
Description: Compress and combine your Javscript/CSS in a single file
*/

defined( 'ABSPATH' ) or die( 'No script!' );

if(is_admin()){
	return;
}

include_once dirname(__FILE__) . '/compress_css_js.php';

function callback($buffer) {

	$buffer = minimize_css_js($buffer);

	return $buffer . "<!-- BUFFER UPDATED START". date("Y-m-d H:i:s") ."; -->";

  return $buffer;
}

function buffer_start() { ob_start("callback"); }

function buffer_end() { ob_end_flush(); }

add_action('init', 'buffer_start');
add_action('wp_footer', 'add_unveil_javascript');
add_action('shutdown', 'buffer_end');

function add_unveil_javascript(){
	wp_enqueue_script( 'unveil', plugins_url()."/wp-gourmet/unveil.js", array( 'jquery' ) );
}
