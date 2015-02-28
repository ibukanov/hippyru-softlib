<?php

//
// Show specific text
//
$bSuccess = FALSE;

$r_id = (int) filter_input (INPUT_GET, "idx", FILTER_VALIDATE_INT);

if ($r_id) {
    $stmt = db_prepare("SELECT author, year, title, sender, uploaded FROM $mysql_table WHERE id = ?");
    db_bind_param($stmt, "i", $r_id);
    db_execute($stmt);
    $result = db_get_result($stmt);
    $row = db_fetch_row($result);
    db_free($result);
    db_close($stmt);
    

    if ($row) {
        $bSuccess = TRUE;

        $CurYear = date ("Y", time ());

        $r_author = $row->author;
        $r_year = $row->year;
        $r_title = $row->title;
        $r_sender = $row->sender;
        $r_stamp = date("d M Y", $row->uploaded);

        $r_contents = file_get_contents(get_data_file_path($r_id));
        if ($r_contents === false) {
            $r_contents = $ERR_not_found;
        }

        // Create year-span
        if ($CurYear != $r_year) {
            $r_year = "$r_year-$CurYear";
        }

        echo "<p class='style2'><b>$r_author</b></p>";
        echo "<p class='style2'><b><i>&nbsp;&nbsp;&nbsp;&nbsp;$r_title</i></b></p>";
        echo "<div class='style2_pad'>$r_contents</div><br>";
        // echo "<p class='style2'><font size='-1'>(c) $r_author $r_year</font></p>";
        // echo "<p class='style2'><font size='-3'>Uploaded by $r_sender at $r_stamp</font></p>";
    }
}

if (!$bSuccess) {
    echo "<p align='center' class='style2'><b>Запрашиваемый вами файл не найден</b></p><br/>";
}

echo $strBackUrl_1;
?>
