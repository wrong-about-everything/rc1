<?php

declare(strict_types=1);

namespace RC\Infrastructure\Http\Request\Inbound;

use RC\Infrastructure\Http\Request\Method;
use RC\Infrastructure\Http\Request\Method\FromString as HttpMethodFromString;
use RC\Infrastructure\Http\Request\Url;
use RC\Infrastructure\Http\Request\Url\FromString as UrlFromString;

class DefaultInbound implements Request
{
    public function method(): Method
    {
        return new HttpMethodFromString($_SERVER['REQUEST_METHOD']);
    }

    public function url(): Url
    {
        return
            new UrlFromString(
                sprintf(
                    '%s://%s%s',
                    isset($_SERVER['HTTPS']) ? 'https' : 'http',
                    $_SERVER['HTTP_HOST'],
                    $_SERVER['REQUEST_URI']
                )
            );
    }

    public function headers(): array
    {
        return getallheaders();
    }

    public function body(): string
    {
        return file_get_contents('php://input');
    }

}