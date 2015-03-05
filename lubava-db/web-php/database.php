<?php
    define ("INCLUDE_LEGAL", TRUE);

    require_once "../lib-php/defines.inc.php";
    require_once "../lib-php/utils.php";
    require_once "../lib-php/session.php";
    require_once "../lib-php/user_establish.inc.php";

if (isset  ($_GET["mode"])) {
    $mode = filter_input (INPUT_GET, "mode", FILTER_SANITIZE_STRING);
} else if (isset  ($_POST["epost"])) {
    $mode = filter_input (INPUT_POST, "epost", FILTER_SANITIZE_STRING);
} else {
    $mode = "list";
}

// Remove pkey cookie before we write any HTML
$saved_pkey_cookie = null;
if (isset($_COOKIE['pkey'])) {
    $saved_pkey_cookie = $_COOKIE['pkey'];
    if (strlen($saved_pkey_cookie) < 8) {
        # Require sufficiently long key
        $saved_pkey_cookie = null;
    }
    setcookie('pkey', '', 1, '', '', true, false);
}

function write_error_html($page_error) {
    global $error_messages, $strBackUrl;
    if (!is_int($page_error)) {
        throw new Exception("page_error is not int: "+gettype($page_error));
    }

    $with_sys_details = false;
    switch ($page_error) {
      case PAGE_DB_ERR:
        $msg = 'Ошибка базы данных.';
        $with_sys_details = true;
        break;
      case PAGE_RECORD_NOT_FOUND:
        $msg = 'Требуемая запись не найдена.';
        break;
      case PAGE_NO_WRITE_ACCESS:
        $msg = 'Вы не имеете прав загружать или редактировать запись.';
        break;
      case PAGE_BAD_POST_KEY:
        $msg = 'Загрузка невозможна. Проверьте, включен ли JavaScript в Вашем браузере.';
        break;
      case PAGE_BAD_INPUT:
        $msg = 'Неверное значениe системного параметра.';
        $with_sys_details = true;
        break;
      case PAGE_BAD_LOGIN:
        $msg = 'Неверный логин или пароль.';
        break;
      default:
        throw new Exception("page_error is unknown: $page_error");
    };

    printf("<center><font class='style2'>%s</font></center>", $msg);
    if ($with_sys_details && count($error_messages)) {
        echo "<div class='syserr'>";
        foreach ($error_messages as $err) {
            printf("%s\n", htmlspecialchars($err, ENT_NOQUOTES));
        }
        echo "</div>";
    }
    printf("<hr>%s", $strBackUrl);
}

//
// Determine the page to display
//
// (Default is 0 (Texts))
$pageid = 0;
$pageid_s = "";
if (isset ($_GET["pageid"]))  $pageid_s = $_GET["pageid"];
if (isset ($_POST["pageid"])) $pageid_s = $_POST["pageid"];
if ($pageid_s != "") {
    if (is_numeric ($pageid_s)) {
        $pageid = (int)$pageid_s;
        if ($pageid < 0 || $pageid >= count($g_PageTitles)) {
            $pageid = 0;
        }
    }
}

$should_show_err = 0;

check_login_cookie();

// If user
// tries to log in...
if (isset($_POST['mode']) && $_POST['mode'] == 'login') {
//
// Try to log him in
//
    if (isset ($_POST["id"]) && isset ($_POST["pass"])) {
       $should_show_err = user_login(
           filter_input(INPUT_POST, "id",   FILTER_SANITIZE_STRING),
           filter_input(INPUT_POST, "pass", FILTER_SANITIZE_STRING));
       if (!$should_show_err) {
           header ("Location: " . $url_me . "?pageid=$pageid");
           exit();
       }
    }

} else if ($mode == 'logout') {
//
// Log out.
//
    $should_show_err = user_logout();
    if (!$should_show_err) {
        header ("Location: " . $url_me . "?pageid=$pageid");
        exit();
    }
}

/************ DISPLAY THE HEADER **************/

echo <<<EOT
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title>$g_PageTitles[$pageid]</title>
    <link rel="stylesheet" type="text/css" href="$static_path/css/db.css">
    <script src="$static_path/js/lib.js"></script>
</head>
<body bgcolor="#FFFFFF" link="#000000" alink="#000000" vlink="#000000">
EOT;

//phpinfo();
//log_err('TEST - %d', 100);

//
// Display login/logout form
//
if ($mode != "skip") {
    // Check authorization
    if ($strUserName != "guest") {
    //
    // Logged in.
    //
        echo "<div style='text-align: right'><a href='${url_me}?mode=logout&pageid=$pageid'>[Выйти ($strUserName)]</a></div>";
    } else {
    //
    // Not logged in.
    //
        echo <<<EOT
<!-- Login form -->
<form enctype="multipart/form-data" action="$url_me" method="post">
  <div style='text-align: right'>
  <input type="hidden" name="epost" value="login" />
  <input type="hidden" name="pageid" value="$pageid" />
  <input type="hidden" name="mode" value="login" />
  Логин:&nbsp;<input type="text" name="id" size='9'/> &nbsp;&nbsp;Пароль:&nbsp;<input type="password" name="pass" size='14'/> &nbsp;&nbsp;<input type="submit" value="Войти" />
  </div>
</form>
EOT;
    }
}

if ($should_show_err) {
    write_error_html($should_show_err);
    $mode = "skip";
}

/******************************************************/
/******************* HERE WE GO ***********************/
/******************************************************/

$strBackUrl   = "<p align='center' class='style2'><a href='${url_me}?mode=list&pageid=$pageid' class='noneline'>Назад</a></p>";

if ($mode == "list") {
//
// Display the list of
// uploaded files.
//
    require_once ("../lib-php/show.php");

    $r = do_get_list('Тексты');
    if (!is_object($r)) {
        write_error_html($r);
    } else {
        if (isWritePermitted()) {
            echo "<p align='center' class='style2'><a href='${url_me}?mode=edit&pageid=$pageid' class='noneline'>+ Добавить ещё ".$g_DocName_0[$pageid]."</a></p>";
        }

        $class = "";
        $need_author = false;
        foreach ($r->rows as $row) {
            $r_class    = $row[LIST_COLUMN_CLASS];
            $r_id       = $row[LIST_COLUMN_ID];
            $r_author   = $row[LIST_COLUMN_AUTHOR];
            $r_year     = $row[LIST_COLUMN_YEAR];
            $r_title    = $row[LIST_COLUMN_TITLE];

            // Split classes
            if ($r_class != $class) {
                if ($class != "") echo "</table>\n";
                echo "<p align='center' class='style2'>$r_class</p><table border='1' align='center' width='50%'>";
                $class = $r_class;
                $need_author = strpos($class, "Чужие") !== false;
            }

            echo "<tr><td align='center' width='10%'><font class='style2'>$r_year</font></td>";

            if ($need_author) {
                echo "<td align=center><font class='style2'>&nbsp;<nobr>$r_author</nobr>&nbsp;</font></td>";
            }

            echo "<td align='center'><font class='style2'>";
            echo "<a href='$url_me?mode=showtext&idx=$r_id&pageid=$pageid' class='noneline'>$r_title</a>";
            echo "</font></td>";
            echo "</tr>";
        }
        if (!count($r->rows))
            echo "<p align='center' class='style2'>Ни одного файла не загружено</p>$strBackUrl";
    }

} elseif ($mode == "edit") {
//
// Form for text uploading.
//
    require_once ("../lib-php/edit.php");

    $r = do_edit();
    if (!is_object($r)) {
        write_error_html($r);
    } else {
        if ($r->new_record) {
            $butTitle = "Загрузить";
            $delete_link = "";
        } else {
            $butTitle = "Сохранить";
            $delete_link = "<br><br><span class='style2'><a href='${url_me}?mode=delete&idx=$r->id&pageid=$pageid' class='noneline' style='vertical-align: top'>Удалить&nbsp;<img width='16' height='16' border='0' src='$static_path/png/16x16.remove.png'/></a></span>";
        }

    echo <<<EOT

    <form id="edit_form" enctype="multipart/form-data" action="$url_me" method="post">
    <input type="hidden" name="epost" value="upload" />
    <input type="hidden" name="id" value="$r->id" />
    <input type="hidden" name="pageid" value="$r->pageid" />
    <center>
    <table>
    <tr>
        <td align="left"><font class='style2'>Выберите категорию</font></td>
        <td align="left">
EOT;
        echo "\n<SELECT size='1' name='group'>\n";
        // echo "<OPTION value=''></OPTION>\n";

        foreach ($r->class_list as $c) {
            if ($c == $r->class)
                echo "<OPTION SELECTED value='$c'>$c</OPTION>\n";
            else
                echo "<OPTION value='$c'>$c</OPTION>\n";
        }

        echo "</SELECT>\n";
        echo <<<EOT
    </td>
    </tr><tr>
    <td align="left">
        <font class='style2'>Или введите новую</font>
    </td><td align="left">
        <input type="text" size="50" name="group_force" value=""/><!--$r->class"-->
    </td>
    </tr>
    <tr><td></td><td>&nbsp;</td></tr>
    <tr>
        <td align="left"><font class='style2'>Год</font></td>
        <td align="left"><input type="text" size="30" name="year" value="$r->year"/></td>
    </tr>
    <tr>
        <td align="left"><font class='style2'>Автор</font></td>
        <td align="left"><input type="text" size="50" name="author" value="$r->author"/></td>
    </tr>
    <tr>
        <td align="left"><font class='style2'>Название</font></td>
        <td align="left"><input type="text" size="50" name="title" value="$r->title"/></td>
    </tr>
    <tr valign="top">
        <td align="left"><span class='style2'>Текст</span>$delete_link</td>
        <td align="left"><textarea id="text_content" name="content" rows="30" cols="60">$r->content</textarea></td>
    </tr>
    <tr>
        <td></td>
        <td align="left"><input type="submit" value="$butTitle" /></td>
    </tr>
    </table>
    </center>
</form>

<script src="/ckeditor/ckeditor.js"></script>
<script type="text/javascript" src="$static_path/js/edit.js" charset="utf-8"></script>
<script>
</script>

<hr>
$strBackUrl
EOT;
    }


} else if ($mode == 'delete') {
//
// Delete specific text
//
    require_once ("../lib-php/edit.php");

    $r = do_delete();
    if (!is_object($r)) {
        write_error_html($r);
    } else {
        $title = sprintf("&laquo;%s&raquo;", $r->title);
        if (!$r->confirmed) {
            // Ask for confirmation.
            echo <<<EOT
<div align='center' class='style2' style='text-align: center'>
<b>Вы действительно хотите удалить запись $title?</b><br><br>
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
            echo "<p align='center' class='style2'><b>Запись $title благополучно удалена.</b></p><br>";
        }
        echo $strBackUrl;
    }

} else if ($mode == 'showtext') {
//
// Show specific text
//
    require_once ("../lib-php/show.php");
    $r = do_show();
    if (!is_object($r)) {
        write_error_html($r);
    } else {
        $CurYear = date ("Y", time ());
        $r_stamp = date("d M Y", $r->uploaded);

        // Create year-span
        if ($CurYear != $r->year) {
            $r->year = "$r->year-$CurYear";
        }

        if (can_edit_for_sender($r->sender)) {
            echo"<span class='style2'><a href='${url_me}?mode=edit&idx=$r->id&pageid=$pageid' class='noneline' style='vertical-align: top'>Редактировать&nbsp;<img width='16' height='16' border='0' src='$static_path/png/16x16.edit.png'/></a></span>";
        }
        echo "<p class='style2'><b>$r->author</b></p>";
        echo "<p class='style2'><b><i>&nbsp;&nbsp;&nbsp;&nbsp;$r->title</i></b></p>";
        echo "<div class='style2_pad'>$r->content</div><br>";
        // echo "<p class='style2'><font size='-1'>(c) $r->author $r->year</font></p>";
        echo $strBackUrl;
    }

} else if ($mode == 'upload') {
//
// Upload the file on server.
//
    require_once ("../lib-php/edit.php");

    $r = do_upload();
    if (!is_object($r)) {
        write_error_html($r);
    } else {
        $record_url = "${url_me}?mode=showtext&idx=$r->id&pageid=$r->pageid";
        $add_more_url = "${url_me}?mode=edit&pageid=$r->pageid";
        echo "<center><font class='style2'>";
        if ($r->new_record) {
            echo "<a href='$record_url'>Файл</a> благополучно загружен в базу данных<br><br>";
        } else {
            echo "<a href='$record_url'>Файл</a> благополучно изменен в базе данных<br><br>";
        }
        echo "<a href='$add_more_url' class='noneline'>Добавить ещё</a>";
        echo "</font></center><br>";
        echo "<hr>" . $strBackUrl;
    }

} else if ($mode == "skip") {
} else {
//
// Unknown mode.
// Return.
//
    echo "<center><font class='style2'>";
    echo "Ваш запрос мне не понятен.";
    echo "</font></center><br>";
    echo $strBackUrl;
}
?>

</body>
</html>
