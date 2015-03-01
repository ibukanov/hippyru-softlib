<?php

function do_edit() {
    global $mode, $pageid, $mysql_table, $strUserName_Full, $strUserName, $g_PageTitles;
    
    $r = new stdClass();

    if (!isWritePermitted())
        return PAGE_NO_WRITE_ACCESS;

    $r->id = (int) filter_input(INPUT_GET, "idx", FILTER_VALIDATE_INT);
    if ($mode == "edit" && $r->id) {
        $r->new_record = false;
        $stmt = db_prepare("SELECT pageid, author, year, title, sender, class, content " .
                           "FROM $mysql_table WHERE id = ?");
        db_bind_param($stmt, "i", $r->id);
        db_execute($stmt);
        db_store_result($stmt);
        db_bind_result7($stmt,
                        $r->pageid, $r->author, $r->year, $r->title,
                        $r->sender, $r->class, $r->content);
        db_fetch($stmt);
        db_close($stmt);
        if (!db_ok())
            return PAGE_DB_ERR;
        if (!isset($r->pageid))
            return PAGE_RECORD_NOT_FOUND;
        if ($r->sender !== $strUserName && !isSuperuser())
            return PAGE_NO_WRITE_ACCESS;
    } else {
        $r->new_record = true;
        $r->pageid    = $pageid;
        $r->author    = $strUserName_Full;
        $r->year      = date ("Y", time ());;
        $r->title     = "";
        $r->sender    = $strUserName;
        $r->class     = $g_PageTitles[$pageid];
        $r->content   = "";
    }

    $stmt = db_prepare("SELECT DISTINCT class FROM $mysql_table WHERE pageid=?");
    db_bind_param($stmt, "i", $r->pageid);
    db_execute($stmt);
    db_bind_result($stmt, $r_class);
    
    $r->class_list = array();
    while (db_fetch($stmt)) {
        array_push($r->class_list, $r_class);
    }
    db_close($stmt);
    if (!db_ok())
        return PAGE_DB_ERR;

    return $r;
}

function do_upload() {
    global $g_PageTitles, $pageid, $strUserName_Full, $strUserName, $mysql_table;

    if (!isWritePermitted())
        return PAGE_NO_WRITE_ACCESS;

    if (!check_post_key())
        return PAGE_BAD_POST_KEY;

    $r = new stdClass();

    $r->id = (int) filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
    if ($r->id !== 0) {
        $stmt = db_prepare("SELECT pageid from $mysql_table WHERE id=?");
        db_bind_param($stmt, "i",$r->id);
        db_execute($stmt);
        db_bind_result($stmt, $r->pageid);
        db_fetch($stmt);
        db_close($stmt);
        if (!db_ok())
            return PAGE_DB_ERR;
        if (!isset($r->pageid))
            return PAGE_RECORD_NOT_FOUND;
    } else {
        $r->pageid = $pageid;
    }

    $class_ = filter_input(INPUT_POST, "group_force", FILTER_SANITIZE_STRING);
    if (!$class_) {
        $class_ = filter_input(INPUT_POST, "group", FILTER_SANITIZE_STRING);
        if (!$class_) {
            $class_ = $g_PageTitles[$r->pageid];
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
    
    if ($r->id !== 0) {
        $r->new_record = false;
        $stmt = db_prepare("UPDATE $mysql_table " .
                           "SET class=?, title=?, author=?, year=?, content=? " .
                           "WHERE id=?");
        $null = null;
        db_bind_param6($stmt, "sssibi",
                       $class_, $title, $author, $year, $null, $r->id);
        db_send_long_data($stmt, 4, $content);
        db_execute($stmt);
        db_close($stmt);
        if (!db_ok())
            return PAGE_DB_ERR;
    } else {
        $r->new_record = true;
        $stamp  = time ();
        $stmt = db_prepare("INSERT INTO $mysql_table " .
                           "VALUES (null, ?, ?, ?, ?, ?, ?, ?, ?)");
        $null = null;
        db_bind_param8($stmt, 'ssssiiib',
                       $class_, $title, $author, $strUserName,
                       $year, $stamp, $r->pageid, $null);
        $stmt->send_long_data(7, $content);
        db_execute($stmt);
        $r->id = db_insert_id();
        db_close($stmt);
        if (!db_ok())
            return PAGE_DB_ERR;
        if (!$r->id) {
            db_err('Zero id for the newly inserted row');
            return PAGE_DB_ERR;
        }
    }

    return $r;
}

function do_delete() {
    global $mysql_table, $strUserName, $pageid;
    
    $r = new stdClass();

    if (!isWritePermitted())
        return PAGE_NO_WRITE_ACCESS;

    $r->confirmed = isset($_POST["confirmed"]);
    if ($r->confirmed && !check_post_key())
        return PAGE_BAD_POST_KEY;
    
    $r->id = (int) filter_input(INPUT_GET, "idx", FILTER_VALIDATE_INT);
    if (!$r->id)
        return PAGE_RECORD_NOT_FOUND;
        
    $stmt = db_prepare("SELECT title, sender FROM $mysql_table WHERE id=?");
    db_bind_param($stmt, "i", $r->id);
    db_execute($stmt);
    db_bind_result2($stmt, $r->title, $sender);
    db_fetch($stmt);
    db_close($stmt);
    if (!db_ok())
        return PAGE_DB_ERR;
    if (!isset($sender))
        return PAGE_RECORD_NOT_FOUND;

    // Only owner or superuser can delete it.
    if (!($strUserName === $sender || isSuperuser()))
        return PAGE_NO_WRITE_ACCESS;

    if ($r->confirmed) {
        $stmt = db_prepare("DELETE FROM $mysql_table WHERE id=?");
        db_bind_param($stmt, "i", $r->id);
        db_execute($stmt);
        $naffected = db_affected_rows($stmt);
        db_close($stmt);
        if (!db_ok())
            return PAGE_DB_ERR;
        if ($naffected !== 1)
            return PAGE_RECORD_NOT_FOUND;
    }
    return $r;
}

?>