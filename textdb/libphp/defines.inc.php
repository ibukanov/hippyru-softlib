<?php
/** * contains various definitions */

$static_path = "/textdb";

define('DB_TABLE_TEXTS', 'texts');
define('DB_TABLE_USERS', 'txtusers');

$url_me = $_SERVER['SCRIPT_NAME'];    

// Заголовки страниц
$g_PageTitles = array(
    "Тексты",
    "Стихи",
    "Песни");

// Родю падеж, ед. число
$g_DocName_0 = array(
    "текст",
    "стих",
    "песню");

// Родю падеж, мн. число
$g_DocName_1 = array(
    "текстов",
    "стихов",
    "песен");


define('PAGE_DB_ERR',             -1);
define('PAGE_RECORD_NOT_FOUND',   -2);
define('PAGE_NO_WRITE_ACCESS',    -3);
define('PAGE_BAD_POST_KEY',       -4);
define('PAGE_BAD_INTERNAL_VALUE', -5);
define('PAGE_BAD_LOGIN',          -6);
