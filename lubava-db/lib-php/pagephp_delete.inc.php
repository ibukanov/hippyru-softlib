<?php
if (!defined ("INCLUDE_LEGAL")) die ("File execution is prohibited.");

//
// Delete specific text
//

function do_delete() {
    global $mysql_table, $saved_pkey_cookie, $strUserName, $url_me, $pageid;
    
    $r_id = filter_input (INPUT_GET, "idx", FILTER_VALIDATE_INT);
    if (!is_int($r_id)) {
        err("idx is not given or is not an integer");
        return;
    }

    $found = false;
    $r_sender = null;

    $stmt = db_prepare("SELECT sender FROM $mysql_table WHERE id=?");
    db_bind_param($stmt, "i", $r_id);
    db_execute($stmt);
    db_bind_result($stmt, $r_sender);
    db_fetch($stmt);
    db_close($stmt);
    if (!isset($r_sender)) {
        if (db_ok()) {
            echo "<p align='center' class='style2'><b>Требуемая запись не найдена.</b></p><br>";
        }
    } else {
        // Only owner or superuser
        // can delete it.
        if ($strUserName === $r_sender || isSuperuser ()) {
            if (!isset($_POST["confirmed"])) {
                // Ask for confirmation.
                echo <<<EOT
<div align='center' class='style2' style='text-align: center'>
<b>Вы действительно хотите удалить эту запись?</b><br><br>
<form style='display: inline' method='POST' onsubmit='return set_post_key(this)'>
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
                if (!check_post_key()) {
                    echo "<p align='center' class='style2'><b>Удаление невозможно. Проверьте, включен ли JavaScript в Вашем браузере.</b></p><br>";
                } else {
                    $stmt = db_prepare("DELETE FROM $mysql_table WHERE id=?");
                    db_bind_param($stmt, "i", $r_id);
                    db_execute($stmt);
                    if (db_affected_rows($stmt) === 1) {
                        echo "<p align='center' class='style2'><b>Запись благополучно удалена.</b></p><br>";
                    }
                }
            }
        } else {
            echo "<p align='center' class='style2'><b>У вас нет прав на удаление данной записи.</b></p><br>";
        }
    }
}

if (!isWritePermitted()) {
    echo "<p align='center' class='style2'><b>У вас нет прав на удаление данной записи.</b></p><br>";
 } else {
    do_delete();
}

echo $strBackUrl_1;
?>
