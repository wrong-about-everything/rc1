<?php

declare(strict_types=1);

namespace RC\Infrastructure\Http\Request\Inbound;

use RC\Infrastructure\Http\Request\Method;
use RC\Infrastructure\Http\Request\Url;
use RC\Infrastructure\Http\Request\Url\Composite as CompositeUrl;
use RC\Infrastructure\Http\Request\Url\Fragment\NonSpecified as NonSpecifiedFragment;
use RC\Infrastructure\Http\Request\Url\Host\Localhost;
use RC\Infrastructure\Http\Request\Url\Path\FromString as PathFromString;
use RC\Infrastructure\Http\Request\Url\Port\NonSpecified as NonSpecifiedPort;
use RC\Infrastructure\Http\Request\Url\Query\FromUrl;
use RC\Infrastructure\Http\Request\Url\Scheme\Https;

class WithPathTakenFromQueryParam implements Request
{
    private $adHocPathQueryParamName;
    private $original;

    public function __construct(string $adHocPathQueryParamName, Request $original)
    {
        $this->adHocPathQueryParamName = $adHocPathQueryParamName;
        $this->original = $original;
    }

    public function method(): Method
    {
        return $this->original->method();
    }

    public function url(): Url
    {
        $query = new FromUrl($this->original->url());
        if ($query->isSpecified()) {
            parse_str($query->value(), $parsedQuery);
        } else {
            $parsedQuery = [];
        }

        return
            new CompositeUrl(
                new Https(),
                new Localhost(),
                new NonSpecifiedPort(),
                new PathFromString(
                    $parsedQuery[$this->adHocPathQueryParamName] ?? ''
                ),
                $query,
                new NonSpecifiedFragment()
            );
    }

    public function headers(): array/*Map<String, String>*/
    {
        return $this->original->headers();
    }

    public function body(): string
    {
        return $this->original->body();
    }
}