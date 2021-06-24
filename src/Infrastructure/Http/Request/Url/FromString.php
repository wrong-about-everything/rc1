<?php

declare(strict_types = 1);

namespace RC\Infrastructure\Http\Request\Url;

use \Exception;
use RC\Infrastructure\Http\Request\Url;

/**
 * https://mathiasbynens.be/demo/url-regex
 */
class FromString extends Url
{
    private $url;

    public function __construct(string $uri)
    {
        if (!preg_match('@^(https?|ftp)://[^\s/$.?#].[^\s]*$@iS', $uri)) {
            throw new Exception(sprintf('Url %s is invalid', $uri));
        }

        $this->url = $uri;
    }

    public function value(): string
    {
        return $this->url;
    }
}
