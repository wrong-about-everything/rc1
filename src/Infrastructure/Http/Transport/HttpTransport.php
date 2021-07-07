<?php

declare(strict_types = 1);

namespace RC\Infrastructure\Http\Transport;

use RC\Infrastructure\Http\Request\Outbound\Request;
use RC\Infrastructure\Http\Response\Inbound\Response;

interface HttpTransport
{
    public function response(Request $request): Response;
}