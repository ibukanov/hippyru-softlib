<?php
if (!defined ("INCLUDE_LEGAL")) die ("File execution is prohibited.");

$stop_error = false;

function failed() {
    global $stop_error;
    return $stop_error;
}

function is_ok() {
    global $stop_error;
    return !$stop_error;
}

function err($msg) {
    global $stop_error;
    $stop_error = true;
    error_log("$msg\n", 3, "/dev/stderr");
    echo $msg;
    return null;
}

function u_read_file($path) {
    if (failed() || is_null($path)) return null;
    $str = file_get_contents($path);
    if ($str === false) return err("failed to read $path");
    return $str;
}

function u_parse_json($str) {
    if (is_null($str)) return null;
    $obj = json_decode($str, true);
    return $obj;
}

function db_connect() {
    if (failed()) return null;

    $access_path = getenv('DB_ACCESS_FILE');
    if (!$access_path)
        return err("environment variable DB_ACCESS_FILE is not set or empty");
    
    $config = u_parse_json(u_read_file($access_path));
    if (is_null($config)) return null;
    
    $db = new mysqli($config['host'], $config['user'], $config['password'], $config['database']);
    if ($db->connect_errno) {
        err("Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error);
        return null;
    }

    $db->set_charset("utf8");
    
    return $db;
}

function db_prepare($db, $sql) {
    if (failed() || is_null($db)) return null;
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        err("prepare() failed: (" . $db->errno . ") " . $db->error);
        return null;
    }
    return $stmt; 
}

function db_bind_param($stmt, $type, &$var) {
    if (failed() || is_null($stmt)) return;
    if (!$stmt->bind_param($type, $var)) {
        err("bind_param() failed: (" . $stmt->errno . ") " . $stmt->error);
    }
}

function db_execute($stmt) {
    if (failed() || is_null($stmt)) return;
    if (!$stmt->execute()) {
        err("execute() failed: (" . $stmt->errno . ") " . $stmt->error);
    }
}

function db_get_result($stmt) {
    if (failed() || is_null($stmt)) return null;
    $result =  mysqli_stmt_get_result($stmt);
    //$result = $stmt->get_result();
    if (!$result) {
        err("result() failed: (" . $stmt->errno . ") " . $stmt->error);
        return null;
    }
    return $result;
}

function db_affected_rows($stmt) {
    if (failed() || is_null($stmt)) return null;
    $num = $stmt->affected_rows;
    if (!is_int($num) || $num < 0) return 0;
    return $num;
}

function db_fetch_assoc($result) {
    if (failed() || is_null($result)) return null;
    $row = $result->fetch_assoc();

    // TODO find out how to distinguish null from errors
    return $row;
}

function db_close($db_object) {
    if (!is_null($db_object)) {
        $db_object->close();
    }
}

function db_free($result) {
    if (!is_null($result)) {
        $result->free();
    }
}

?>
