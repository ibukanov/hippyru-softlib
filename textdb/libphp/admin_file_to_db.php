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
db_bind_result($stmt, $r_id);
$ids = array();
while (db_fetch($stmt)) {
    array_push($ids, $r_id);
}
db_close($stmt);
if (!db_ok())
    die();

$stmt = db_prepare("SELECT count(id) FROM %s", DEFS_DB_TABLE_TEXTS);
db_execute($stmt);
db_bind_result($stmt, $db_count);
db_fetch($stmt);
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
    db_bind_param2($stmt, "si", $texts[$i], $ids[$i]);
    db_execute($stmt);
    $affected = db_affected_rows($stmt);
    db_close($stmt);
}


$bad = false;
for ($i = 0; $i < $n; $i += 1) {
    $stmt = db_prepare("SELECT content FROM %s WHERE id=?", DEFS_DB_TABLE_TEXTS);
    db_bind_param($stmt, "i", $ids[$i]);
    db_execute($stmt);
    db_store_result($stmt);
    $text = null;
    db_bind_result($stmt, $text);
    db_fetch($stmt);
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
