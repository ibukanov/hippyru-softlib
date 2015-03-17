<?php

function do_edit(Page $page) {
    global $pageid, $strUserName_Full, $strUserName, $g_PageTitles;
    
    if (!isWritePermitted())
        return PAGE_NO_WRITE_ACCESS;

    $page->id = (int) filter_input(INPUT_GET, "idx", FILTER_VALIDATE_INT);
    if ($page->id) {
        $page->new_record = false;
        $stmt = db_prepare(
            "SELECT pageid, author, year, title, sender, class, content " .
            "FROM %s WHERE id = ?",
            DEFS_DB_TABLE_TEXTS);
        db_bind_param($stmt, "i", $page->id);
        db_execute($stmt);
        db_store_result($stmt);
        db_bind_result7($stmt,
                        $page->pageid, $page->author, $page->year, $page->title,
                        $page->sender, $page->class, $page->content);
        db_fetch($stmt);
        db_close($stmt);
        if (!db_ok())
            return PAGE_DB_ERR;
        if (!isset($page->pageid))
            return PAGE_RECORD_NOT_FOUND;
        if ($page->sender !== $strUserName && !isSuperuser())
            return PAGE_NO_WRITE_ACCESS;
    } else {
        $page->new_record = true;
        $page->pageid    = $pageid;
        $page->author    = $strUserName_Full;
        $page->year      = date ("Y", time ());;
        $page->title     = "";
        $page->sender    = $strUserName;
        $page->class     = $g_PageTitles[$pageid];
        $page->content   = "";
    }

    $stmt = db_prepare("SELECT DISTINCT class FROM %s WHERE pageid=?", DEFS_DB_TABLE_TEXTS);
    db_bind_param($stmt, "i", $page->pageid);
    db_execute($stmt);
    db_bind_result($stmt, $r_class);
    
    $page->class_list = array();
    while (db_fetch($stmt)) {
        array_push($page->class_list, $r_class);
    }
    db_close($stmt);
    if (!db_ok())
        return PAGE_DB_ERR;
}

function do_upload(Page $page) {
    global $g_PageTitles, $pageid, $strUserName_Full, $strUserName;

    if (!isWritePermitted())
        return PAGE_NO_WRITE_ACCESS;

    if (!check_post_key())
        return PAGE_BAD_POST_KEY;

    $page->id = (int) filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
    if ($page->id !== 0) {
        $stmt = db_prepare("SELECT pageid from %s WHERE id=?", DEFS_DB_TABLE_TEXTS);
        db_bind_param($stmt, "i",$page->id);
        db_execute($stmt);
        db_bind_result($stmt, $page->pageid);
        db_fetch($stmt);
        db_close($stmt);
        if (!db_ok())
            return PAGE_DB_ERR;
        if (!isset($page->pageid))
            return PAGE_RECORD_NOT_FOUND;
    } else {
        $page->pageid = $pageid;
    }

    $class_ = filter_input(INPUT_POST, "group_force", FILTER_SANITIZE_STRING);
    if (!$class_) {
        $class_ = filter_input(INPUT_POST, "group", FILTER_SANITIZE_STRING);
        if (!$class_) {
            $class_ = $g_PageTitles[$page->pageid];
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
        $author = $strUserName_Full;
    }

    $content = filter_input(INPUT_POST, "content");
    
    if ($page->id !== 0) {
        $page->new_record = false;
        $stmt = db_prepare(
            "UPDATE %s SET class=?, title=?, author=?, year=?, content=? " .
            "WHERE id=?",
            DEFS_DB_TABLE_TEXTS);
        $null = null;
        db_bind_param6($stmt, "sssibi",
                       $class_, $title, $author, $year, $null, $page->id);
        db_send_long_data($stmt, 4, $content);
        db_execute($stmt);
        db_close($stmt);
        if (!db_ok())
            return PAGE_DB_ERR;
    } else {
        $page->new_record = true;
        $stamp  = time ();
        $stmt = db_prepare(
            "INSERT INTO %s VALUES (null, ?, ?, ?, ?, ?, ?, ?, ?)",
            DEFS_DB_TABLE_TEXTS);
        $null = null;
        db_bind_param8($stmt, 'ssssiiib',
                       $class_, $title, $author, $strUserName,
                       $year, $stamp, $page->pageid, $null);
        $stmt->send_long_data(7, $content);
        db_execute($stmt);
        $page->id = db_insert_id();
        db_close($stmt);
        if (!db_ok())
            return PAGE_DB_ERR;
        if (!$page->id) {
            db_err('Zero id for the newly inserted row');
            return PAGE_DB_ERR;
        }
    }
}

function do_delete(Page $page) {
    global $strUserName, $pageid;
    
    if (!isWritePermitted())
        return PAGE_NO_WRITE_ACCESS;

    $page->confirmed = isset($_POST["confirmed"]);
    if ($page->confirmed && !check_post_key())
        return PAGE_BAD_POST_KEY;
    
    $page->id = (int) filter_input(INPUT_GET, "idx", FILTER_VALIDATE_INT);
    if (!$page->id)
        return PAGE_RECORD_NOT_FOUND;
        
    $stmt = db_prepare("SELECT title, sender FROM %s WHERE id=?", DEFS_DB_TABLE_TEXTS);
    db_bind_param($stmt, "i", $page->id);
    db_execute($stmt);
    db_bind_result2($stmt, $page->title, $sender);
    db_fetch($stmt);
    db_close($stmt);
    if (!db_ok())
        return PAGE_DB_ERR;
    if (!isset($sender))
        return PAGE_RECORD_NOT_FOUND;

    // Only owner or superuser can delete it.
    if (!($strUserName === $sender || isSuperuser()))
        return PAGE_NO_WRITE_ACCESS;

    if ($page->confirmed) {
        $stmt = db_prepare("DELETE FROM %s WHERE id=?", DEFS_DB_TABLE_TEXTS);
        db_bind_param($stmt, "i", $page->id);
        db_execute($stmt);
        $naffected = db_affected_rows($stmt);
        db_close($stmt);
        if (!db_ok())
            return PAGE_DB_ERR;
        if ($naffected !== 1)
            return PAGE_RECORD_NOT_FOUND;
    }
}

?>