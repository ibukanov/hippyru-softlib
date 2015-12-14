<?php
$error_messages = array();

function get_log_path() {
    return defined('DEF_LOG_PATH') ? DEF_LOG_PATH : 'php://stderr';
}

function log_err($msg) {
    global $error_messages;
    if (func_num_args() > 1) {
        $args = func_get_args();
        array_shift($args);
        $msg = vsprintf($msg, $args);
    }
    $text = sprintf("ERROR: %s\n", $msg);
    file_put_contents(get_log_path(), $text);
    array_push($error_messages, $msg);
}

function log_info($msg) {
    if (func_num_args() > 1) {
        $args = func_get_args();
        array_shift($args);
        $msg = vsprintf($msg, $args);
    }
    $text = $msg . "\n";
    file_put_contents(get_log_path(), $text);
}

function uri_safe_base64($str) {
    return strtr(rtrim(base64_encode($str), '='), '+/', '-_');
}

function escape_html_text($str) {
    return htmlspecialchars($str, ENT_NOQUOTES | ENT_HTML401 | ENT_DISALLOWED, 'UTF-8');
}

function u_read_file($path) {
    if (!isset($path)) return null;
    $str = file_get_contents($path);
    if ($str === false) {
        log_err("failed to read $path");
        return;
    }
    return $str;
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

    try {
        $db = new PDO(DEF_DB_DSN, DEF_DB_USER, DEF_DB_PASSWORD);
    } catch (PDOException $e) {
        db_err(sprintf("Failed to connect to '%s': (%d) %s",
                       DEF_DB_DSN, $e->getCode(), $e->getMessage()));
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
        $info = $db->errorInfo();
        db_err(sprintf("query(%s) failed: (%d) %s", $sql, $info[1], $info[2]));
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
        $info = $db->errorInfo();
        db_err(sprintf("prepare(%s) failed: (%d) %s", $sql, $info[1], $info[2]));
        return null;
    }
    return $stmt;
}

function db_bind_value($stmt, $parameter, $value, $type) {
    if (!isset($stmt) || db_failed())
        return;
    if (!$stmt->bindValue($parameter, $value, $type)) {
        $info = $stmt->errorInfo();
        db_err(sprintf("bind_value(%s, %s, %s) failed: (%d) %s",
                       $parameter, $value, $type,
                       $info[1], $info[2]));
    }
}

function db_execute($stmt) {
    if (!isset($stmt) || db_failed())
        return;
    if (!$stmt->execute()) {
        $info = $stmt->errorInfo();
        db_err(sprintf("execute() failed: (%d) %s", $info[1], $info[2]));
    }
}

function db_last_insert_id($columnName) {
    if (!($db = db_connect()))
        return 0;
    return $db->lastInsertId();
}

function db_bind_column($stmt, $column, &$v, $type) {
    if (!isset($stmt) || db_failed())
        return;
    if (!$stmt->bindColumn($column, $v, $type)) {
        $info = $stmt->errorInfo();
        db_err(sprintf("bind_column(%s) failed: (%d) %s", $column, $info[1], $info[2]));
    }
}

function db_fetch_bound($stmt) {
    if (!isset($stmt) || db_failed())
        return false;
    $status = $stmt->fetch(PDO::FETCH_BOUND);
    if ($status === false) {
        // fetch failure cannot be distinguished from no-more-results
        return false;
    }
    return true;
}

function db_row_count($stmt) {
    if (!isset($stmt) || db_failed())
        return null;
    $num = $stmt->rowCount();
    if (!is_int($num) || $num < 0)
        return 0;
    return $num;
}

// TODO what about PDOStatement::closeCursor ?
function db_close(&$stmt) {
    if (!is_null($stmt)) {
        $stmt = null;
    }
}

?>
