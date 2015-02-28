<?php

//
// Display the list of
// uploaded files.
//

$sort_first='Тексты';

// Sort first by frequency of the class column but make $sort_first always
// come first. Then sort by year and title.

$stmt = db_prepare(
    "SELECT a.class, a.id, a.sender, a.author, a.year, a.title " .
    "FROM texts a JOIN " .
    "(SELECT class, count(*) AS freq FROM texts WHERE pageid=? GROUP BY class) b " .
    "ON a.class = b.class " .
    "WHERE a.pageid=? " .
    "ORDER BY a.class <> ?, b.freq desc, a.year desc, a.title");

db_bind_param3($stmt, "iis", $pageid, $pageid, $sort_first);
db_execute($stmt);
$result = db_get_result($stmt);
$rows = db_fetch_all($result);
db_free($result);
db_close($stmt);

$num = count($rows);

if ($num == 0) {
    echo "<p align='center' class='style2'>Ни одного файла не загружено</p>$strBackUrl";
} else {
    
    // Output
    $class = "";
    $need_author = false;
    
    echo <<<EOT
$strBackUrl
EOT;

    foreach ($rows as $row) {
        $r_class  = $row[0];
        $r_id     = $row[1];
        $r_sender = $row[2];
        $r_author = $row[3];
        $r_year   = $row[4];
        $r_title  = $row[5];
            
        // Split classes
        if ($r_class != $class) {
            if ($class != "") echo "</table>\n";
            echo "<p align='center' class='style2'>$r_class</p><table border='1' align='center' width='50%'>";
            $class = $r_class;
            
            $need_author = strpos ($class, "Чужие") !== false;
        }
        
        echo "<tr><td align='center' width='10%'><font class='style2'>$r_year</font></td>";
            
        if ($need_author) 
            echo "<td align=center><font class='style2'>&nbsp;<nobr>$r_author</nobr>&nbsp;</font></td>";
            
        echo "<td align='center'><font class='style2'>";
        echo "<a href='$url_me?mode=showtext&idx=$r_id&pageid=$pageid' class='noneline'>$r_title</a>";
        echo "</font></td>";
        
        // If you are the one who uploaded that or you're
        // the superuser, you can edit/delete it.
        if ($r_sender == $strUserName || isSuperuser ()) {
            echo <<<EOT
<td class="aircell">
<a href="$url_me?mode=delete&idx=$r_id&pageid=$pageid" class="noneline">
        <img width="16" height="16" border="0" src="$static_path/png/16x16.remove.png"/>
</a>
<a href="$url_me?mode=edit&idx=$r_id&pageid=$pageid" class="noneline">
        <img width="16" height="16" border="0" src="$static_path/png/16x16.edit.png"/>
</a>
</td>
EOT;
        }
        echo "</tr>";
    }
}

?>
