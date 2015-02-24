<?php
if (!defined ("INCLUDE_LEGAL")) die ("File execution is prohibited.");

/**
 * Establish the user-data of
 * the caller, if authorized.
 */

define ("ACCESS_BANNED",    0);
define ("ACCESS_LEGAL",     1);
define ("ACCESS_SUPERUSER", 2);
define ("ACCESS_ADMIN",     3);

//
// Check if user is
// logged in
$strUserName      = "guest";
$strUserName_Full = "guest";
$eUserAccess      = ACCESS_BANNED;

/// Check if the caller
/// is the superuser.
function isSuperuser () {
    global $eUserAccess;

    return ($eUserAccess & ACCESS_SUPERUSER) == ACCESS_SUPERUSER;
}

/// Check if the caller
/// has write access
function isWritePermitted () {
    global $eUserAccess;

    return ($eUserAccess & ACCESS_LEGAL) == ACCESS_LEGAL;
}

if (isset ($_COOKIE['udscr'])) {
    $splitted = preg_split("/-/", $_COOKIE['udscr']);
    $login    = $splitted[0];

    $query = "SELECT name, access, lastip FROM $mysql_database.$mysql_table_users WHERE nickname = '$login'";

    if ($db = mysql_connect ($mysql_host, $mysql_user, $mysql_password)) {
        mysql_set_charset("utf8");
        if ($result = mysql_query ($query)) {
            $ip_now = $_SERVER['REMOTE_ADDR'];
            $ip_old = mysql_result ($result, 0, "lastip");
//echo $login . $eUserAccess. "  " . $ip_now . "  " . $ip_old;
            // Check IP address
            if ($ip_now == $ip_old) {
                $eUserAccess      = mysql_result ($result, 0, "access");
                $strUserName_Full = mysql_result ($result, 0, "name");
                $strUserName      = $login;
            } else {
                // Possible session hi-jack.
                // Invalidate the ID.
                setcookie ("udscr", "", time () - 3600);

                // Kick user back
                header ("Location: " . $url_me); 

                $mode = "skip";
            }
        }

        mysql_close ($db);
    }
}
?>
