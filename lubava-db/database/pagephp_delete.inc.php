<?php
if (!defined ("INCLUDE_LEGAL")) die ("File execution is prohibited.");

//
// Delete specific text
//
    $bSuccess = FALSE;
    if (isset ($_GET["idx"])) {
        // Create the query
        $r_id = filter_input (INPUT_GET, "idx", FILTER_VALIDATE_INT);

        if (is_numeric ($r_id)) {
            if ($db = mysql_connect ($mysql_host, $mysql_user, $mysql_password)) {
                mysql_set_charset("utf8");
                $query = "SELECT sender, contents FROM $mysql_database.$mysql_table WHERE id=$r_id";

                if ($result = mysql_query ($query)) {
                    if (mysql_numrows ($result)) {
                        $bSuccess = TRUE;

                        // Only owner or superuser
                        // can delete it.
                        if ($strUserName == mysql_result ($result, 0, "sender") || isSuperuser ()) {

                            if (!isset ($_GET["confirmed"])) {
                                // Ask for confirmation.
                                echo <<<EOT
<p align='center' class='style2'><b>Вы действительно хотите удалить эту запись?</b><br><br>
<a class="a_buttonlike" href="$url_me?mode=delete&idx=$r_id&confirmed=1&pageid=$pageid">Да</a>
<a class="a_buttonlike" href="$url_me?mode=list&pageid=$pageid">Нет</a>
</p><br><br>
EOT;
                            } else {
                                // Operation is confirmed.

                                $query = "DELETE FROM $mysql_database.$mysql_table WHERE id = $r_id";
    
                                // Delete refernced file
                                unlink ($_SERVER['DOCUMENT_ROOT'] .  "/" . mysql_result ($result, 0, "contents"));
    
                                if (!mysql_query ($query)) {
                                    $bSuccess = FALSE;
                                } else {
                                    echo "<p align='center' class='style2'><b>Запись благополучно удалена.</b></p><br>";
                                }
                            }
                        } else {
                            echo "<p align='center' class='style2'><b>У вас нет прав на удаление данной записи.</b></p><br>";
                        }
                    }
                }

                mysql_close ($db);
            }
        }
    }

    if (!$bSuccess) {
        echo "<p align='center' class='style2'><b>Требуемая запись не найдена.</b></p><br>";
    }

    echo $strBackUrl_1;
?>
