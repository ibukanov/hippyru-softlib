<?php
//if (!defined ("INCLUDE_LEGAL")) die ("File execution is prohibited.");

//
// Upload the file on server.
//

$bSuccess = true;

if (!isWritePermitted()) {
    echo "<center><font class='style2'>";
    echo "Вы не имеете прав загружать файлы на сервер.";
    echo "</font></center>";
} else if (!check_post_key()) {
    echo "<center><font class='style2'>";
    echo "Загрузка невозможна. Проверьте, включен ли JavaScript в Вашем браузере.";
    echo "</font></center>";
} else {

    if (isset ($_POST["title"])  &&
        isset ($_POST["year"])   &&
        isset ($_POST["author"]) &&
        isset ($_POST["contents"])
        ) {

        $r_pageid = (int) filter_input(INPUT_POST, "pageid", FILTER_VALIDATE_INT);

        $r_class = filter_input(INPUT_POST, "group_force", FILTER_SANITIZE_STRING);
        if (!$r_class) {
            $r_class = filter_input(INPUT_POST, "group", FILTER_SANITIZE_STRING);
            if (!$r_class) {
                $r_class = $g_PageTitles[$r_pageid];
            }
        }

        $r_title = filter_input(INPUT_POST, "title",  FILTER_SANITIZE_STRING);
        if (!$r_title) {
            $r_title = "Без названия";
        }

        $r_year = filter_input(INPUT_POST, "year", FILTER_VALIDATE_INT);
        if (!$r_year) {
            $r_year = (int) date("Y");
        }
        
        $r_author = filter_input (INPUT_POST, "author", FILTER_SANITIZE_STRING);
        if (!$r_author) {
            $r_group = $strUserName_Full;
        }

        $r_content = filter_input (INPUT_POST, "contents");

        $r_stamp  = time ();

        $r_id = (int) filter_input (INPUT_POST, "id", FILTER_VALIDATE_INT);
        
        $path = get_data_file_path($r_id);

        if ($r_id != 0) {
            $stmt = db_prepare("UPDATE $mysql_table " .
                               "SET class=?, title=?, author=?, year=? WHERE id=?");
            db_bind_param5($stmt, "sssii", $r_class, $r_title, $r_author, $r_year, $r_id);
            db_execute($stmt);
            $exists = (db_affected_rows($stmt) === 1);
            db_close($stmt);
            if (!$exists && db_ok()) {
                // affected rows is zero if the new values matches old ones
                $stmt = db_prepare("SELECT id FROM $mysql_table where id=?");
                db_bind_param($stmt, "i", $r_id);
                db_execute($stmt);
                db_bind_result($stmt, $r_old_id);
                db_fetch($stmt);
                db_close($stmt);
                if ($r_old_id === $r_id) {
                    $exists = true;
                }
            }
            
            if ($exists) {
                if (strlen($r_content) === file_put_contents($path, $r_content)) {
                    $bSuccess = true;
                } else {
                    log_err('failed to write to %s %d bytes', $path, strlen($r_content));
                }

            } elseif (db_ok()) {
                echo "<p align='center' class='style2'><b>Требуемая запись не найдена.</b></p><br>";
            }
            db_close($stmt);
        } else {
            // Add the record to database
            $stmt = db_prepare("INSERT INTO $mysql_table " .
                               "VALUES (null, ?, ?, ?, ?, ?, ?, '', ?)");
            
            db_bind_param7($stmt, 'ssssiii', $r_class, $r_title, $r_author, $strUserName,
                           $r_year, $r_stamp, $r_pageid);
            db_execute($stmt);
            $r_id = db_insert_id();
            db_close($stmt);
            if ($r_id) {
                if (strlen($r_content) === file_put_contents($path, $r_content)) {
                    $bSuccess = true;
                } else {
                    log_err('failed to write to %s %d bytes', $path, strlen($r_content));

                    // Remove just inserted table
                    $stmt = db_prepare("DELETE FROM $mysql_table WHERE id = ?");
                    db_bind_param($stmt, "i", $r_id);
                    db_execute($stmt);
                    db_close($stmt);
                }
            }
        }
    }
    
    if ($bSuccess) {
        echo "<center><font class='style2'>";
        echo "Файл благополучно загружен в базу данных<br><br>";
        echo "<a href='${url_me}?mode=ask_file&pageid=$r_pageid' class='noneline'>Добавить ещё</a>";
        echo "</font></center><br>";
    }
}

echo "<hr>" . $strBackUrl;
?>
