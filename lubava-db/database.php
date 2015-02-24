<?php
    define ("INCLUDE_LEGAL", TRUE);

    require_once "database/defines.inc.php";
    require_once "database/utils.filter.inc.php";
    require_once "database/req_type.inc.php";
    require_once "database/user_establish.inc.php";
    require_once "database/user_login.inc.php";
    require_once "database/form_loginout.inc.php";
?>
<?php
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
        if ($pageid < 0 || $pageid >= count ($url_files)) {
            $pageid = 0;
        }
    }
}

$strBackUrl   = "<p align='center' class='style2'><a href='".$url_me."?mode=title&pageid=$pageid' class='noneline'>Назад</a></p>";
$strBackUrl_1 = "<p align='center' class='style2'><a href='".$url_me."?mode=list&pageid=$pageid' class='noneline'>Назад</a></p>";

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
    if (user_logout ()) {
        header ("Location: " . $url_me . "?pageid=$pageid");
    }

    $mode = "skip";
}
    
/************ DISPLAY THE HEADER **************/

echo <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
       "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>$g_PageTitles[$pageid]</title>
    <link rel="stylesheet" type="text/css" href="$static_path/css/lubava.white.css"/>
</head>
<body bgcolor="#FFFFFF" link="#000000" alink="#000000" vlink="#000000">
EOT;

//
// Display login/logout form
//
if ($mode != "skip") {
    display_form_login ();
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
        require_once ("database/pagephp_list.inc.php");

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
    require_once ("database/pagephp_list.inc.php");

} else if ($mode == "ask_file" ||
           $mode == "edit"
) {
//
// Form for text uploading.
//
    require_once ("database/pagephp_edit.inc.php");

} else if ($mode == 'delete') {
//
// Delete specific text
//
    require_once ("database/pagephp_delete.inc.php");

} else if ($mode == 'showtext') {
//
// Show specific text
//
    require_once ("database/pagephp_show.inc.php");

} else if ($mode == 'upload') {
//
// Upload the file on server.
//
    require_once ("database/pagephp_upload.inc.php");
/*
} else if ($mode == "install") {
//
// Install the script.
// This creates the tables in database.
//
    require_once ("database/pagephp_install.inc.php");
*/
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
