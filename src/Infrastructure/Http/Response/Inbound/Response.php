<?php

declare(strict_types=1);

namespace RC\Infrastructure\Http\Response\Inbound;

use RC\Infrastructure\Http\Response\Code;
use RC\Infrastructure\Http\Response\Header;

interface Response
{
    public function code(): Code;

    /**
     * @return Header[]
     */
    public function headers(): array;

    public function body(): string;
}
