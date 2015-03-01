<?php

//
// Form for text uploading.
//
$CurYear = date ("Y", time ());

$r_class     = $g_PageTitles[$pageid];
$r_year      = $CurYear;
$r_author    = $strUserName_Full;
$r_sender    = "";
$r_title     = "";
$r_contents  = "";
$r_pageid    = $pageid;
$butTitle    = "Загрузить";

$r_id = (int) filter_input(INPUT_GET, "idx", FILTER_VALIDATE_INT);

if ($mode == "edit" && $r_id) {

    $stmt = db_prepare("SELECT pageid, author, year, title, sender, class, content " .
                       "FROM $mysql_table WHERE id = ?");
    db_bind_param($stmt, "i", $r_id);
    db_execute($stmt);
    db_store_result();
    db_bind_result7($stmt, $r_pageid, $r_author, $r_year, $r_title,
                    $r_sender, $r_class, $r_content);
    db_fetch($stmt);
    db_close($stmt);
}

if (isWritePermitted () && ($r_sender == $strUserName || isSuperuser () || $r_sender == "")) {

    $stmt = db_prepare("SELECT DISTINCT class AS sel_gr FROM $mysql_table WHERE pageid=?");
    db_bind_param($stmt, "i", $r_pageid);
    db_execute($stmt);
    db_bind_result($stmt, $sel_gr);

    $groups = array();
    while (db_fetch($stmt)) {
        array_push($groups, $sel_gr);
    }
    db_close($stmt);

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

    <form enctype="multipart/form-data" action="$url_me" method="post" onsubmit="return check_fields(this) && set_post_key(this);">
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

        foreach ($groups as $sel_gr) {
            if ($sel_gr == $r_class)
                echo "<OPTION SELECTED value='$sel_gr'>$sel_gr</OPTION>\n";
            else
                echo "<OPTION value='$sel_gr'>$sel_gr</OPTION>\n";
        }

        echo "</SELECT>\n";
        echo <<<EOT
    </td>
    </tr><tr>
    <td align="left">
        <font class='style2'>Или введите новую</font>
    </td><td align="left">
        <input type="text" size="50" name="group_force" value=""/><!--$r_class"-->
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
        <td align="left"><textarea id="teContents" name="contents" rows="20" cols="60">$r_content</textarea></td>
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
