<?php

namespace session;

# Password management follows
# https://github.com/ascorbic/php-stateless-cookies/blob/master/StatelessCookie.php
# http://www.cl.cam.ac.uk/~sjm217/papers/protocols08cookies.pdf

function get_crypto_hash($value) {
    return uri_safe_base64(hash('sha256', $value, true));
}

function check_crypto_hash($value, $expected_hash) {
    return get_crypto_hash($value) === $expected_hash;
}

function get_crypto_hmac($value, $hmac_secret) {
    return uri_safe_base64(hash_hmac("sha256", $value, $hmac_secret, true));
}

function get_password_storage_hash($password) {
    $salt_and_hash = password_hash($password, PASSWORD_BCRYPT, ["cost" => 10]);
    if (strlen($salt_and_hash) != 60) {
        throw new Exception("Error creating hash");
    }
    $salt = substr($salt_and_hash, 0, 29);
    $hash = substr($salt_and_hash, 29);
    $authenticator = get_crypto_hash($hash);

    # Use custom prefix not to coincide with anything that crypt accepts
    return '$X$' . $salt . $authenticator;   
}

function parse_stored_hash_($stored_hash, &$salt, &$authenticator) {
    # 75 == strlen('X') + 29 + 43 where 43 is strlen(encoded(sha_hash()))
    if (!$stored_hash ||
        strlen($stored_hash) !== 75 ||
        substr_compare($stored_hash, '$X$', 0, 3) !== 0) {
        # Unknown $stored_hash format
        return false;
    }
    $salt = substr($stored_hash, 3, 29);
    $authenticator = substr($stored_hash, 32);
    return true;
}

function build_cookie_($user, $auth_hash, $expiration, $hmac_secret) {
    $user_str = str_replace('.', '%2E', urlencode($user));
    
    # Make url-friendly value
    $auth_hash_str = strtr($auth_hash, './', '-_');
    
    $expiration_str = base_convert(strval($expiration), 10, 36);
    
    $cookie = sprintf("%s.%s.%s", $user_str, $auth_hash_str, $expiration_str);

    $hmac_str = get_crypto_hmac($cookie, $hmac_secret);
    return $cookie . '.' . $hmac_str;
}

/**
 * Verify password and construct session cookie.
 */
function verify_password($user, $password, $expiration, $stored_hash, $hmac_secret) {
    for (;;) {
        if (!isset($user, $password, $expiration, $stored_hash, $hmac_secret))
            break;
            
        if (!parse_stored_hash_($stored_hash, $salt, $authenticator))
            break;


        $check = crypt($password, $salt);

        # More sanity check for $salt format
        if (strlen($check) !== 60 || substr_compare($check, $salt, 0, 29))
            break;

        $auth_hash = substr($check, 29);
        if (!check_crypto_hash($auth_hash, $authenticator))
            break;

        return build_cookie_($user, $auth_hash, $expiration, $hmac_secret);
    }
    return null;
}

function parse_cookie_user($cookie) {
    if (!$cookie) return null;
    $first_comma = strpos($cookie, '.');
    if ($first_comma === false || $first_comma === 0) return null;
    return urldecode(substr($cookie, 0, $first_comma));
}
    
function parse_cookie($cookie, $stored_hash, $hmac_secret) {
    $expiration = 0;
    do {
        if (!$cookie || !$stored_hash || !$hmac_secret)
            break;
        $last_separator = strrpos($cookie, '.');
        if ($last_separator === false || $last_separator + 1 + 43 != strlen($cookie))
            break;
        $hmac = substr($cookie, $last_separator + 1);
        $before_separator = substr($cookie, 0, $last_separator);
        if ($hmac !== get_crypto_hmac($before_separator, $hmac_secret))
            break;
        $a = explode('.', $cookie, 4);
        if (count($a) !== 4)
            break;
        if (!parse_stored_hash_($stored_hash, $salt, $authenticator))
            break;
        $auth_hash = strtr($a[1], '-_', './');
        if (!check_crypto_hash($auth_hash, $authenticator))
            break;
        $expiration = intval($a[2], 36);
    } while (false);
    return (object) [ 'expiration' => $expiration ];
}

?>
