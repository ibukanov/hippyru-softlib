<?php
$error_messages = array();

function get_log_path() {
    $path = getenv('LOG_PATH');
    return $path ? $path : 'php://stderr';
}

function log_err($msg) {
    global $error_messages;
    if (func_num_args() > 1) {
        $args = func_get_args();
        array_shift($args);
        $msg = vsprintf($msg, $args);
    }
    file_put_contents(get_log_path(), sprintf("ERROR: %s\n", $msg));
    array_push($error_messages, $msg);
}

function log_info($msg) {
    if (func_num_args() > 1) {
        $args = func_get_args();
        array_shift($args);
        $msg = vsprintf($msg, $args);
    }
    file_put_contents(get_log_path(), $msg . "\n");
}

function uri_safe_base64($str) {
    return strtr(rtrim(base64_encode($str), '='), '+/', '-_');
}

function u_read_file($path) {
    if (!isset($path)) return null;
    $str = file_get_contents($path);
    if ($str === false) {
        log_err("failed to read $path");
        return null;
    }
    return $str;
}

function u_parse_json($str) {
    if (!isset($str)) return null;
    $obj = json_decode($str, true);
    return $obj;
}

$db_connection = null;

// Number of DB errors after successful db_connect()
$db_errors = 0;

function db_err($message) {
    global $db_errors;
    $db_errors += 1;
    log_err($message);
}

function db_failed() {
    global $db_errors;

    return !!$db_errors;
}

function db_ok() {
    return !db_failed();
}

function db_connect() {
    global $db_connection, $db_errors;

    if (isset($db_connection)) {
        if ($db_errors)
            return null;
        return $db_connection;
    }
    
    $access_path = getenv('DB_ACCESS_FILE');
    if (!$access_path) {
        db_err("environment variable DB_ACCESS_FILE is not set or empty");
        return null;
    }
    
    $config = u_parse_json(u_read_file($access_path));
    if (!isset($config)) {
        db_err("Failed to parse the DB configuration in $access_path as JSON file");
        return null;
    }
    
    $db = new mysqli($config['host'], $config['user'], $config['password'], $config['database']);
    if ($db->connect_errno) {
        db_err(sprintf("Failed to connect to MySQL at %s: (%d) %s",
                       $config['host'], $db->connect_errno, $db->connect_error));
        return null;
    }

    if (!$db->set_charset('utf8')) {
        db_err(sprintf("set_charset('utf8') failed: (%d) %s", $sql, $db->errno, $db->error));
        return null;
    }

    $db_connection = $db;
    return $db;
}

function db_query($sql) {
    if (!isset($sql) || !($db = db_connect()))
        return null;
    if (func_num_args() > 1) {
        $args = func_get_args();
        array_shift($args);
        $sql = vsprintf($sql, $args);
    }
    $status = $db->query($sql);
    if ($status === false) {
        db_err(sprintf("query(%s) failed: (%d) %s", $sql, $db->errno, $db->error));
        return null;
    }
    return $status;
}

function db_prepare($sql) {
    if (!isset($sql) || !($db = db_connect()))
        return null;
    if (func_num_args() > 1) {
        $args = func_get_args();
        array_shift($args);
        $sql = vsprintf($sql, $args);
    }
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        db_err(sprintf("prepare(%s) failed: (%d) %s", $sql, $db->errno, $db->error));
        return null;
    }
    return $stmt; 
}

function db_bind_param($stmt, $type, &$v) {
    if (!isset($stmt) || db_failed())
        return;
    if (!$stmt->bind_param($type, $v)) {
        db_err(sprintf("bind_param() failed: (%d) %s", $stmt->errno, $stmt->error));
    }
}

function db_bind_param2($stmt, $types, &$v1, &$v2) {
    if (!isset($stmt) || db_failed())
        return;
    if (!$stmt->bind_param($types, $v1, $v2)) {
        db_err(sprintf("bind_param() failed: (%d) %s", $stmt->errno, $stmt->error));
    }
}

function db_bind_param3($stmt, $types, &$v1, &$v2, &$v3) {
    if (!isset($stmt) || db_failed())
        return;
    if (!$stmt->bind_param($types, $v1, $v2, $v3)) {
        db_err(sprintf("bind_param() failed: (%d) %s", $stmt->errno, $stmt->error));
    }
}

function db_bind_param4($stmt, $types, &$v1, &$v2, &$v3, &$v4) {
    if (!isset($stmt) || db_failed())
        return;
    if (!$stmt->bind_param($types, $v1, $v2, $v3, $v4)) {
        db_err(sprintf("bind_param() failed: (%d) %s", $stmt->errno, $stmt->error));
    }
}

function db_bind_param5($stmt, $types, &$v1, &$v2, &$v3, &$v4, &$v5) {
    if (!isset($stmt) || db_failed())
        return;
    if (!$stmt->bind_param($types, $v1, $v2, $v3, $v4, $v5)) {
        db_err(sprintf("bind_param() failed: (%d) %s", $stmt->errno, $stmt->error));
    }
}

function db_bind_param7($stmt, $types, &$v1, &$v2, &$v3, &$v4, &$v5, &$v6, &$v7) {
    if (!isset($stmt) || db_failed())
        return;
    if (!$stmt->bind_param($types, $v1, $v2, $v3, $v4, $v5, $v6, $v7)) {
        db_err(sprintf("bind_param() failed: (%d) %s", $stmt->errno, $stmt->error));
    }
}

function db_bind_param6($stmt, $types, &$v1, &$v2, &$v3, &$v4, &$v5, &$v6) {
    if (!isset($stmt) || db_failed())
        return;
    if (!$stmt->bind_param($types, $v1, $v2, $v3, $v4, $v5, $v6)) {
        db_err(sprintf("bind_param() failed: (%d) %s", $stmt->errno, $stmt->error));
    }
}

function db_bind_param8($stmt, $types, &$v1, &$v2, &$v3, &$v4, &$v5, &$v6, &$v7, &$v8) {
    if (!isset($stmt) || db_failed())
        return;
    if (!$stmt->bind_param($types, $v1, $v2, $v3, $v4, $v5, $v6, $v7, $v8)) {
        db_err(sprintf("bind_param() failed: (%d) %s", $stmt->errno, $stmt->error));
    }
}

function db_send_long_data($stmt, $column, $data) {
    if (!isset($stmt) || db_failed())
        return;
    if (!$stmt->send_long_data($column, $data)) {
        db_err(sprintf("send_long_data() failed: (%d) %s", $stmt->errno, $stmt->error));
    }
}

function db_execute($stmt) {
    if (!isset($stmt) || db_failed())
        return;
    if (!$stmt->execute()) {
        db_err(sprintf("execute() failed: (%d) %s", $stmt->errno, $stmt->error));
    }
}

function db_insert_id() {
    if (!($db = db_connect()))
        return 0;
    return $db->insert_id;
}

function db_store_result($stmt) {
    if (!isset($stmt) || db_failed())
        return null;
    if (!$stmt->store_result()) {
        db_err("store_result() failed: (%d) %s", $stmt->errno, $stmt->error);
    }
    return null;
}

function db_bind_result($stmt, &$v) {
    if (!isset($stmt) || db_failed())
        return;
    if (!$stmt->bind_result($v)) {
        db_err(sprintf("bind_result() failed: (%d) %s", $stmt->errno, $stmt->error));
    }
}

function db_bind_result2($stmt, &$v1, &$v2) {
    if (!isset($stmt) || db_failed())
        return;
    if (!$stmt->bind_result($v1, $v2)) {
        db_err(sprintf("bind_result() failed: (%d) %s", $stmt->errno, $stmt->error));
    }
}

function db_bind_result4($stmt, &$v1, &$v2, &$v3, &$v4) {
    if (!isset($stmt) || db_failed())
        return;
    if (!$stmt->bind_result($v1, $v2, $v3, $v4)) {
        db_err(sprintf("bind_result() failed: (%d) %s", $stmt->errno, $stmt->error));
    }
}

function db_bind_result6($stmt, &$v1, &$v2, &$v3, &$v4, &$v5, &$v6) {
    if (!isset($stmt) || db_failed())
        return;
    if (!$stmt->bind_result($v1, $v2, $v3, $v4, $v5, $v6)) {
        db_err(sprintf("bind_result() failed: (%d) %s", $stmt->errno, $stmt->error));
    }
}

function db_bind_result7($stmt, &$v1, &$v2, &$v3, &$v4, &$v5, &$v6, &$v7) {
    if (!isset($stmt) || db_failed())
        return;
    if (!$stmt->bind_result($v1, $v2, $v3, $v4, $v5, $v6, $v7)) {
        db_err(sprintf("bind_result() failed: (%d) %s", $stmt->errno, $stmt->error));
    }
}

function db_fetch($stmt) {
    if (!isset($stmt) || db_failed())
        return false;
    $status = $stmt->fetch();
    if ($status !== true) {
        if ($status === false)
            db_err(sprintf("fetch() failed: (%d) %s", $stmt->errno, $stmt->error));
        return false;
    }
    return true;
}

function db_get_result($stmt) {
    if (!isset($stmt) || db_failed())
        return null;
    $result = $stmt->get_result();
    if (!$result) {
        db_err("result() failed: (%d) %s", $stmt->errno, $stmt->error);
        return null;
    }
    return $result;
}

function db_affected_rows($stmt) {
    if (!isset($stmt) || db_failed())
        return null;
    $num = $stmt->affected_rows;
    if (!is_int($num) || $num < 0)
        return 0;
    return $num;
}

function db_fetch_all($result) {
    if (!isset($result) || db_failed())
        return array();
    return $result->fetch_all();
}

function db_close($stmt) {
    if (isset($stmt)) {
        $stmt->close();
    }
}

function db_free($result) {
    if (!is_null($result)) {
        $result->free();
    }
}

?>
