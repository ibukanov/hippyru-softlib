<?php

require("ajax-core.php");

$writableDirs = array('../wp-content/uploads', '../wp-content/plugins', '../wp-content/cache');

foreach($writableDirs as $d) {
	$e = str_replace('../', '', $d);
	if(is_dir($d) == false) {
		WPDebuggerError("The folder {$e} is missing, please create it.");
	} else {
		if(is_writable($d) == false) {
			WPDebuggerError("The folder {$e} does exist but is not writable. <br /><a href='http://codex.wordpress.org/Changing_File_Permissions'>More information</a>.");
		} else {
			WPDebuggerSuccess("The folder {$e} exists and is writable");
		}
	}
}
