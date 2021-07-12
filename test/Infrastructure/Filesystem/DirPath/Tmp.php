<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\Filesystem\DirPath;

use Exception;
use RC\Infrastructure\Filesystem\DirPath;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class Tmp extends DirPath
{
    public function value(): ImpureValue
    {
        if (!file_exists('/tmp/project_custom_temp_dir')) {
            if (mkdir('/tmp/project_custom_temp_dir') === false) {
                throw new Exception('Can not create /tmp/project_custom_temp_dir');
            }
        }

        return new Successful(new Present('/tmp/project_custom_temp_dir'));
    }

    public function exists(): bool
    {
        return file_exists('/tmp/project_custom_temp_dir');
    }
}