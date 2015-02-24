<?php
if (!defined ("INCLUDE_LEGAL")) die ("File execution is prohibited.");

/**
 * Try to log the user in,
 * using POST data.
 */

/// Returns TRUE, if
/// login is successful.
function user_login ($name, $pass) {
    global $mysql_host;
    global $mysql_database;
    global $mysql_user;
    global $mysql_password;
    global $mysql_table_users;

    $name_chk = addslashes ($name);

    $query = "SELECT passhash FROM $mysql_database.$mysql_table_users WHERE nickname='$name_chk'";
#    $query = "SELECT passhash FROM $mysql_table_users WHERE nickname='$name_chk'";

    if ($db = mysql_connect ($mysql_host, $mysql_user, $mysql_password)) {
         mysql_set_charset("utf8");
#			die($query);

        if ($result = mysql_query ($query)) {
            // Check pass
            if (MD5 ($pass) == mysql_result ($result, 0, "passhash")) {
                // Create cookie
#				die("blya");
                $ip    = $_SERVER['REMOTE_ADDR'];
                $epass = MD5 ($pass);
                $stamp = MD5 (time ());
                $cookie_val = $name_chk."-".$epass."-".$stamp;

                setcookie   ("udscr", $cookie_val, time() + 12 * 3600);
                mysql_query ("UPDATE $mysql_database.$mysql_table_users SET lastip='$ip' WHERE nickname='$name_chk'");
                mysql_close ($db);
                return TRUE;
            }
        }

        mysql_close ($db);
    }

    return FALSE;
}

/// Returns TRUE, if
/// logout is successful.
function user_logout () {
    setcookie ("udscr", "", time () - 12 * 3600);
    return TRUE;
}
?>
