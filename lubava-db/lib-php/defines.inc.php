<?php
/** * contains various definitions */

$static_path = "/db-static";

$mysql_table        = "texts";
$mysql_table_users  = "txtusers";         

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
