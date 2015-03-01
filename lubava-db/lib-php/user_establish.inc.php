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

function get_user_info($login) {
    global $mysql_table_users;

    $row = null;
    if ($login) {
        $stmt = db_prepare("SELECT name, passhash, cookiesalt, access " .
                           "FROM $mysql_table_users WHERE nickname=?");
        db_bind_param($stmt, "s", $login);
        db_execute($stmt);
        $result = db_get_result($stmt);
        $row = db_fetch_row($result);
        db_free($result);
        db_close($stmt);
    }
    if ($row)
        return $row;

    return (object) ['name' => null,
                     'passhash' => null,
                     'cookiesalt' => null,
                     'access' => null];
}

function get_hmac_secret($user_info) {
    if (!isset($user_info->cookiesalt))
        return null;
    $secret = u_read_file("/area/weblogin/lubava.info.seed");
    if (!isset($secret))
        return null;
    return $secret . $user_info->cookiesalt;
}

function set_login_cookie($value, $expiration) {
    setcookie('u', $value, $expiration, '', '', true, true);
}

function drop_login_cookie() {
    setcookie('u', '', 1, '', '', true, true);
}

function check_login_cookie() {
    global $eUserAccess, $strUserName_Full, $strUserName, $mode;
    
    if (!isset($_COOKIE['u']))
        return;

    $cookie = $_COOKIE['u'];
    $user = session\parse_cookie_user($cookie);
    $user_info = get_user_info($user);
    $cookie_info = session\parse_cookie($cookie, $user_info->passhash, get_hmac_secret($user_info));
    if ($cookie_info->expiration > time()) {
        $eUserAccess      = $user_info->access;
        $strUserName_Full = $user_info->name;
        $strUserName      = $user;
    } else {
        // Invalidate the ID.
        drop_login_cookie();
            
        // Kick user back
        header ("Location: " . $url_me); 
        
        $mode = "skip";
    }
}

/// Returns TRUE, if
/// login is successful.
function user_login ($user, $pass) {
    $user_info = get_user_info($user);
    $expiration = time() + 12 * 3600;
    $cookie = session\verify_password($user, $pass, $expiration,
                                      $user_info->passhash, get_hmac_secret($user_info));
    if (isset($cookie)) {
        set_login_cookie($cookie, $expiration);
        return true;
    }
    return false;
}

function user_logout () {
    drop_login_cookie();
}

check_login_cookie();

?>