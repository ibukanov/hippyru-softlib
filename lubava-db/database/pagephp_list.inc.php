<?php
if (!defined ("INCLUDE_LEGAL")) die ("File execution is prohibited.");

//
// Display the list of
// uploaded files.
//

// Create the query
    $query  = "SELECT id, author, sender, year, title, class AS upper_class ".
              "FROM $mysql_database.$mysql_table WHERE pageid=$pageid ".
              "ORDER BY upper_class ASC, year DESC, author ASC, title ASC";

    $bSuccess = FALSE;
    if ($db = mysql_connect ($mysql_host, $mysql_user, $mysql_password)) {
        mysql_set_charset("utf8");	
        $result = mysql_query ($query);
        if ($result) {
            $bSuccess = TRUE;
            $num      = mysql_numrows ($result);

            if ($num == 0) {
                echo "<p align='center' class='style2'>Ни одного файла не загружено</p>$strBackUrl";
            } else {
                $ranks = array ();
                $rank_max = 0;

                // Sort the result
                for ($i = 0; $i < $num; $i++) {
                    $cur_class = mysql_result ($result, $i, "upper_class");
                    if ($cur_class == "Тексты") {
                        $ranks[$cur_class] = 100;
                        $rank_max = max ($rank_max, 100);
                    } else {
                        $result1 = mysql_query ("SELECT COUNT(id) AS rank FROM $mysql_database.$mysql_table WHERE class='$cur_class' AND pageid=$pageid");
                        if ($result1) {
                            $rank = mysql_result ($result1, 0, "rank");
                            $ranks[$cur_class] = $rank;
                            $rank_max = max ($rank_max, $rank);
                        }
                    }
                }

                // Output
                $class = "";
                $need_author = false;

                echo <<<EOT
$strBackUrl
EOT;

                for ($r = $rank_max; $r >= 0; $r--) {
                    for ($i = 0; $i < $num; $i++) {
                        $r_class = mysql_result ($result, $i, "upper_class");

                        if ($ranks[$r_class] != $r) continue;

                        $r_id       = mysql_result ($result, $i, "id");
                        $r_sender   = mysql_result ($result, $i, "sender");
                        $r_author   = mysql_result ($result, $i, "author");
                        $r_year     = mysql_result ($result, $i, "year");
                        $r_title    = mysql_result ($result, $i, "title");

                        // Split classes
                        if ($r_class != $class) {
                            if ($class != "") echo "</table>\n";
                            echo "<p align='center' class='style2'>$r_class</p><table border='1' align='center' width='50%'>";
                            $class = $r_class;

                            $need_author = strpos ($class, "Чужие") !== false;
                        }

                        echo "<tr><td align='center' width='10%'><font class='style2'>$r_year</font></td>";

                        if ($need_author) 
                            echo "<td align=center><font class='style2'>&nbsp;<nobr>$r_author</nobr>&nbsp;</font></td>";

                        echo "<td align='center'><font class='style2'>";
                        echo "<a href='$url_me?mode=showtext&idx=$r_id&pageid=$pageid' class='noneline'>$r_title</a>";
                        echo "</font></td>";

                        // If you are the one who uploaded that or you're
                        // the superuser, you can edit/delete it.
                        if ($r_sender == $strUserName || isSuperuser ()) {
                            echo <<<EOT
<td class="aircell">
<a href="$url_me?mode=delete&idx=$r_id&pageid=$pageid" class="noneline">
        <img width="16" height="16" border="0" src="$static_path/png/16x16.remove.png"/>
</a>
<a href="$url_me?mode=edit&idx=$r_id&pageid=$pageid" class="noneline">
        <img width="16" height="16" border="0" src="$static_path/png/16x16.edit.png"/>
</a>
</td>
EOT;
                        }
                        echo "</tr>";
                    }
                }
            }
        }
echo mysql_error ();
        mysql_close ($db);
    }

    if ($bSuccess) {

    } else {
        echo "<p align='center' class='style2'>Ошибка при выполнении запроса</p>";
    }

    //echo $strBackUrl;
?>
