<?php
if (!defined ("INCLUDE_LEGAL")) die ("File execution is prohibited.");

/**
 * Determine request type
 * and init corresponding variables.
 */
 
//
// Determine the
// request type...
//
if (isset  ($_GET["mode"])) {
    $mode = filter_input (INPUT_GET, "mode", FILTER_SANITIZE_STRING);
} else if (isset  ($_POST["epost"])) {
    $mode = filter_input (INPUT_POST, "epost", FILTER_SANITIZE_STRING);
} else {
    $mode = "title";
}
?>