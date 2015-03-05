<?php
require "defines.inc.php";
require "utils.php";

// Create data tables
db_query(
    "CREATE TABLE IF NOT EXISTS %s (" .
    "id int(6) NOT NULL auto_increment, " .
    "class varchar(128) NOT NULL, " .
    "title varchar(128) NOT NULL, " .
    "author varchar(128) NOT NULL, " .
    "sender varchar(64) NOT NULL, " .
    "year int(4) NOT NULL, " .
    "uploaded int(4) NOT NULL, " .
    "pageid int(4) NOT NULL, " .
    "content longblob NOT NULL, " .
    "PRIMARY KEY (id)) " .
    "DEFAULT CHARSET=utf8",
    DB_TABLE_TEXTS);

// Create table for users
db_query(
    "CREATE TABLE IF NOT EXISTS %s (" .
    "id int(6) NOT NULL auto_increment, " .
    "name varchar(128) NOT NULL, " .
    "nickname varchar(32) NOT NULL, " .
    "passhash varchar(128) NOT NULL, " .
    "access SMALLINT NOT NULL, " .
    "registered BIGINT NOT NULL, " .
    "cookiesalt varbinary(32) NOT NULL, " .
    "PRIMARY KEY (id), UNIQUE (nickname)) " .
    "DEFAULT CHARSET=utf8",
    DB_TABLE_USERS);

// Fill initial user-data
db_query(
    "INSERT INTO %s VALUES (NULL, 'Любава', 'lubava', '', 3, 1172778442, X'00')",
    DB_TABLE_USERS);

?>
