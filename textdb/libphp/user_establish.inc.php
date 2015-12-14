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
    db_bind_value($stmt, 1, $login, PDO::PARAM_STR);
    db_execute($stmt);

    db_bind_column($stmt, 1, $ui->name, PDO::PARAM_STR);
    db_bind_column($stmt, 2, $ui->passhash, PDO::PARAM_STR);
    db_bind_column($stmt, 3, $ui->cookiesalt, PDO::PARAM_STR);
    db_bind_column($stmt, 4, $ui->access, PDO::PARAM_INT);
    db_fetch_bound($stmt);
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
    global $url_me;
    setcookie('u', $value, $expiration, $url_me . '/', '', true, true);
}

function drop_login_cookie() {
    global $url_me;
    setcookie('u', '', 1, $url_me . '/', '', true, true);
}

function check_login_cookie(Page $page, $refresh) {
    if (isset($page->error))
        return;

    if (!isset($_COOKIE['u']))
        return;

    $cookie = new session\Cookie($_COOKIE['u']);
    $cookie->parse_user();

    $user_info = get_user_info($cookie->user);

    if (!is_object($user_info)) {
        $page->error = $user_info;
        goto drop;
    }

    $cookie->validate($user_info->passhash, get_hmac_secret($user_info));
    $time = time();

    if ((int) $cookie->expiration < $time)
        goto drop;

    $page->user_login     = $cookie->user;
    $page->user_access    = $user_info->access;
    $page->user_full_name = $user_info->name;

    if ($refresh) {
        $new_expiration = $time + DEFS_LOGIN_DURATION;
        set_login_cookie($cookie->refresh($new_expiration), $new_expiration);
    }
    return;

    drop:
    drop_login_cookie();
}

/// Returns 0, if login is successful.
function user_login($user, $pass) {
    if (!isset($user, $pass))
        return PAGE_MISSING_OR_INVALID_PARAM;

    $user_info = get_user_info($user);
    if (!is_object($user_info))
        return $user_info;
    if (!isset($user_info->name)) {
        // TODO report bad user name
        return PAGE_BAD_LOGIN;
    }

    $expiration = time() + DEFS_LOGIN_DURATION;
    $cookie = session\verify_password($user, $pass, $expiration,
                                      $user_info->passhash, get_hmac_secret($user_info));
    if ($cookie === false)
        return PAGE_MISSING_OR_INVALID_PARAM;
    if ($cookie === null)
        return PAGE_BAD_LOGIN;

    set_login_cookie($cookie, $expiration);
}

function user_logout(Page $page) {
    drop_login_cookie();

    # Logout all sessions by changing the hmac secret for the login cookie.
    if ($page->user_login && $page->user_login !== "guest") {
        $salt = openssl_random_pseudo_bytes(6);
        $stmt = db_prepare("UPDATE %s SET cookiesalt=? WHERE nickname=?", DEFS_DB_TABLE_USERS);
        db_bind_value($stmt, 1, $salt, PDO::PARAM_STR);
        db_bind_value($stmt, 2, $page->user_login, PDO::PARAM_STR);
        db_execute($stmt);
        $nchanged = db_row_count($stmt);
        db_close($stmt);
        if (!db_ok())
            return PAGE_DB_ERR;
        if ($nchanged !== 1) {
            db_err("Failed to update login record for $page->user_login");
            return PAGE_DB_ERR;
        }
    }
}
