<?php
    define ("INCLUDE_LEGAL", TRUE);

    require_once "../lib-php/defines.inc.php";
    require_once "../lib-php/utils.php";
    require_once "../lib-php/session.php";
    require_once "../lib-php/user_establish.inc.php";
    require_once "../lib-php/form_loginout.inc.php";

if (isset  ($_GET["mode"])) {
    $mode = filter_input (INPUT_GET, "mode", FILTER_SANITIZE_STRING);
} else if (isset  ($_POST["epost"])) {
    $mode = filter_input (INPUT_POST, "epost", FILTER_SANITIZE_STRING);
} else {
    $mode = "title";
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
        $msg = 'Ошибка базы данных';
        $with_sys_details = true;
        break;
      case PAGE_RECORD_NOT_FOUND:
        $msg = 'Требуемая запись не найдена';
        break;
      case PAGE_NO_WRITE_ACCESS:
        $msg = 'Вы не имеете прав загружать или редактировать запись.';
        break;
      case PAGE_BAD_POST_KEY:
        $msg = 'Загрузка невозможна. Проверьте, включен ли JavaScript в Вашем браузере.';
        break;
      case PAGE_BAD_INPUT:
        $msg = 'Неверное значениe системного параметра';
        $with_sys_details = true;
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

check_login_cookie();

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

$strBackUrl   = "<p align='center' class='style2'><a href='".$url_me."?mode=title&pageid=$pageid' class='noneline'>Назад</a></p>";
$strBackUrl_1 = "<p align='center' class='style2'><a href='".$url_me."?mode=list&pageid=$pageid' class='noneline'>Назад</a></p>";

$should_show_err = 0;

// If user
// tries to log in...
if (isset($_POST['mode']) && $_POST['mode'] == 'login') {
//
// Try to log him in
//
    if (isset ($_POST["id"]) && isset ($_POST["pass"])) {
       if (user_login (
                filter_input (INPUT_POST, "id",   FILTER_SANITIZE_STRING),
                filter_input (INPUT_POST, "pass", FILTER_SANITIZE_STRING)
        )){
            header ("Location: " . $url_me . "?pageid=$pageid");
        } else {
            echo "<p align='center' class='style2'>Неверный логин или пароль</p>";
            echo "<hr>" . $strBackUrl;
        }
    }

    $mode = "skip";
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

//
// Display login/logout form
//
if ($mode != "skip") {
    display_form_login ();
}

if ($should_show_err) {
    write_error_html($should_show_err);
    $mode = "skip";
}


/******************************************************/
/******************* HERE WE GO ***********************/
/******************************************************/

if ($strUserName == "guest") {
    $strBackUrl_1 = $strBackUrl;
}

if ($mode == "title") {
//
// Display the title page.
//
    $strBackUrl = "<p align='center' class='style2'><a href='http://".$_SERVER['SERVER_NAME']."' class='noneline'>Назад</a></p>";

    if ($strUserName == "guest") {
        // For guests there is no
        // title page. Redirect them to the list.
        require_once ("../lib-php/pagephp_list.inc.php");

    } else {
        echo "<p align='center' class='style2'><a href='${url_me}?mode=ask_file&pageid=$pageid' class='noneline'>+ Добавить ещё ".$g_DocName_0[$pageid]."</a></p>";
        echo "<p align='center' class='style2'><a href='${url_me}?mode=list&pageid=$pageid' class='noneline'>+ Список ".$g_DocName_1[$pageid]."</a></p>";
        echo "<br>".$strBackUrl;
    }

} else if ($mode == "list") {
//
// Display the list of
// uploaded files.
//
    require_once ("../lib-php/pagephp_list.inc.php");

} elseif ($mode == "ask_file" || $mode == "edit") {
//
// Form for text uploading.
//
    require_once ("../lib-php/edit.php");

    $r = do_edit();
    if (!is_object($r)) {
        write_error_html($r);
    } else {
        $butTitle = $r->new_record ? "Загрузить" : "Сохранить";

    echo <<<EOT
<script type="text/javascript">
    var _editor_url  = "/xinha_txtarea/";
    var _editor_lang = "ru";
</script>
<script type="text/javascript" src="xinha_txtarea/XinhaCore.js"></script>
<script type="text/javascript" src="$static_path/js/edit.js" charset="utf-8"></script>

    <form enctype="multipart/form-data" action="$url_me" method="post" onsubmit="return check_fields(this) && set_post_key(this);">
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
        <td align="left"><font class='style2'>Текст</font></td>
        <td align="left"><textarea id="teContent" name="content" rows="20" cols="60">$r->content</textarea></td>
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
        echo $strBackUrl_1;
    }
    
} else if ($mode == 'showtext') {
//
// Show specific text
//
    require_once ("../lib-php/pagephp_show.inc.php");

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
        $add_more_url = "${url_me}?mode=ask_file&pageid=$r->pageid";
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
