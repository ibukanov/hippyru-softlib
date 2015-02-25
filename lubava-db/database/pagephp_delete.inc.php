<?php
if (!defined ("INCLUDE_LEGAL")) die ("File execution is prohibited.");

//
// Delete specific text
//

function do_delete() {
    global $mysql_table;
    
    $r_id = filter_input (INPUT_GET, "idx", FILTER_VALIDATE_INT);
    if (!is_int($r_id)) {
        err("idx is not given or is not an integer");
        return;
    }

    $found = false;
    $r_path = null;
    $r_sender = null;

    $db = db_connect();
    $stmt = db_prepare($db, "SELECT sender, contents FROM $mysql_table WHERE id=?");
    db_bind_param($stmt, "i", $r_id);
    db_execute($stmt);
    $result = db_get_result($stmt);
    $row = db_fetch_assoc($result);
    if (is_null($row)) {
        if (is_ok()) {
            echo "<p align='center' class='style2'><b>Требуемая запись не найдена.</b></p><br>";
        }
    } else {
        $found = true;
        $r_sender = $row['sender'];
        $r_path = $row['contents'];
    }
    db_free($result);
    db_close($stmt);

    if ($found) {
        // Only owner or superuser
        // can delete it.
        if ($strUserName == $r_sender || isSuperuser ()) {
            if (!isset($_POST["confirmed"])) {
                // Ask for confirmation.
                echo <<<EOT
<div align='center' class='style2' style='text-align: center'>
<b>Вы действительно хотите удалить эту запись?</b><br><br>
<form style='display: inline' method='POST'>
<button type='submit' name='confirmed' value='1'>Да</button>
</form>
<form style='display: inline' action='$url_me' method='get'>
<input type='hidden' name='mode' value='list'>
<input type='hidden' name='pageid' value='$pageid'>
<button type='submit'>Нет</button>
</form>
</div>
<br><br>
EOT;
            } else {
                // Operation is confirmed.
                $stmt = db_prepare($db, "DELETE FROM $mysql_table WHERE id=?");
                db_bind_param($stmt, "i", $r_id);
                db_execute($stmt);
                if (is_ok() && !is_null($r_path)) {
                    unlink ($_SERVER['DOCUMENT_ROOT'] .  "/" . $r_path);
                }
            
                if (is_ok()) {
                    echo "<p align='center' class='style2'><b>Запись благополучно удалена.</b></p><br>";
                }
            }
        } else {
            echo "<p align='center' class='style2'><b>У вас нет прав на удаление данной записи.</b></p><br>";
        }
    }
    
    db_close($db);
}

do_delete();

echo $strBackUrl_1;
?>
