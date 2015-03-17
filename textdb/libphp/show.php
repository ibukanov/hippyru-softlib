<?php

// Column names for the do_get_list() result

define('LIST_COLUMN_CLASS',    0);
define('LIST_COLUMN_ID',       1);
define('LIST_COLUMN_AUTHOR',   2);
define('LIST_COLUMN_YEAR',     3);
define('LIST_COLUMN_TITLE',    4);

//
// Gey the list of
// uploaded files.
//
function do_get_list(Page $page, $sort_first_class) {
    global $pageid;
    
    // Sort first by frequency of the class column but make $sort_first always
    // come first. Then sort by year and title.

    $stmt = db_prepare(
        "SELECT a.class, a.id, a.author, a.year, a.title " .
        "FROM %s a JOIN " .
        "(SELECT class, count(*) AS freq FROM %s WHERE pageid=? GROUP BY class) b " .
        "ON a.class = b.class " .
        "WHERE a.pageid=? " .
        "ORDER BY a.class <> ?, b.freq desc, a.year desc, a.title",
        DEFS_DB_TABLE_TEXTS, DEFS_DB_TABLE_TEXTS);

    db_bind_param3($stmt, "iis", $pageid, $pageid, $sort_first_class);
    db_execute($stmt);
    $result = db_get_result($stmt);
    $rows = db_fetch_all($result);
    db_free($result);
    db_close($stmt);
    if (!db_ok())
        return PAGE_DB_ERR;

    $page->rows = $rows;
}

function do_show(Page $page) {
    $page->id = (int) filter_input (INPUT_GET, "idx", FILTER_VALIDATE_INT);
    if (!$page->id)
        return PAGE_RECORD_NOT_FOUND;

    $stmt = db_prepare(
        "SELECT author, year, title, sender, uploaded, content FROM %s WHERE id = ?",
        DEFS_DB_TABLE_TEXTS);
    db_bind_param($stmt, "i", $page->id);
    db_execute($stmt);
    db_store_result($stmt);
    db_bind_result6($stmt, $page->author, $page->year, $page->title, $page->sender,
                    $page->uploaded, $page->content);
    db_fetch($stmt);
    db_close($stmt);
    if (!db_ok())
        return PAGE_DB_ERR; 
    
    if (!isset($page->author))
        return PAGE_RECORD_NOT_FOUND;
}

?>