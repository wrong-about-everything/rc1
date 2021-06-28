<?php

declare(strict_types = 1);

namespace RC\Infrastructure\Http\Request\Url;

use RC\Infrastructure\Http\Request\Url;

class Composite extends Url
{
    private $scheme;
    private $host;
    private $port;
    private $path;
    private $query;
    private $fragment;

    public function __construct(Scheme $scheme, Host $host, Port $port, Path $path, Query $query, Fragment $fragment)
    {
        $this->scheme = $scheme;
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->query = $query;
        $this->fragment = $fragment;
    }

    public function value(): string
    {
        return
            ($this->scheme->isSpecified() ? $this->scheme->value() : '')
                .
            $this->host->value()
                .
            ($this->port->isSpecified() ? $this->port->value() : '')
                .
            ($this->path->isSpecified() ? $this->path->value() : '')
                .
            ($this->query->isSpecified() ? ('?' . $this->query->value()) : '')
                .
            ($this->fragment->isSpecified() ? ('#' . $this->fragment->value()) : '')
        ;
    }
}
