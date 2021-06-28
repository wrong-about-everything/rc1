<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem;

interface FileContents
{
    public function value(): string;
}