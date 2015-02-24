<?php
if (!defined ("INCLUDE_LEGAL")) die ("File execution is prohibited.");

    if (!function_exists ("filter_input")) {
        define("INPUT_POST", 0);
        define("INPUT_GET",  1);
    
        function filter_input ($type, $name, $filter) {
            switch ($type) {
                case 0:
                    return $_POST[$name];
                case 1:
                    return $_GET[$name];
            }
        }
    }
?>