<?php

function do_edit(Page $page) {
    global $g_PageTitles;

    if (!$page->can_write())
        return PAGE_NO_WRITE_ACCESS;

    if ($page->new_record) {
        $page->author    = $page->user_full_name;
        $page->year      = date ("Y", time ());;
        $page->title     = "";
        $page->sender    = $page->user_login;
        $page->class     = $g_PageTitles[$page->text_kind];
        $page->content   = "";
    } else {
        $stmt = db_prepare(
            "SELECT pageid, author, year, title, sender, class, content " .
            "FROM %s WHERE id = ?",
            DEFS_DB_TABLE_TEXTS);
        db_bind_value($stmt, 1, $page->record_id, PDO::PARAM_INT);
        db_execute($stmt);
        db_bind_column($stmt, 1, $page->text_kind, PDO::PARAM_INT);
        db_bind_column($stmt, 2, $page->author, PDO::PARAM_STR);
        db_bind_column($stmt, 3, $page->year, PDO::PARAM_INT);
        db_bind_column($stmt, 4, $page->title, PDO::PARAM_STR);
        db_bind_column($stmt, 5, $page->sender, PDO::PARAM_STR);
        db_bind_column($stmt, 6, $page->class, PDO::PARAM_STR);
        db_bind_column($stmt, 7, $page->content, PDO::PARAM_LOB);
        db_fetch_bound($stmt);
        db_close($stmt);
        if (!db_ok())
            return PAGE_DB_ERR;
        if (!isset($page->text_kind))
            return PAGE_RECORD_NOT_FOUND;
        if (!$page->can_edit_for_sender($page->sender))
            return PAGE_NO_WRITE_ACCESS;
    }

    $stmt = db_prepare("SELECT DISTINCT class FROM %s WHERE pageid=?", DEFS_DB_TABLE_TEXTS);
    db_bind_value($stmt, 1, $page->text_kind, PDO::PARAM_INT);
    db_execute($stmt);
    db_bind_column($stmt, 1, $r_class, PDO::PARAM_STR);

    $page->class_list = array();
    while (db_fetch_bound($stmt)) {
        array_push($page->class_list, $r_class);
    }
    db_close($stmt);
    if (!db_ok())
        return PAGE_DB_ERR;
}

function do_save(Page $page) {
    global $g_PageTitles, $strUserName;

    if (!$page->can_write())
        return PAGE_NO_WRITE_ACCESS;

    if (!$page->has_post_key())
        return PAGE_BAD_POST_KEY;

    if (!$page->new_record) {
        $stmt = db_prepare("SELECT pageid from %s WHERE id=?", DEFS_DB_TABLE_TEXTS);
        db_bind_value($stmt, 1, $page->record_id, PDO::PARAM_INT);
        db_execute($stmt);
        db_bind_column($stmt, 1, $page->text_kind, PDO::PARAM_INT);
        db_fetch_bound($stmt);
        db_close($stmt);
        if (!db_ok())
            return PAGE_DB_ERR;
        if (!isset($page->text_kind))
            return PAGE_RECORD_NOT_FOUND;
    }

    $class_ = filter_input(INPUT_POST, "group_force", FILTER_SANITIZE_STRING);
    if (!$class_) {
        $class_ = filter_input(INPUT_POST, "group", FILTER_SANITIZE_STRING);
        if (!$class_) {
            $class_ = $g_PageTitles[$page->text_kind];
        }
    }

    $title = filter_input(INPUT_POST, "title",  FILTER_SANITIZE_STRING);
    if (!$title) {
        $title = "Без названия";
    }

    $year = filter_input(INPUT_POST, "year", FILTER_VALIDATE_INT);
    if (!$year) {
        $year = (int) date("Y");
    }

    $author = filter_input(INPUT_POST, "author", FILTER_SANITIZE_STRING);
    if (!$author) {
        $author = $page->user_full_name;
    }

    $content = filter_input(INPUT_POST, "content");

    if ($page->new_record) {
        $stamp  = time();
        $stmt = db_prepare(
            "INSERT INTO %s VALUES (null, ?, ?, ?, ?, ?, ?, ?, ?)",
            DEFS_DB_TABLE_TEXTS);
        db_bind_value($stmt, 1, $class_, PDO::PARAM_STR); 
        db_bind_value($stmt, 2, $title, PDO::PARAM_STR); 
        db_bind_value($stmt, 3, $author, PDO::PARAM_STR); 
        db_bind_value($stmt, 4, $page->user_login, PDO::PARAM_STR); 
        db_bind_value($stmt, 5, $year, PDO::PARAM_INT); 
        db_bind_value($stmt, 6, $stamp, PDO::PARAM_INT); 
        db_bind_value($stmt, 7, $page->text_kind, PDO::PARAM_INT); 
        db_bind_value($stmt, 8, $content, PDO::PARAM_LOB); 
        db_execute($stmt);
        $page->record_id = db_last_insert_id("id");
        db_close($stmt);
        if (!db_ok())
            return PAGE_DB_ERR;
        if (!$page->record_id) {
            db_err('Zero id for the newly inserted row');
            return PAGE_DB_ERR;
        }
    } else {
        $stmt = db_prepare(
            "UPDATE %s SET class=?, title=?, author=?, year=?, content=? " .
            "WHERE id=?",
            DEFS_DB_TABLE_TEXTS);
        $null = null;
        db_bind_value($stmt, 1, $class_, PDO::PARAM_STR); 
        db_bind_value($stmt, 2, $title, PDO::PARAM_STR); 
        db_bind_value($stmt, 3, $author, PDO::PARAM_STR); 
        db_bind_value($stmt, 4, $year, PDO::PARAM_INT); 
        db_bind_value($stmt, 5, $content, PDO::PARAM_LOB); 
        db_bind_value($stmt, 6, $page->record_id, PDO::PARAM_INT); 
        db_execute($stmt);
        db_close($stmt);
        if (!db_ok())
            return PAGE_DB_ERR;
    }
    $page->title = $title;
}

function do_delete(Page $page) {
    if (!$page->can_write())
        return PAGE_NO_WRITE_ACCESS;

    $page->confirmed = isset($_POST["confirmed"]);
    if ($page->confirmed && !$page->has_post_key())
        return PAGE_BAD_POST_KEY;

    $stmt = db_prepare("SELECT pageid, title, sender FROM %s WHERE id=?", DEFS_DB_TABLE_TEXTS);
    db_bind_value($stmt, 1, $page->record_id, PDO::PARAM_INT);
    db_execute($stmt);
    db_bind_column($stmt, 1, $page->text_kind, PDO::PARAM_INT);
    db_bind_column($stmt, 2, $page->title, PDO::PARAM_STR);
    db_bind_column($stmt, 3, $sender, PDO::PARAM_STR);
    db_fetch_bound($stmt);
    db_close($stmt);
    if (!db_ok())
        return PAGE_DB_ERR;
    if (!isset($sender))
        return PAGE_RECORD_NOT_FOUND;

    // Only owner or superuser can delete it.
    if (!$page->can_edit_for_sender($sender))
        return PAGE_NO_WRITE_ACCESS;

    if ($page->confirmed) {
        $stmt = db_prepare("DELETE FROM %s WHERE id=?", DEFS_DB_TABLE_TEXTS);
        db_bind_value($stmt, 1, $page->record_id, PDO::PARAM_INT);
        db_execute($stmt);
        $nchanged = db_row_count($stmt);
        db_close($stmt);
        if (!db_ok())
            return PAGE_DB_ERR;
        if ($nchanged !== 1)
            return PAGE_RECORD_NOT_FOUND;
        $page->record_id = null;
    }
}

?>
