<?php

declare(strict_types = 1);

namespace RC\Infrastructure\Http\Request\Url\Host;

use \Exception;
use RC\Infrastructure\Http\Request\Url;
use RC\Infrastructure\Http\Request\Url\Host;

class FromUrl implements Host
{
    private $host;

    public function __construct(Url $uri)
    {
        $host = parse_url($uri->value(), PHP_URL_HOST);

        // @info: Is it possible for Url in current implementation to have empty host? Cover this in tests!
//        if ($host === false) {
//            throw new Exception('Url is incorrect. Fix Url constructor to fix this.');
//        }
//
//        if ($host === null) {
//            throw new Exception('Url is incorrect: host can not be empty. Fix Url constructor to fix this.');
//        }

        $this->host = $host;
    }

    public function value(): string
    {
        return $this->host;
    }
}