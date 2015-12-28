<?php

require("ajax-core.php");

$table = $wpdb->escape($_POST['arg']);
$wpdb->show_errors();
if(strlen($table) > 0) {
	$wpdb->get_results("OPTIMIZE TABLE {$table}");
	echo "The table {$table} has been attempted optimized.";
}
