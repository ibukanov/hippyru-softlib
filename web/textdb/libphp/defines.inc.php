<?php
/** * contains various definitions */

!defined('DEFS_DB_TABLE_TEXTS') && define('DEFS_DB_TABLE_TEXTS', 'texts');
!defined('DEFS_DB_TABLE_USERS') && define('DEFS_DB_TABLE_USERS', 'txtusers');

!defined('DEFS_LOGIN_DURATION') && define('DEFS_LOGIN_DURATION', 12 * 3600);

// text kinds
define('TEXT_PROSE', 0);
define('TEXT_POETRY', 1);
define('TEXT_KIND_END', 2);

$g_text_kind_paths = array(
    'prose',
    'poetry'
);

$g_PageTitles = array(
    "Тексты",
    "Стихи"
);

// Родительный падеж, ед. число
$g_DocName_0 = array(
    "текст",
    "стих"
);

// Родительный падеж, мн. число
$g_DocName_1 = array(
    "текстов",
    "стихов"
);


define('PAGE_DB_ERR',                   -1);
define('PAGE_RECORD_NOT_FOUND',         -2);
define('PAGE_NO_WRITE_ACCESS',          -3);
define('PAGE_BAD_POST_KEY',             -4);
define('PAGE_BAD_INTERNAL_VALUE',       -5);
define('PAGE_BAD_LOGIN',                -6);
define('PAGE_UNKNOWN_REQUEST',          -7);
define('PAGE_MISSING_OR_INVALID_PARAM', -8);
