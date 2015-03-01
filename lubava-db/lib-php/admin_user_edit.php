<?php
require "defines.inc.php";
require "utils.php";
require "session.php";

function update_password($user, $password) {
    global $mysql_table_users;
    
    $n = strlen($password);
    if (!(1 <= $n && $n <= 70)) {
        log_err(sprintf("password length %d is outside the permitted range [1, 70]", n));
        return;
    }

    $hash = session\get_password_storage_hash($password);
    $stmt = db_prepare("UPDATE $mysql_table_users SET passhash=? WHERE nickname=?");
    db_bind_param2($stmt, "ss", $hash, $user);
    db_execute($stmt);
    $naffected = db_affected_rows($stmt);
    db_close($stmt);
    if ($naffected === 1) {
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


//db_query ("ALTER TABLE $mysql_table ADD COLUMN (pageid int(4))");
//db_query ("UPDATE $mysql_database.$mysql_table SET pageid=0 WHERE pageid IS NULL");
//mysql_query ("UPDATE $mysql_database.$mysql_table SET contents=REPLACE(contents, 'txtbase/data', 'database/data/texts')");

?>
