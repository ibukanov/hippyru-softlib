<?php

//Will only work as admin
if(!is_admin())
        die();

//Get hold of database and plugin settings
global $wpdb, $wpDebugSettings;

