<?php

set_exception_handler(
    function (Throwable $throwable) {
        echo
            json_encode([
                'result' => [
                    'successful' => false,
                    'code' => 500
                ],
                'payload' => []
            ])
        ;
    }
);
