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
    $auth_hash = substr($salt_and_hash, 29);

    // make $auth_hash friendly to uri encoding as it is a part of the cookie
    $auth_hash = strtr($auth_hash, './', '-_');

    $authenticator = get_crypto_hash($auth_hash);

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

/**
 * Verify password and construct session cookie.
 */
function verify_password($user, $password, $expiration, $stored_hash, $hmac_secret) {
    if (!isset($user, $password, $expiration, $stored_hash, $hmac_secret))
        return;

    if (!parse_stored_hash_($stored_hash, $salt, $authenticator))
        return;

    $check = crypt($password, $salt);

    # More sanity check for $salt format
    if (strlen($check) !== 60 || substr_compare($check, $salt, 0, 29))
        return;

    $auth_hash = substr($check, 29);
    $auth_hash = strtr($auth_hash, './', '-_');

    if (!check_crypto_hash($auth_hash, $authenticator))
        return;

    return build_cookie_($user, $auth_hash, $expiration, $hmac_secret);
}

function build_cookie_($user, $auth_hash, $expiration, $hmac_secret) {
    $user_str = str_replace('.', '%2E', urlencode($user));

    $expiration_str = base_convert(strval($expiration), 10, 36);

    $payload = sprintf("%s.%s.%s", $user_str, $auth_hash, $expiration_str);
    $hmac = get_crypto_hmac($payload, $hmac_secret);
    return $payload . '.' . $hmac;
}

class Cookie {
    public $user;
    public $expiration;

    private $cookie;
    private $dot1;
    private $auth_hash;
    private $hmac_secret;

    function __construct($cookie) {
        $this->cookie = $cookie;
    }

    function parse_user() {
        if (!isset($this->cookie))
            return;
        $dot1 = strpos($this->cookie, '.');
        if ($dot1 === false || $dot1 === 0)
            return;

        $this->user = urldecode(substr($this->cookie, 0, $dot1));
        $this->dot1 = $dot1;
    }

    function validate($stored_hash, $hmac_secret) {
        if (!isset($stored_hash, $hmac_secret, $this->user))
            return;
        $dot2 = strpos($this->cookie, '.', $this->dot1 + 1);
        if ($dot2 === false)
            return;
        $dot3 = strpos($this->cookie, '.', $dot2 + 1);
        if ($dot3 === false)
            return;
        if ($dot3 + 1 + 43 != strlen($this->cookie))
            return;
        $payload = substr($this->cookie, 0, $dot3);
        $hmac = get_crypto_hmac($payload, $hmac_secret);
        if (substr_compare($this->cookie, $hmac, $dot3 + 1) !== 0)
            return;
        if (!parse_stored_hash_($stored_hash, $salt, $authenticator))
            return;
        $auth_hash = substr($this->cookie, $this->dot1 + 1, $dot2 - ($this->dot1 + 1));
        if (!check_crypto_hash($auth_hash, $authenticator))
            return;
        $expiration_str = substr($this->cookie, $dot2 + 1, $dot3 - ($dot2 + 1));
        $this->expiration = intval($expiration_str, 36);
        $this->auth_hash = $auth_hash;
        $this->hmac_secret = $hmac_secret;
    }

    function refresh($new_expiration) {
        if (!isset($new_expiration, $this->auth_hash))
            return;

        return build_cookie_($this->user, $this->auth_hash, $new_expiration,
                             $this->hmac_secret);
    }
}
?>
