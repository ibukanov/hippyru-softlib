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

$ERR_not_found = "<font color='red'><strong>Файл не найден. Этого не должно происходить.<br>Обратитесь, пожалуйста, к <a href='mailto:lubava@hippy.ru'>администратору</a> сайта</strong></font>";?>
