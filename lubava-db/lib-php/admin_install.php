<?php
require "defines.inc.php";
require "utils.php";

//db_query ("ALTER TABLE $mysql_table ADD COLUMN (pageid int(4))");
//db_query ("UPDATE $mysql_database.$mysql_table SET pageid=0 WHERE pageid IS NULL");
//mysql_query ("UPDATE $mysql_database.$mysql_table SET contents=REPLACE(contents, 'txtbase/data', 'database/data/texts')");

// Create data tables
db_query("CREATE TABLE IF NOT EXISTS $mysql_table (id int(6) NOT NULL auto_increment, class varchar(128) NOT NULL, title varchar(128) NOT NULL, author varchar(128) NOT NULL, sender varchar(64) NOT NULL, year int(4) NOT NULL, uploaded int(4) NOT NULL, content longblob NOT NULL, pageid int(4) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARSET=utf8");

// Create table for users
db_query("CREATE TABLE IF NOT EXISTS $mysql_table_users (id int(6) NOT NULL auto_increment, name varchar(128) NOT NULL, nickname varchar(32) NOT NULL, passhash varchar(128) NOT NULL, access SMALLINT NOT NULL, registered BIGINT NOT NULL, cookiesalt varbinary(32) NOT NULL, PRIMARY KEY (id), UNIQUE (nickname))  DEFAULT CHARSET=utf8");

// Fill initial user-data
db_query("INSERT INTO $mysql_table_users VALUES (NULL, 'Любава', 'lubava', '', 3, 1172778442, X'00')");

?>
