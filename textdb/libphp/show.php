<?php

// Get the array of inlined touples (class, data) where data is an array of
// inlined touples (id author year title).
function do_get_list(Page $page, $sort_first_class) {

    // Sort first by frequency of the class column but make $sort_first always
    // come first. Then sort by year and title.
    $stmt = db_prepare(
        "SELECT a.class, a.id, a.author, a.year, a.title " .
        "FROM %s a JOIN " .
        "(SELECT class, count(*) AS freq FROM %s WHERE pageid=? GROUP BY class) b " .
        "ON a.class = b.class " .
        "WHERE a.pageid=? " .
        "ORDER BY a.class <> ?, b.freq desc, a.class, a.year desc, a.title",
        DEFS_DB_TABLE_TEXTS, DEFS_DB_TABLE_TEXTS);

    db_bind_param3($stmt, "iis", $page->pageid, $page->pageid, $sort_first_class);
    db_execute($stmt);
    db_bind_result5($stmt, $class, $id, $author, $year, $title);
    $classes = array();
    $prev_class = null;
    while (db_fetch($stmt)) {
        if ($prev_class !== $class) {
            $classes[] = $class;
            $classes[] = array();
            $prev_class = $class;
            $data = & $classes[count($classes) - 1];
        }
        array_push($data, $id, $author, $year, $title);
    }
    db_close($stmt);
    if (!db_ok())
        return PAGE_DB_ERR;

    $page->classes = $classes;
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

    # Store result to access the blob column content
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