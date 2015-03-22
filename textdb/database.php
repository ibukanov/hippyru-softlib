<?php
if (isset($_SERVER['TEXTDB_CONFIG'])) {
    require_once $_SERVER['TEXTDB_CONFIG'];
} else {
    require_once "config.php";
}
require_once 'libphp/defines.inc.php';
require_once 'libphp/utils.php';
require_once 'libphp/session.php';

class Page {

    const ACCESS_BANNED    = 0;
    const ACCESS_LEGAL     = 1;
    const ACCESS_SUPERUSER = 2;
    const ACCESS_ADMIN     = 3;

    public $error;
    public $mode = '';
    public $user_login = 'guest';
    public $user_full_name = 'guest';
    public $user_access = self::ACCESS_BANNED;
    public $pageid = 0;
    public $location = '';
    public $show_login_logout = true;

    public $saved_pkey_cookie;

    private
    function is_superuser() {
        return ($this->user_access & self::ACCESS_SUPERUSER) === self::ACCESS_SUPERUSER;
    }

    public
    function can_edit_for_sender($sender) {
        return $this->is_superuser() || ($sender && $sender === $this->user_login);
    }

    public
    function can_write() {
        return ($this->user_access & self::ACCESS_LEGAL) === self::ACCESS_LEGAL;
    }

    public
    function has_post_key() {
        return isset($_POST["pkey"]) && $_POST["pkey"] === $this->saved_pkey_cookie;
    }

    public
    function redirect($partial_url) {
        global $url_me;

        $this->location = $url_me . $partial_url;
    }
}

require_once "libphp/user_establish.inc.php";

function parse_page() {
    global $page, $path_info, $original_path_info, $g_PageTitles;

    $page = new Page();

    $path_info = $_SERVER['PATH_INFO'];
    $last_slash = strrpos($path_info, '/');
    if ($last_slash !== false) {
        $offset = $last_slash + 1;
        if (substr_compare($path_info, 'login', $offset) === 0) {
            $page->mode = 'login';
            $original_path_info = substr($path_info, 0, $last_slash);
        } elseif (substr_compare($path_info, 'logout', $offset) === 0) {
            $page->mode = 'logout';
            $original_path_info = substr($path_info, 0, $last_slash);
        } else {
            $page->error = PAGE_UNKNOWN_REQUEST;
        }
    } elseif (isset($_GET["mode"])) {
        $page->mode = filter_input (INPUT_GET, "mode", FILTER_SANITIZE_STRING);
    } elseif (isset($_POST["epost"])) {
        $page->mode = filter_input (INPUT_POST, "epost", FILTER_SANITIZE_STRING);
    } else {
        $page->mode = 'list';
    }

    $pageid_s = "";
    if (isset ($_GET["pageid"]))  $pageid_s = $_GET["pageid"];
    if (isset ($_POST["pageid"])) $pageid_s = $_POST["pageid"];
    if ($pageid_s != "") {
        if (is_numeric ($pageid_s)) {
            $pageid = (int)$pageid_s;
            if (0 <= $pageid && $pageid < count($g_PageTitles)) {
                $page->pageid = $pageid;
            }
        }
    }

    // Remove pkey cookie if set after recoding it for form check later
    if (isset($_COOKIE['pkey'])) {
        $value = $_COOKIE['pkey'];
        if (strlen($value) >= 8) {
            # Require sufficiently long key
            $page->saved_pkey_cookie = $value;
        }
        setcookie('pkey', '', 1, '', '', true, false);
    }
}

function write_error_html($page_error) {
    global $error_messages, $strBackUrl;
    if (!is_int($page_error)) {
        throw new Exception("page_error is not int: "+gettype($page_error));
    }

}

function do_login(Page $page) {
    global $original_path_info, $if_query, $query_part;

    $page->show_login_logout = false;

    if (isset($_POST["id"]) && isset($_POST["pass"])) {
        // Try to log the user in
        $error = user_login(
           filter_input(INPUT_POST, "id",   FILTER_SANITIZE_STRING),
           filter_input(INPUT_POST, "pass", FILTER_SANITIZE_STRING));
        if ($error)
            return $error;
        $page->redirect("$original_path_info$if_query$query_part");
        return;
    }
}

function do_logout(Page $page) {
    global $original_path_info, $if_query, $query_part;

    $page->show_login_logout = false;

    $error = user_logout($page);
    if ($error)
        return $error;
    $page->redirect("$original_path_info$if_query$query_part");
}

$url_me = $_SERVER['SCRIPT_NAME'];
$query_part = $_SERVER['QUERY_STRING'];
$if_query = $query_part ? '?' : '';

parse_page();

check_login_cookie($page, $page->mode === 'edit');

$error = null;
if ($page->mode === 'login') {
    $error = do_login($page);

} elseif ($page->mode === 'logout') {
    $error = do_logout($page);

} elseif ($page->mode === 'list') {
    require_once("libphp/show.php");
    $error = do_get_list($page, 'Тексты');

} elseif ($page->mode === "edit") {
    require_once ("libphp/edit.php");
    $error = do_edit($page);

} elseif ($page->mode === 'delete') {
    require_once ("libphp/edit.php");
    $error = do_delete($page);
    
} else if ($page->mode === 'showtext') {
    require_once ("libphp/show.php");
    $error = do_show($page);

} else if ($page->mode === 'upload') {
    require_once ("libphp/edit.php");
    $error = do_upload($page);

} else {
    $error = PAGE_UNKNOWN_REQUEST;
}

if ($error) {
    $page->error = $error;
}

if (!$page->error && $page->location) {
    header("Location: " . $page->location);
    return;
}

echo <<<EOT
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title>{$g_PageTitles[$page->pageid]}</title>
    <link rel="stylesheet" type="text/css" href="$static_path/css/db.css">
</head>
<body bgcolor="#FFFFFF" link="#000000" alink="#000000" vlink="#000000">
EOT;

//phpinfo();
//log_err('TEST - %d', 100);
//error_log('TEST 2');

$strBackUrl   = "<p align='center' class='style2'><a href='${url_me}?mode=list&pageid=$page->pageid' class='noneline'>Назад</a></p>";

if (!$page->error && $page->show_login_logout) {
    if ($page->user_login === "guest") {
        $text = '[Войти]';
        $url = "$url_me$path_info/login$if_query$query_part";
    } else {
        $text = sprintf('[Выйти (%s)]', $page->user_login);
        $url = "$url_me$path_info/logout$if_query$query_part";
    }
    echo "<div style='text-align: right'><a href='$url'>$text</a></div>";
}

if ($page->error) {
    $with_sys_details = false;
    switch ($page->error) {
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
      case PAGE_BAD_INTERNAL_VALUE:
        $msg = 'Неверное значениe системного параметра.';
        $with_sys_details = true;
        break;
      case PAGE_BAD_LOGIN:
        $msg = 'Неверный логин или пароль.';
        break;
      case PAGE_UNKNOWN_REQUEST:
        $msg = "Ваш запрос мне не понятен.";
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

} elseif ($page->mode === 'login') {
    $login_url = "$url_me$path_info$if_query$query_part";
    echo <<<EOT
<!-- Login form -->
<form enctype="multipart/form-data" action="$login_url" method="post">
  <div style='text-align: center'>
  Логин:&nbsp;<input type="text" name="id" size='9'/><br>
  Пароль:&nbsp;<input type="password" name="pass" size='14'/><br>
  <input type="submit" value="Войти" />
  </div>
</form>
EOT;

} elseif ($page->mode === 'list') {
    if ($page->can_write()) {
        echo "<div class='style2' style='text-align: left'><a href='${url_me}?mode=edit&pageid=$page->pageid' class='noneline'>+ Добавить ещё ".$g_DocName_0[$page->pageid]."<img width='16' height='16' border='0' src='$static_path/png/16x16.edit.png'/></a></div>";
    }
    
    $class = "";
    $need_author = false;
    foreach ($page->rows as $row) {
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
        echo "<a href='$url_me?mode=showtext&idx=$r_id&pageid=$page->pageid' class='noneline'>$r_title</a>";
        echo "</font></td>";
        echo "</tr>";
    }
    if (!count($page->rows)) {
        echo "<p align='center' class='style2'>Ни одного файла не загружено</p>$strBackUrl";
    }

} elseif ($page->mode === 'edit') {
    if ($page->new_record) {
        $butTitle = "Загрузить";
        $delete_link = "";
    } else {
        $butTitle = "Сохранить";
        $delete_link = "<br><br><span class='style2'><a href='${url_me}?mode=delete&idx=$page->id&pageid=$page->pageid' class='noneline' style='vertical-align: top'>Удалить&nbsp;<img width='16' height='16' border='0' src='$static_path/png/16x16.remove.png'/></a></span>";
        }

    echo <<<EOT

    <form id="edit_form" enctype="multipart/form-data" action="$url_me" method="post">
    <input type="hidden" name="epost" value="upload" />
    <input type="hidden" name="id" value="$page->id" />
    <input type="hidden" name="pageid" value="$page->pageid" />
    <center>
    <table>
    <tr>
        <td align="left"><font class='style2'>Выберите категорию</font></td>
        <td align="left">
EOT;
        echo "\n<SELECT size='1' name='group'>\n";
        // echo "<OPTION value=''></OPTION>\n";

        foreach ($page->class_list as $c) {
            if ($c == $page->class)
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
        <input type="text" size="50" name="group_force" value=""/><!--$page->class"-->
    </td>
    </tr>
    <tr><td></td><td>&nbsp;</td></tr>
    <tr>
        <td align="left"><font class='style2'>Год</font></td>
        <td align="left"><input type="text" size="30" name="year" value="$page->year"/></td>
    </tr>
    <tr>
        <td align="left"><font class='style2'>Автор</font></td>
        <td align="left"><input type="text" size="50" name="author" value="$page->author"/></td>
    </tr>
    <tr>
        <td align="left"><font class='style2'>Название</font></td>
        <td align="left"><input type="text" size="50" name="title" value="$page->title"/></td>
    </tr>
    <tr valign="top">
        <td align="left"><span class='style2'>Текст</span>$delete_link</td>
        <td align="left"><textarea id="text_content" name="content" rows="30" cols="60">$page->content</textarea></td>
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
on_edit_page();
</script>

<hr>
$strBackUrl
EOT;


} else if ($page->mode === 'delete') {
    $title = sprintf('&laquo;%s&raquo;', $page->title);
    if (!$page->confirmed) {
        // Ask for confirmation.
        echo <<<EOT
<div align='center' class='style2' style='text-align: center'>
<b>Вы действительно хотите удалить запись $title?</b><br><br>
<form id='confirm_delete_form' style='display: inline' method='POST'>
<button type='submit' name='confirmed' value='1'>Да</button>
</form>
<form style='display: inline' action='$url_me' method='get'>
<input type='hidden' name='mode' value='list'>
<input type='hidden' name='pageid' value='$page->pageid'>
<button type='submit'>Нет</button>
</form>
</div>
<br><br>
<script type="text/javascript" src="$static_path/js/edit.js" charset="utf-8"></script>
<script>
on_delete_page();
</script>
EOT;
    } else {
        echo "<p align='center' class='style2'><b>Запись $title благополучно удалена.</b></p><br>";
    }
    echo $strBackUrl;

} else if ($page->mode === 'showtext') {
    $CurYear = date ("Y", time ());
    $r_stamp = date("d M Y", $page->uploaded);

    // Create year-span
    if ($CurYear != $page->year) {
        $page->year = "$page->year-$CurYear";
    }

    if ($page->can_edit_for_sender($page->sender)) {
        echo "<div class='style2'><a href='${url_me}?mode=edit&idx=$page->id&pageid=$page->pageid' class='noneline' style='vertical-align: top'>Редактировать&nbsp;<img width='16' height='16' border='0' src='$static_path/png/16x16.edit.png'/></a></div>";
    }
    echo "<p class='style2'><b>$page->author</b></p>";
    echo "<p class='style2'><b><i>&nbsp;&nbsp;&nbsp;&nbsp;$page->title</i></b></p>";
    echo "<div class='style2_pad'>$page->content</div><br>";
    // echo "<p class='style2'><font size='-1'>(c) $page->author $page->year</font></p>";
    echo $strBackUrl;

} else if ($page->mode === 'upload') {
    $record_url = "${url_me}?mode=showtext&idx=$page->id&pageid=$page->pageid";
    $add_more_url = "${url_me}?mode=edit&pageid=$page->pageid";
    echo "<center><font class='style2'>";
    if ($page->new_record) {
        echo "<a href='$record_url'>Файл</a> благополучно загружен в базу данных<br><br>";
    } else {
        echo "<a href='$record_url'>Файл</a> благополучно изменен в базе данных<br><br>";
    }
    echo "<a href='$add_more_url' class='noneline'>Добавить ещё</a>";
    echo "</font></center><br>";
    echo "<hr>" . $strBackUrl;
}
?>

</body>
</html>
