<?php

require("ajax-core.php");

//==============================
// Check spam comments
//==============================
$spamCommentArgs = array('status'=>'spam');
$spamComments = get_comments($spamCommentArgs);
$spamCount = count($spamComments);
if($spamCount == 0) {
	 WPDebuggerSuccess("No comment spam in your database.");	
} else {
	WPDebuggerError("Found {$spamCount} spam comments, do you want to delete them all?", true, 'fixInternal', 'clearSpam');	
}

