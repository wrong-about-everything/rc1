<?php

declare(strict_types=1);

namespace RC\Infrastructure\ExecutionEnvironmentAdapter;

class WebServer
{
    private $request;

    public function __construct(HttpRequest $request, User)
    {
        $this->request = $request;
    }

    public function response(): void
    {
        ;
    }
}