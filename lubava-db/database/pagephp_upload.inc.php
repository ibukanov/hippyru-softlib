<?php
//if (!defined ("INCLUDE_LEGAL")) die ("File execution is prohibited.");

//
// Upload the file on server.
//
    if (true) {
        if (isset ($_POST["title"])  &&
            isset ($_POST["year"])   &&
            isset ($_POST["author"]) &&
            isset ($_POST["contents"])
        ) {
            $r_class = "";

            // Fetch group from the
            // edit box, if any
            if ($r_class == "") {
                if (isset ($_POST["group_force"]))
                    if ($_POST["group_force"] != "")
                        $r_class = filter_input (INPUT_POST, "group_force", FILTER_SANITIZE_STRING);
            }

            // Fetch group from
            // menu then
            if ($r_class == "") {
                if (isset ($_POST["group"]))
                    if ($_POST["group"] != "")
                        $r_class = filter_input (INPUT_POST, "group", FILTER_SANITIZE_STRING);
            }

            $r_title    = filter_input (INPUT_POST, "title",  FILTER_SANITIZE_STRING);
            $r_year     = filter_input (INPUT_POST, "year",   FILTER_SANITIZE_STRING);
            $r_author   = filter_input (INPUT_POST, "author", FILTER_SANITIZE_STRING);
            $r_contents = filter_input (INPUT_POST, "contents");

            if (get_magic_quotes_gpc() == 1) {
                $r_contents = stripslashes ($r_contents);
            }

            $r_stamp  = time ();
            $query    = "";
            $r_id     = 0;
            $r_pageid = $pageid;

            if (isset ($_POST["id"])) {
                $r_id = (int)filter_input (INPUT_POST, "id", FILTER_VALIDATE_INT);
            }

            // Determine the page id.
            if (isset ($_POST["pageid"])) {
                $r_pageid = (int)filter_input (INPUT_POST, "pageid", FILTER_VALIDATE_INT);
            }

            // Validate fields
            if ($r_title  == "") $r_title = "Без названия";
            if ($r_year   == "") $r_year  = date ("Y", time ());
            if ($r_class  == "") $r_class = $g_PageTitles[$r_pageid];
            if ($r_author == "") $r_group = $strUserName_Full;

            $bSuccess = FALSE;

            if ($db = mysql_connect ($mysql_host, $mysql_user, $mysql_password)) {
                mysql_set_charset("utf8");
                if (is_numeric ($r_id) && $r_id != 0) {
                    // Now we need to
                    // update the record

                    $r_path = $url_files[$r_pageid] . "text-" . $r_id . ".htmlraw";

                    // Create the query
                    $query = "UPDATE $mysql_database.$mysql_table SET class='$r_class', title='$r_title', author='$r_author', year='$r_year', contents='$r_path' WHERE id=$r_id";
                } else {
                    // Now we need to add
                    // the record to database

                    if ($result = mysql_query  ("SELECT MAX(id) AS last FROM $mysql_database.$mysql_table")) {
                        $r_id   = mysql_result ($result, 0, "last") + 1;
                        $r_path = $url_files[$r_pageid] . "text-" . $r_id . ".htmlraw";

                        // Create the query
                        $query = "INSERT INTO $mysql_database.$mysql_table VALUES (null, '$r_class', '$r_title', '$r_author', '$strUserName', '$r_year', '$r_stamp', '$r_path', '$r_pageid')";
                    }
                }

                if ($query != "") {
                    if (mysql_query ($query)) {
		        $r_path = $_SERVER['DOCUMENT_ROOT'] . "/" . $r_path;
                        if ($file = fopen ("$r_path", "wb")) {
                            fwrite ($file, $r_contents);
                            fclose ($file);
                            //chmod ("$r_path", 666);

                            $bSuccess = TRUE;
                        } else {
                            // Rollback (!!!)
                            // mysql_query ("DELETE FROM $mysql_database.$mysql_table WHERE id = (SELECT MAX(id) FROM $mysql_database.$mysql_table)");
                        } 
                    }
                }

                mysql_close ($db);
            }
        }

        if ($bSuccess) {
            echo "<center><font class='style2'>";
            echo "Файл благополучно загружен в базу данных<br><br>";
            echo "<a href='${url_me}?mode=ask_file&pageid=$r_pageid' class='noneline'>Добавить ещё</a>";
            echo "</font></center><br>";
        } else {
            echo "<center><font class='style2'>";
            echo "Ошибка при загрузке файла. Попробуйте снова?";
            echo "</font></center>";
        }
    } else {
        echo "<center><font class='style2'>";
        echo "Вы не имеете права загружать файлы на сервер.";
        echo "</font></center>";
    }

    echo "<hr>" . $strBackUrl;
?>
