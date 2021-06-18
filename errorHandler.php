<?php

set_error_handler(
    function ($errno, $errstr, $errfile, $errline, array $errcontex) {
        throw new Exception($errstr, 0);
    },
    E_ALL
);
