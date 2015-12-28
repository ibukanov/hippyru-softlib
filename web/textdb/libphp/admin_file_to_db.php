<?php
require "defines.inc.php";
require "utils.php";

//*
db_query("ALTER TABLE %s DROP contents", DEFS_DB_TABLE_TEXTS);
if (!db_ok())
    die();
//*/

//*
db_query("ALTER TABLE %s ADD (content longtext NOT NULL)", DEFS_DB_TABLE_TEXTS);
if (!db_ok())
    die();
//*/
$stmt = db_prepare("SELECT id FROM %s", DEFS_DB_TABLE_TEXTS);
db_execute($stmt);
db_bind_column($stmt, 1, $r_id, PDO::PARAM_INT);
$ids = array();
while (db_fetch_bound($stmt)) {
    array_push($ids, $r_id);
}
db_close($stmt);
if (!db_ok())
    die();

$stmt = db_prepare("SELECT count(id) FROM %s", DEFS_DB_TABLE_TEXTS);
db_execute($stmt);
db_bind_column($stmt, 1, $db_count, PDO::PARAM_INT);
db_fetch_bound($stmt);
db_close($stmt);
if (!db_ok())
    die();

$n = count($ids);
if ($n !== $db_count) {
    die(snprintf("Mismatch between fetched number of ids and db-reported: %d != %d",
                 $n, $db_count));
}

$texts = array();

for ($i = 0; $i < $n; $i += 1) {
    $id = $ids[$i];
    $path = sprintf('/www/site/lubava.info/html/database/data/texts/text-%d.htmlraw', $id);
    $text = file_get_contents($path);
    if ($text === false)
        die("Failed to read $path");
    if (!$text)
        die("File $path is empty");
    $texts[$i] = $text;
}

for ($i = 0; $i < $n; $i += 1) {
    $stmt = db_prepare("UPDATE %s SET content=? WHERE id=?", DEFS_DB_TABLE_TEXTS);
    db_bind_value($stmt, 1, $texts[$i], PDO::PARAM_LOB);
    db_bind_value($stmt, 2, $ids[$i], PDO::PARAM_INT);
    db_execute($stmt);
    db_close($stmt);
}


$bad = false;
for ($i = 0; $i < $n; $i += 1) {
    $stmt = db_prepare("SELECT content FROM %s WHERE id=?", DEFS_DB_TABLE_TEXTS);
    db_bind_value($stmt, 1, $ids[$i], PDO::PARAM_INT);
    db_execute($stmt);
    $text = null;
    db_bind_column($stmt, 1, $text, PDO::PARAM_LOB);
    db_fetch_bound($stmt);
    db_close($stmt);
    if ($text !== $texts[$i]) {
        log_err("Failed to compare db and file text for id=%s, flength=%s, dblength=%s",
                $ids[$i], strlen($texts[$i]), strlen($text));
        $bad = true;
    }

}

if ($bad)
    die();

?>
