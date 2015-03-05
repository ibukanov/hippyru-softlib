<?php

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

// Check if can modify/delete record with the given $sender
function can_edit_for_sender($sender) {
    global $strUserName;
    return isSuperuser() || ($sender && $sender === $strUserName);
}

function get_user_info($login) {
    $ui = new stdClass();
    $stmt = db_prepare(
        "SELECT name, passhash, cookiesalt, access " .
        "FROM %s WHERE nickname=?", DB_TABLE_USERS);
    db_bind_param($stmt, "s", $login);
    db_execute($stmt);
    db_bind_result4($stmt, $ui->name, $ui->passhash, $ui->cookiesalt, $ui->access);
    db_fetch($stmt);
    db_close($stmt);
    if (!db_ok())
        return PAGE_DB_ERR;
    return $ui;
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
    global $eUserAccess, $strUserName_Full, $strUserName;

    if (!isset($_COOKIE['u']))
        return;

    $cookie = $_COOKIE['u'];
    $user = session\parse_cookie_user($cookie);
    if (!$user)
        return;

    $user_info = get_user_info($user);

    // TODO report DB errors
    if (is_object($user_info)) {
        $cookie_info = session\parse_cookie($cookie, $user_info->passhash,
                                            get_hmac_secret($user_info));
        if ($cookie_info->expiration > time()) {
            $eUserAccess      = $user_info->access;
            $strUserName_Full = $user_info->name;
            $strUserName      = $user;
        } else {
            // Invalidate the ID.
            drop_login_cookie();
        }
    }
}

/// Returns 0, if login is successful.
function user_login($user, $pass) {
    $user_info = get_user_info($user);
    if (!is_object($user_info))
        return $user_info;

    $expiration = time() + 12 * 3600;
    $cookie = session\verify_password($user, $pass, $expiration,
                                      $user_info->passhash, get_hmac_secret($user_info));
    if (!isset($cookie))
        return PAGE_BAD_LOGIN;

    set_login_cookie($cookie, $expiration);
    return 0;
}

function user_logout() {
    global $strUserName;

    drop_login_cookie();

    # Logout all sessions by changing the hmac secret for the login cookie.
    if ($strUserName && $strUserName !== "guest") {
        $salt = openssl_random_pseudo_bytes(6);
        $stmt = db_prepare("UPDATE %s SET cookiesalt=? WHERE nickname=?", DB_TABLE_USERS);
        db_bind_param2($stmt, "ss", $salt, $strUserName);
        db_execute($stmt);
        $naffected = db_affected_rows($stmt);
        db_close($stmt);
        if (!db_ok())
            return PAGE_DB_ERR;
        if ($naffected !== 1) {
            db_err("Failed to update login record for $strUserName");
            return PAGE_DB_ERR;
        }
    }
    return 0;
}

function check_post_key() {
    global $saved_pkey_cookie;
    return isset($_POST["pkey"]) && $_POST["pkey"] === $saved_pkey_cookie;
}

check_login_cookie();

?>
