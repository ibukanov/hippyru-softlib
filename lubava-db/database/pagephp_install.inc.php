<?php
if (!defined ("INCLUDE_LEGAL")) die ("File execution is prohibited.");

//
// Install the script.
// This creates the tables in database.
//
    $bSuccess = TRUE;

    if ($db = mysql_connect ($mysql_host, $mysql_user, $mysql_password)) {
       mysql_set_charset("utf8"); 
       //mysql_query ("ALTER TABLE $mysql_database.$mysql_table ADD COLUMN (pageid int(4))");
       //mysql_query ("UPDATE $mysql_database.$mysql_table SET pageid=0 WHERE pageid IS NULL");
       //mysql_query ("UPDATE $mysql_database.$mysql_table SET contents=REPLACE(contents, 'txtbase/data', 'database/data/texts')");

        // mysql_query ("RENAME TABLE $mysql_database.texts TO $mysql_database.$mysql_table_texts")

        // Create data tables
        mysql_query ("CREATE TABLE IF NOT EXISTS $mysql_database.$mysql_table (id int(6) NOT NULL auto_increment, class varchar(128) NOT NULL, title varchar(128) NOT NULL, author varchar(128) NOT NULL, sender varchar(64) NOT NULL, year int(4) NOT NULL, uploaded int(4) NOT NULL, contents varchar(128) NOT NULL, pageid int(4) NOT NULL, PRIMARY KEY (id), UNIQUE id (id))")
            or die ("Error on: CREATE TABLE $mysql_table");

        // Create table for users
        mysql_query ("CREATE TABLE IF NOT EXISTS $mysql_database.$mysql_table_users (id int(6) NOT NULL auto_increment, name varchar(128) NOT NULL, nickname varchar(32) NOT NULL, passhash varchar(32) NOT NULL, access int(4) NOT NULL, registered int(4) NOT NULL, lastip varchar(16) NOT NULL, lastsession varchar(128) NOT NULL, PRIMARY KEY (id), UNIQUE id (id))")
            or die ("Error on: CREATE TABLE $mysql_table_users");

        // Fill initial user-data
        if ($bSuccess) {
            $time  = time ();
            $pass  = MD5 ("poetpoet");
            $query = "INSERT INTO $mysql_database.$mysql_table_users VALUES (NULL, 'Любава', 'lubava', '$pass', 3, $time, '', '')";

            mysql_query ($query) or die ("Error on INSERT [lubava]");

            $pass  = MD5 ("p3ac3");
            $query = "INSERT INTO $mysql_database.$mysql_table_users VALUES (NULL, 'Редактор', 'editor', '$pass', 1, $time, '', '')";

            mysql_query ($query) or die ("Error on INSERT [editor]");
        }

        mysql_close ($db);
    }

    if ($bSuccess) {
        echo "<p align='center' class='style2'>Вам повезло.<br>Скрипт успешно установлен.</p>";
    } else {
        echo "<p align='center' class='style2'>Произошла ошибка в процессе установки</p>";
    }

    echo $strBackUrl;
?>
