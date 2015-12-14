<?php
require "defines.inc.php";
require "utils.php";
require "session.php";

function update_password($user, $password) {
    $n = strlen($password);
    if (!(1 <= $n && $n <= 70)) {
        log_err(sprintf("password length %d is outside the permitted range [1, 70]", n));
        return;
    }

    $hash = session\get_password_storage_hash($password);
    $stmt = db_prepare("UPDATE %s SET passhash=? WHERE nickname=?", DEFS_DB_TABLE_USERS);
    db_bind_value($stmt, 1, $hash, PDO::PARAM_STR);
    db_bind_value($stmt, 2, $user, PDO::PARAM_STR);
    db_execute($stmt);
    $nchanged = db_row_count($stmt);
    db_close($stmt);
    if ($nchanged === 1) {
        log_info('Updated password for %s', $user);
    } else if (db_ok()) {
        log_err("Failed to locate the user '%s'", $user);
    }
}

$options = getopt("u:p");
if ($options === false) {
    log_err('Bad usage');
}

if (!isset($options['u'])) {
    log_err('-u is required');
    exit();
}

$user = $options['u'];

if (isset($options['p'])) {

    $password =  stream_get_contents(STDIN);
    if ($password === false) {
        log_err('failed to read password from stdin');
    } else {
        update_password($user, $password);
    }
    


}

?>
