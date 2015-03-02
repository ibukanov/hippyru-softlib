<?php

// Column names for the do_get_list() result

define('LIST_COLUMN_CLASS',    0);
define('LIST_COLUMN_ID',       1);
define('LIST_COLUMN_SENDER',   2);
define('LIST_COLUMN_AUTHOR',   3);
define('LIST_COLUMN_YEAR',     4);
define('LIST_COLUMN_TITLE',    5);

//
// Gey the list of
// uploaded files.
//
function do_get_list($sort_first_class) {
    global $mysql_table, $pageid;
    
    $r = new stdClass();

    // Sort first by frequency of the class column but make $sort_first always
    // come first. Then sort by year and title.

    $stmt = db_prepare(
        "SELECT a.class, a.id, a.sender, a.author, a.year, a.title " .
        "FROM $mysql_table a JOIN " .
        "(SELECT class, count(*) AS freq FROM $mysql_table WHERE pageid=? GROUP BY class) b " .
        "ON a.class = b.class " .
        "WHERE a.pageid=? " .
        "ORDER BY a.class <> ?, b.freq desc, a.year desc, a.title");

    db_bind_param3($stmt, "iis", $pageid, $pageid, $sort_first_class);
    db_execute($stmt);
    $result = db_get_result($stmt);
    $rows = db_fetch_all($result);
    db_free($result);
    db_close($stmt);
    if (!db_ok())
        return PAGE_DB_ERR; 

    $r->rows = $rows;

    return $r;
}

function do_show() {
    global $mysql_table;
    
    $r = new stdClass();

    $r->id = (int) filter_input (INPUT_GET, "idx", FILTER_VALIDATE_INT);
    if (!$r->id)
        return PAGE_RECORD_NOT_FOUND;

    $stmt = db_prepare("SELECT author, year, title, sender, uploaded, content FROM $mysql_table WHERE id = ?");
    db_bind_param($stmt, "i", $r->id);
    db_execute($stmt);
    db_store_result($stmt);
    db_bind_result6($stmt, $r->author, $r->year, $r->title, $r->sender, $r->uploaded,
                    $r->content);
    db_fetch($stmt);
    db_close($stmt);
    if (!db_ok())
        return PAGE_DB_ERR; 
    
    if (!isset($r->author))
        return PAGE_RECORD_NOT_FOUND;

    return $r;
}

?>