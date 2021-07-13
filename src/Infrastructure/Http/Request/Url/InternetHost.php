<?php

declare(strict_types = 1);

namespace RC\Infrastructure\Http\Request\Url;

interface InternetHost
{
    /**
    // Taken shamelessly from here: https://stackoverflow.com/a/106223/618020
    if (
        preg_match('@^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$@', $value) === 0
            ||
        preg_match('@^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$@', $value) === 0
    ) {
        throw new Exception('Host is invalid');
    }
     */
    public function value(): string;
}
