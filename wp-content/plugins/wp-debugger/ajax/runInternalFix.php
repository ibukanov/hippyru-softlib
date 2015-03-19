<?php

require("ajax-core.php");

//==============================
// Figure out what to do
//==============================
$arg = $_POST['arg'];
switch($arg) {
	case 'clearSpam':
		DBDClearSpam();
	break;
}

 
//==============================
// Check spam comments
//==============================
function DBDClearSpam() {
	$spamCommentArgs = array('status'=>'spam');
	$spamComments = get_comments($spamCommentArgs);
	foreach($spamComments as $s) {
		$id = $s->comment_ID;
		wp_delete_comment($id);
	}
	echo "Removed ".count($spamComments)." spam comments.";
}
