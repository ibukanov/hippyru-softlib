<?php

/**
 * Establish the user-data of
 * the caller, if authorized.
 */

function get_user_info($login) {
    if (!isset($login))
        return;
    $ui = new stdClass();
    $stmt = db_prepare(
        "SELECT name, passhash, cookiesalt, access " .
        "FROM %s WHERE nickname=?", DEFS_DB_TABLE_USERS);
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
        return;
    return DEF_LOGIN_SEED . $user_info->cookiesalt;
}

function set_login_cookie($value, $expiration) {
    setcookie('u', $value, $expiration, '', '', true, true);
}

function drop_login_cookie() {
    setcookie('u', '', 1, '', '', true, true);
}

function check_login_cookie(Page $page, $refresh) {
    if (!isset($_COOKIE['u']))
        return;

    do {
        $cookie = new session\Cookie($_COOKIE['u']);
        $cookie->parse_user();
        $user_info = get_user_info($cookie->user);

        // TODO report DB errors
        if (!is_object($user_info))
            break;

        $cookie->validate($user_info->passhash, get_hmac_secret($user_info));
        $time = time();
        if ((int) $cookie->expiration < $time)
            break;
        
        $page->user_login     = $cookie->user;
        $page->user_access    = $user_info->access;
        $page->user_full_name = $user_info->name;

        if ($refresh) {
            $new_expiration = $time + DEFS_LOGIN_DURATION;
            set_login_cookie($cookie->refresh($new_expiration), $new_expiration);
        }
        return;
    } while (false);

    // Invalidate the ID.
    drop_login_cookie();
}

/// Returns 0, if login is successful.
function user_login($user, $pass) {
    $user_info = get_user_info($user);
    if (!is_object($user_info))
        return $user_info;

    $expiration = time() + DEFS_LOGIN_DURATION;
    $cookie = session\verify_password($user, $pass, $expiration,
                                      $user_info->passhash, get_hmac_secret($user_info));
    if (!isset($cookie))
        return PAGE_BAD_LOGIN;

    set_login_cookie($cookie, $expiration);
}

function user_logout(Page $page) {
    drop_login_cookie();

    # Logout all sessions by changing the hmac secret for the login cookie.
    if ($page->user_login && $page->user_login !== "guest") {
        $salt = openssl_random_pseudo_bytes(6);
        $stmt = db_prepare("UPDATE %s SET cookiesalt=? WHERE nickname=?", DEFS_DB_TABLE_USERS);
        db_bind_param2($stmt, "ss", $salt, $page->user_login);
        db_execute($stmt);
        $naffected = db_affected_rows($stmt);
        db_close($stmt);
        if (!db_ok())
            return PAGE_DB_ERR;
        if ($naffected !== 1) {
            db_err("Failed to update login record for $page->user_login");
            return PAGE_DB_ERR;
        }
    }
}
