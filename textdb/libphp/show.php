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

    db_bind_value($stmt, 1, $page->text_kind, PDO::PARAM_INT); 
    db_bind_value($stmt, 2, $page->text_kind, PDO::PARAM_INT);
    db_bind_value($stmt, 3, $sort_first_class, PDO::PARAM_STR);
    db_execute($stmt);

    db_bind_column($stmt, 1, $class, PDO::PARAM_STR);
    db_bind_column($stmt, 2, $id, PDO::PARAM_INT);
    db_bind_column($stmt, 3, $author, PDO::PARAM_STR);
    db_bind_column($stmt, 4, $year, PDO::PARAM_INT);
    db_bind_column($stmt, 5, $title, PDO::PARAM_STR);

    $classes = array();
    $prev_class = null;
    while (db_fetch_bound($stmt)) {
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
    $stmt = db_prepare(
        "SELECT pageid, author, year, title, sender, uploaded, content FROM %s WHERE id = ?",
        DEFS_DB_TABLE_TEXTS);
    db_bind_value($stmt, 1, $page->record_id, PDO::PARAM_INT);
    db_execute($stmt);

    # Store result to access the blob column content
    db_bind_column($stmt, 1, $page->text_kind, PDO::PARAM_INT);
    db_bind_column($stmt, 2, $page->author, PDO::PARAM_STR);
    db_bind_column($stmt, 3, $page->year, PDO::PARAM_INT);
    db_bind_column($stmt, 4, $page->title, PDO::PARAM_STR);
    db_bind_column($stmt, 5, $page->sender, PDO::PARAM_STR);
    db_bind_column($stmt, 6, $page->uploaded, PDO::PARAM_INT);
    db_bind_column($stmt, 7, $page->content, PDO::PARAM_LOB);
    db_fetch_bound($stmt);
    db_close($stmt);
    if (!db_ok())
        return PAGE_DB_ERR;

    if (!isset($page->author))
        return PAGE_RECORD_NOT_FOUND;
}

?>
