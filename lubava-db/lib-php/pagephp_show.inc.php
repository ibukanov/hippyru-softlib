<?php

//
// Show specific text
//
$bSuccess = FALSE;

$r_id = (int) filter_input (INPUT_GET, "idx", FILTER_VALIDATE_INT);

if ($r_id) {
    $stmt = db_prepare("SELECT author, year, title, sender, uploaded, content FROM $mysql_table WHERE id = ?");
    db_bind_param($stmt, "i", $r_id);
    db_execute($stmt);
    db_store_result($stmt);
    db_bind_result6($stmt, $r_author, $r_year, $r_title, $r_sender, $r_uploaded, $r_content);
    db_fetch($stmt);
    db_close($stmt);
    
    if (isset($r_author)) {
        $bSuccess = TRUE;

        $CurYear = date ("Y", time ());
        $r_stamp = date("d M Y", $r_uploaded);

        // Create year-span
        if ($CurYear != $r_year) {
            $r_year = "$r_year-$CurYear";
        }

        echo "<p class='style2'><b>$r_author</b></p>";
        echo "<p class='style2'><b><i>&nbsp;&nbsp;&nbsp;&nbsp;$r_title</i></b></p>";
        echo "<div class='style2_pad'>$r_content</div><br>";
        // echo "<p class='style2'><font size='-1'>(c) $r_author $r_year</font></p>";
        // echo "<p class='style2'><font size='-3'>Uploaded by $r_sender at $r_stamp</font></p>";
    }
}

if (!$bSuccess) {
    echo "<p align='center' class='style2'><b>Запрашиваемый вами файл не найден</b></p><br/>";
}

echo $strBackUrl_1;
?>
