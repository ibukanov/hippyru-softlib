<?php
if (isset($_SERVER['TEXTDB_CONFIG'])) {
    require_once $_SERVER['TEXTDB_CONFIG'];
} else {
    require_once "config.php";
}
require_once 'libphp/defines.inc.php';
require_once 'libphp/utils.php';
require_once 'libphp/session.php';

class Page
{
    const ACCESS_BANNED    = 0;
    const ACCESS_LEGAL     = 1;
    const ACCESS_SUPERUSER = 2;
    const ACCESS_ADMIN     = 3;

    // Action constants
    const LOGIN   = 0;
    const LOGOUT  = 1;
    const LISTREC = 2;
    const SHOW    = 3;
    const EDIT    = 4;
    const SAVE    = 5;
    const DELETE  = 6;

    public $error;
    public $action;
    public $user_login = 'guest';
    public $user_full_name = 'guest';
    public $user_access = self::ACCESS_BANNED;
    public $login_error = false;
    public $target_path;
    public $text_kind;
    public $record_id;
    public $new_record;

    public $location = '';

    public $saved_pkey_cookie;

    private function is_superuser()
    {
        return ($this->user_access & self::ACCESS_SUPERUSER) === self::ACCESS_SUPERUSER;
    }

    public function can_edit_for_sender($sender)
    {
        return $this->is_superuser() || ($sender && $sender === $this->user_login);
    }

    public function can_write()
    {
        return ($this->user_access & self::ACCESS_LEGAL) === self::ACCESS_LEGAL;
    }

    public function has_post_key()
    {
        return isset($_POST["pkey"]) && $_POST["pkey"] === $this->saved_pkey_cookie;
    }

    public function redirect($url)
    {
        $this->location = $url;
    }

    public function text_kind_path()
    {
        global $g_text_kind_paths;
        return $g_text_kind_paths[$this->text_kind];
    }
}

require_once "libphp/user_establish.inc.php";

function parse_text_kind($path_info, $offset) {
    global $g_text_kind_paths;
    for ($i = 0; $i < TEXT_KIND_END; $i += 1) {
        if (substr_compare($path_info, $g_text_kind_paths[$i], $offset) === 0)
            return $i;
    }
    return null;
}

function parse_page($page) {
    // Remove pkey cookie if set after recoding it for form check later
    if (isset($_COOKIE['pkey'])) {
        $value = $_COOKIE['pkey'];
        if (strlen($value) >= 8) {
            # Require sufficiently long key
            $page->saved_pkey_cookie = $value;
        }
        setcookie('pkey', '', 1, '', '', true, false);
    }

    $path_info = $_SERVER['PATH_INFO'];
    if (!$path_info || $path_info[0] !== '/')
        goto unknown;

    $slash2 = strpos($path_info, '/', 1);
    if ($slash2 !== false) {
        $action_str = substr($path_info, 1, $slash2 - 1);
        $arg_is_target = false;
        $arg_is_text_kind = false;
        switch ($action_str) {
            case 'login':
                $action = Page::LOGIN;
                $arg_is_target = true;
                break;
            case 'logout':
                $action = Page::LOGOUT;
                $arg_is_target = true;
                break;
            case 'show':
                $action = Page::SHOW;
                break;
            case 'new':
                $action = Page::EDIT;
                $arg_is_text_kind = true;
                $page->new_record = true;
                break;
            case 'upload':
                $action = Page::SAVE;
                $arg_is_text_kind = true;
                $page->new_record = true;
                break;
            case 'edit':
                $action = Page::EDIT;
                $page->new_record = false;
                break;
            case 'save':
                $action = Page::SAVE;
                $page->new_record = false;
                break;
            case 'delete':
                $action = Page::DELETE;
                break;
            default:
                goto unknown;
        }
        $page->action = $action;
        if ($arg_is_target) {
            $page->target_path = substr($path_info, $slash2);
        } elseif ($arg_is_text_kind) {
            $page->text_kind = parse_text_kind($path_info, $slash2 + 1);
            if (!isset($page->text_kind))
                goto unknown;
        } else {
            $id = filter_var(substr($path_info, $slash2 + 1),
                             FILTER_VALIDATE_INT,
                             array('options' => array('min_range' => 1)));
            if ($id === false)
                goto unknown;
            $page->record_id = $id;
        }
    } else {
        // Check if path_info is /text_kind
        $page->text_kind = parse_text_kind($path_info, 1);
        if (!isset($page->text_kind))
            goto unknown;
        $page->action = Page::LISTREC;
    }
    return;

    unknown:
    $page->error = PAGE_UNKNOWN_REQUEST;
}

function get_login_logout_target() {
    global $url_me, $page;

    return $url_me . $page->target_path;
}

function do_login(Page $page) {
    global $original_path_info, $if_query, $query_part;

    if (isset($_POST['submit'])) {
        $error = user_login(
           filter_input(INPUT_POST, 'id',   FILTER_SANITIZE_STRING),
           filter_input(INPUT_POST, 'pass', FILTER_SANITIZE_STRING)
        );
        if (!$error) {
            $page->redirect(get_login_logout_target());
        } elseif ($error == PAGE_BAD_LOGIN) {
            $page->login_error = true;
        } else {
            return $error;
        }
    }
}

function do_logout(Page $page) {
    global $original_path_info, $if_query, $query_part;

    $error = user_logout($page);
    if ($error)
        return $error;
    $page->redirect(get_login_logout_target());
}

function get_id_for_category($cat) {
    return 'category/' . preg_replace('/[,\s]+/', '_', $cat);
}

$url_me = $_SERVER['SCRIPT_NAME'];
$static_path = $url_me;

$page = new Page();

parse_page($page);

check_login_cookie($page, $page->action === Page::EDIT);

if (!isset($page->error)) {
    switch ($page->action) {
        case Page::LOGIN:
            $page->error = do_login($page);
            break;
        case Page::LOGOUT:
            $page->error = do_logout($page);
            break;
        case Page::LISTREC:
            require_once('libphp/show.php');
            $page->error = do_get_list($page, 'Тексты');
            break;
        case Page::SHOW:
            require_once('libphp/show.php');
            $page->error = do_show($page);
            break;
        case Page::EDIT:
            require_once('libphp/edit.php');
            $page->error = do_edit($page);
            break;
        case Page::DELETE:
            require_once ('libphp/edit.php');
            $page->error = do_delete($page);
            break;
        case Page::SAVE:
            require_once ('libphp/edit.php');
            $page->error = do_save($page);
            break;
        default:
            $page->error = PAGE_UNKNOWN_REQUEST;
            break;
    }
}

if (!isset($page->error) && $page->location) {
    header("Location: " . $page->location);
    return;
}

if ($page->error) {
    $page_title = 'Ошибка';
} elseif ($page->action === Page::LOGIN) {
    $page_title = 'Войти';
} elseif ($page->action === Page::SHOW) {
    $page_title = escape_html_text($page->title);
} else {
    $page_title = $g_PageTitles[$page->text_kind];
}

$page_title = $page_title . ' - ' . $_SERVER['SERVER_NAME'];

echo <<<EOT
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title>$page_title</title>
    <link rel="stylesheet" type="text/css" href="$static_path/css/db.css">
</head>
<body bgcolor="#FFFFFF" link="#000000" alink="#000000" vlink="#000000">
EOT;

//phpinfo();
//log_err('TEST - %d', 100);
//error_log('TEST 2');

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
        case PAGE_MISSING_OR_INVALID_PARAM:
            $msg = 'Параметер запроса отсутствует или имеет неверный формат.';
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
    return;
}

if ($page->action === Page::LOGIN) {
    $login_url = $url_me . $_SERVER['PATH_INFO'];
    $cancel_url = get_login_logout_target();

    if ($page->login_error) {
        echo "<p align='center' class='style2'>Oшибка: неверный пароль или имя пользователя.</p>";
    }
    echo <<<EOT
<form enctype="multipart/form-data" action="$login_url" method="post">
  <table style='margin: auto'>
  <tr><td style='text-align: right'>Логин:</td><td><input type="text" name="id" size='9'/></td></tr>
  <tr><td style='text-align: right'>Пароль:</td><td><input type="password" name="pass" size='14'/></td></tr>
  <tr><td colspan=2 style='text-align: center'>
  <button type="submit" name='submit'>Войти</button>
  </td></tr>
  </table>
</form>
<hr>
<p align='center' class='style2'><a href='$cancel_url' class='noneline'>Вернуться назад</a></p>";
EOT;
    return;
}

$list_url = $url_me . '/' . $page->text_kind_path();
$strBackUrl   = "<p align='center' class='style2'><a href='$list_url' class='noneline'>Список {$g_DocName_1[$page->text_kind]}</a></p>";

$menu_right = '';
$menu_left = '';

if ($page->user_login === "guest") {
    $text = '[Войти]';
    $url = $url_me . '/login' . $_SERVER['PATH_INFO'];
} else {
    $text = sprintf('[Выйти]');
    $url = $url_me . '/logout';
    if (isset($page->record_id)) {
        $url .= '/show/' . $page->record_id;
    } else {
        $url .= '/' . $page->text_kind_path();
    }
}
$menu_right = "<a href='$url'>$text</a></div>";

if ($page->action === Page::LISTREC) {
    if ($page->can_write()) {
        $add_url = $url_me . '/new/' . $page->text_kind_path();
        $menu_left = "<a href='$add_url' class='noneline'>+ Добавить ещё ".$g_DocName_0[$page->text_kind]."<img width='16' height='16' border='0' src='$static_path/png/16x16.edit.png'/></a>";
    }
} elseif ($page->action === Page::SHOW) {
    if ($page->can_edit_for_sender($page->sender)) {
        $edit_url = $url_me . '/edit/' . $page->record_id;
        $menu_left = "<a href='$edit_url' class='noneline' style='vertical-align: top'>Редактировать&nbsp;<img width='16' height='16' border='0' src='$static_path/png/16x16.edit.png'/></a>";
    }
}

if ($menu_left || $menu_right) {
    echo "<div>\n";
    if ($menu_left) {
        echo "<div class='style2' style='float: left'>$menu_left</div>\n";
    }
    if ($menu_right) {
        echo "<div style='float: right'>$menu_right</div>\n";
    }
    echo "<div style='clear: both'></div>\n";
    echo "</div>\n";
}

if ($page->action === Page::LISTREC) {
    $class = "";
    $need_author = false;
    $nclasses = count($page->classes);
    if ($nclasses != 0) {
        echo "<p style='text-align: center'>";
        for ($i = 0; $i < $nclasses; $i += 2) {
            $class = $page->classes[$i + 0];
            $class_ref = get_id_for_category($class);
            if ($i !== 0) {
                echo " ";
            }
            echo "<a href='#$class_ref' style='white-space: nowrap'>[$class]</a>";
        }
        echo "</p>\n";
    }

    for ($i = 0; $i < $nclasses; $i += 2) {
        $class = $page->classes[$i + 0];
        $data = $page->classes[$i + 1];
        $class_ref = get_id_for_category($class);

        $need_author = strpos($class, "Чужие") !== false;
        $ndata = count($data);
        echo "<p align='center' class='style2' id='$class_ref'>$class</p>\n";
        echo "<table border='1' align='center' width='50%'>\n";
        for ($j = 0; $j < $ndata; $j += 4) {
            $id     = $data[$j + 0];
            $author = $data[$j + 1];
            $year   = $data[$j + 2];
            $title  = $data[$j + 3];

            echo "<tr>\n";
            echo "<td align='center' width='10%'><font class='style2'>$year</font></td>\n";
            if ($need_author) {
                echo "<td align=center><font class='style2'>&nbsp;<nobr>$author</nobr>&nbsp;</font></td>\n";
            }
            $show_url = $url_me . '/show/' . $id;
            echo "<td align='center'><a class='style2 noneline' href='$show_url'>$title</a></td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    if ($nclasses === 0) {
        echo "<p align='center' class='style2'>Ни одного файла не загружено</p>$strBackUrl";
    }

} else if ($page->action === Page::SHOW) {
    $CurYear = date ("Y", time ());
    $r_stamp = date("d M Y", $page->uploaded);

    // Create year-span
    if ($CurYear != $page->year) {
        $page->year = "$page->year-$CurYear";
    }

    echo "<p class='style2'><b>$page->author</b></p>";
    echo "<p class='style2'><b><i>&nbsp;&nbsp;&nbsp;&nbsp;$page->title</i></b></p>";
    echo "<div class='style2_pad'>$page->content</div><br>";
    // echo "<p class='style2'><font size='-1'>(c) $page->author $page->year</font></p>";
    echo $strBackUrl;

} elseif ($page->action === Page::EDIT) {
    if ($page->new_record) {
        $butTitle = "Загрузить";
        $delete_link = "";
        $action_part = '/upload/' . $page->text_kind_path();
    } else {
        $butTitle = "Сохранить";
        $delete_link = "<br><br><span class='style2'><a href='$url_me/delete/$page->record_id' class='noneline' style='vertical-align: top'>Удалить&nbsp;<img width='16' height='16' border='0' src='$static_path/png/16x16.remove.png'/></a></span>";
        $action_part = '/save/' . $page->record_id;
    }

    echo <<<EOT
<form id="edit_form" enctype="multipart/form-data" action="$url_me$action_part" method="post">
    <input type="hidden" name="epost" value="upload" />
    <input type="hidden" name="id" value="$page->record_id" />
    <center>
    <table>
    <tr>
        <td align="left"><font class='style2'>Выберите категорию</font></td>
        <td align="left">
EOT;
        echo "\n<SELECT size='1' name='group'>\n";
        // echo "<OPTION value=''></OPTION>\n";

        foreach ($page->class_list as $c) {
            $selected = '';
            if ($c == $page->class) {
                $selected = 'SELECTED ';
            }
            echo "<OPTION {$selected}value='$c'>$c</OPTION>\n";
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


} else if ($page->action === Page::DELETE) {
    $title = sprintf('&laquo;%s&raquo;', $page->title);
    if (!$page->confirmed) {
        // Ask for confirmation.
        echo <<<EOT
<div align='center' class='style2' style='text-align: center'>
<b>Вы действительно хотите удалить запись $title?</b><br><br>
<form id='confirm_delete_form' style='display: inline' method='POST'>
<button type='submit' name='confirmed' value='1'>Да</button>
</form>
<form style='display: inline' action='$url_me/show/$page->record_id' method='get'>
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

} else if ($page->action === Page::SAVE) {
    $show_url = $url_me . '/show/' . $page->record_id;
    $add_more_url = $url_me . '/new/' . $page->text_kind_path();
    $title = sprintf('&laquo;%s&raquo;', $page->title);
    echo "<center><font class='style2'>";
    if ($page->new_record) {
        echo "Запись <a href='$show_url'>$title</a> благополучно загружена в базу данных<br><br>";
    } else {
        echo "Запись <a href='$show_url'>$title</a> благополучно измененa в базе данных<br><br>";
    }
    echo "<a href='$add_more_url' class='noneline'>Добавить ещё</a>";
    echo "</font></center><br>";
    echo "<hr>" . $strBackUrl;
}
?>

</body>
</html>
