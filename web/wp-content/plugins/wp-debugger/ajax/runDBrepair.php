<?php

require("ajax-core.php");

$table = $wpdb->escape($_POST['arg']);
$wpdb->show_errors();
if(strlen($table) > 0) {
	$wpdb->get_results("REPAIR TABLE {$table}");
	echo "The table {$table} has been attempted fixed, re-run this test to check it again.";
}
