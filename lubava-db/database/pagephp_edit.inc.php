<?php
if (!defined ("INCLUDE_LEGAL")) die ("File execution is prohibited.");

//
// Form for text uploading.
//
    $CurYear = date ("Y", time ());

//    if (!isset ($_GET["table"]))      die ("Неверное обращение к скрипту.");
//    $r_table     = filter_input (INPUT_GET, "table", FILTER_VALIDATE_INT);

    $r_group     = $g_PageTitles[$pageid];
    $r_year      = $CurYear;
    $r_author    = $strUserName_Full;
    $r_sender    = "";
    $r_title     = "";
    $r_contents  = "";
    $r_id        = 0;
    $r_pageid    = $pageid;
    $butTitle    = "Загрузить";

if ($mode == "edit" && $_GET["idx"]) {
    if (is_numeric ($_GET["idx"])) {
        $r_id = filter_input (INPUT_GET, "idx", FILTER_VALIDATE_INT);

        // Create the query
        $query = "SELECT id, pageid, author, sender, year, title, contents, class FROM $mysql_database.$mysql_table WHERE id = $r_id";

        if ($db = mysql_connect ($mysql_host, $mysql_user, $mysql_password)) {
		mysql_set_charset("utf8");
            if ($result = mysql_query ($query)) {
                if (mysql_numrows ($result)) {
                    $r_id       = mysql_result ($result, 0, "id");
                    $r_pageid   = mysql_result ($result, 0, "pageid");
                    $r_year     = mysql_result ($result, 0, "year");
                    $r_author   = mysql_result ($result, 0, "author");
                    $r_sender   = mysql_result ($result, 0, "sender");
                    $r_title    = mysql_result ($result, 0, "title");
                    $r_file     = mysql_result ($result, 0, "contents");
                    $r_group    = mysql_result ($result, 0, "class");

		    $r_file = $_SERVER['DOCUMENT_ROOT'] . "/" . $r_file;

		    if ($file = fopen ($r_file, "rb")) {
                        $r_contents = fread ($file, filesize ($r_file));
                        fclose ($file);
                    } else {
                        $r_contents = $ERR_not_found;
                    }

                    $butTitle   = "Сохранить изменения";
                }
            }

            mysql_close ($db);
        }
    }
}

if (isWritePermitted () && ($r_sender == $strUserName || isSuperuser () || $r_sender == "")) {
        echo <<<EOT
<!-- Scripts section -->
<script type="text/javascript">
<!--
    var _editor_url  = "/xinha_txtarea/";
    var _editor_lang = "ru";
-->
</script>
<script type="text/javascript" src="xinha_txtarea/XinhaCore.js"></script>
<script type="text/javascript" src="scripts/edit_check.js" charset="utf-8"></script>
<script type="text/javascript" src="scripts/edit_xinha.js" charset="utf-8"></script>

    <form enctype="multipart/form-data" action="$url_me" method="post" onsubmit="return check_fields(this);">
    <input type="hidden" name="epost" value="upload" />
    <input type="hidden" name="id" value="$r_id" />
    <input type="hidden" name="pageid" value="$r_pageid" />
    <center>
    <table>
    <tr>
        <td align="left"><font class='style2'>Выберите категорию</font></td>
        <td align="left">
EOT;
        echo "\n<SELECT size='1' name='group'>\n";
        // echo "<OPTION value=''></OPTION>\n";

        if ($db = mysql_connect ($mysql_host, $mysql_user, $mysql_password)) {
            mysql_set_charset("utf8");
            if ($result = mysql_query ("SELECT DISTINCT class AS sel_gr FROM $mysql_database.$mysql_table WHERE pageid=$r_pageid")) {
// ORDER BY (SELECT COUNT(id) FROM $mysql_database.$mysql_table WHERE class=sel_gr) DESC
                if ($n = mysql_numrows ($result)) {
                    for ($i = 0; $i < $n; $i++) {
                        $sel_gr = mysql_result ($result, $i, "sel_gr");
                        if ($sel_gr == $r_group)
                            echo "<OPTION SELECTED value='$sel_gr'>$sel_gr</OPTION>\n";
                        else
                            echo "<OPTION value='$sel_gr'>$sel_gr</OPTION>\n";
                    }
                }
            }
            echo mysql_error ();
            mysql_close ($db);
        }

        echo "</SELECT>\n";
        echo <<<EOT
    </td>
    </tr><tr>
    <td align="left">
        <font class='style2'>Или введите новую</font>
    </td><td align="left">
        <input type="text" size="50" name="group_force" value=""/><!--$r_group"-->
    </td>
    </tr>
    <tr><td></td><td>&nbsp;</td></tr>
    <tr>
        <td align="left"><font class='style2'>Год</font></td>
        <td align="left"><input type="text" size="30" name="year" value="$r_year"/></td>
    </tr>
    <tr>
        <td align="left"><font class='style2'>Автор</font></td>
        <td align="left"><input type="text" size="50" name="author" value="$r_author"/></td>
    </tr>
    <tr>
        <td align="left"><font class='style2'>Название</font></td>
        <td align="left"><input type="text" size="50" name="title" value="$r_title"/></td>
    </tr>
    <tr valign="top">
        <td align="left"><font class='style2'>Текст</font></td>
        <td align="left"><textarea id="teContents" name="contents" rows="20" cols="60">$r_contents</textarea></td>
    </tr>
    <tr>
        <td></td>
        <td align="left"><input type="submit" value="$butTitle" /></td>
    </tr>
    </table>
    </center>
</form>
<hr>
$strBackUrl
EOT;
    } else {
        echo "<center><font class='style2'>";
        echo "Вы не имеете права загружать файлы на сервер.";
        echo "</font></center><hr>";
        echo $strBackUrl;
    }
?>
