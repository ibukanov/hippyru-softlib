<?php

require("ajax-core.php");

//==============================
// Check: missing database tables
//==============================
$tablesList = $wpdb->get_col("SHOW TABLES", 0);
$tablesMissing = false;
foreach($wpDebugSettings['tables'] as $tn) {
	if(!in_array($tn, $tablesList)) {
		$tablesMissing = true;
		WPDebuggerError("Your database is missing the table {$tn}!");
	}
}

if(!$tablesMissing) {
	WPDebuggerSuccess("All ".count($wpDebugSettings['tables'])." needed Wordpress database tables is present.");
}

//==============================
// Check: corrupt database tablest
//==============================
$tableStatus = $wpdb->get_results("SHOW TABLE STATUS FROM ".$wpdb->dbname);
foreach($tableStatus as $t) {
        if(in_array($t->Name, $wpDebugSettings['tables'])) {
		$c = $t->Comment;
		if(stristr($c, 'try to repair')) {
			WPDebuggerError("The table '{$t->Name}' is marked as crashed, try to repair it. ", true, 'repairDB', $t->Name);	
		} else {
			WPDebuggerSuccess("The table '{$t->Name}' appears to be fine.");
		}
	}
}

//==============================
// Check: Not optimized database tables
//==============================
foreach($tableStatus as $t) {
	if(in_array($t->Name, $wpDebugSettings['tables'])) {
		$dataFree = $t->Data_free;
		if($dataFree > 0) {
			WPDebuggerNotice("The table '{$t->Name}' has ".round(($dataFree/1024),1)." KB free and can be optimized to improve performance.", true, 'optimizeDB', $t->Name);
		} else {
			WPDebuggerSuccess("The table '{$t->Name}' is optimized.");
		}
	}
}
