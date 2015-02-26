<?php
if (!defined ("INCLUDE_LEGAL")) die ("File execution is prohibited.");

//
// Show specific text
//
    $bSuccess = FALSE;
    if (isset ($_GET["idx"]))
        // Create the query
        $r_id  = filter_input (INPUT_GET, "idx", FILTER_VALIDATE_INT);

        if (is_numeric ($r_id)) {
            $query = "SELECT id, author, year, title, contents, sender, uploaded FROM $mysql_database.$mysql_table WHERE id = $r_id";

            if ($db = mysql_connect ($mysql_host, $mysql_user, $mysql_password)) {
                mysql_set_charset("utf8");
                if ($result = mysql_query ($query)) {
                    if (mysql_numrows ($result)) {
                    $bSuccess = TRUE;

                    $CurYear = date ("Y", time ());

                    $r_id       = mysql_result ($result, 0, "id");
                    $r_year     = mysql_result ($result, 0, "year");
                    $r_author   = mysql_result ($result, 0, "author");
                    $r_title    = mysql_result ($result, 0, "title");
                    $r_file     = mysql_result ($result, 0, "contents");
                    $r_sender   = mysql_result ($result, 0, "sender");
                    $r_stamp    = date ("d M Y", mysql_result ($result, 0, "uploaded"));

		    $r_file = $_SERVER['DOCUMENT_ROOT'] . "/" . $r_file;
		    $r_contents = $ERR_not_found;

                    if ($file = fopen ($r_file, "rb")) {
                        $r_contents = fread ($file, filesize ($r_file));
                        fclose ($file);
                    }

                    // Create year-span
                    if ($CurYear != $r_year) {
                        $r_year = "$r_year-$CurYear";
                    }

                    // $r_contents = str_replace ("\n", "<br>", $r_contents);

                    echo "<p class='style2'><b>$r_author</b></p>";
                    echo "<p class='style2'><b><i>&nbsp;&nbsp;&nbsp;&nbsp;$r_title</i></b></p>";
                    echo "<div class='style2_pad'>$r_contents</div><br>";
                    // echo "<p class='style2'><font size='-1'>(c) $r_author $r_year</font></p>";
                    // echo "<p class='style2'><font size='-3'>Uploaded by $r_sender at $r_stamp</font></p>";
                    }
                }

                mysql_close ($db);
            }
    }

    if (!$bSuccess) {
        echo "<p align='center' class='style2'><b>Запрашиваемый вами файл не найден</b></p><br/>";
    }

    echo $strBackUrl_1;
?>
