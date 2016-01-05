<?php
/*
Plugin Name: WP Debugger
Plugin URI: http://wordpress.org/extend/plugins/wp-debugger/
Description: WP Debugger is a plugin that checks your WordPress installation for errors, optimizes the database, does integrity checks and much more.
Version: 0.3
Author: Tor Henning Ueland
Author URI: http://www.h3x.no
*/

//==============================
// Will only work as admin
//==============================
if( is_admin() ) {

	//Load core functions and add menu element
	require("settings.php");
	require("wp-debugger-functions.php");
	add_action('admin_menu', 'wp_debugger_construct_menu');
	add_action('wp_ajax_wp_debugger_check', 'wp_debugger_run_check');
}

