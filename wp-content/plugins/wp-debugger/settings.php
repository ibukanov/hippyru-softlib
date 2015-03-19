<?php

global $wpdb;
$pr = $wpdb->prefix;


$wpDebugSettings = array(

	'tables' => array($pr."commentmeta", $pr."comments", $pr."links", $pr."options", $pr."postmeta", $pr."posts", $pr."terms", $pr."term_relationships", $pr."usermeta", $pr."users"),


);
