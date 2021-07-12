<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\Environment;

use Exception;
use RC\Tests\Infrastructure\Filesystem\DirPath\Tmp;

class Reset
{
    public function run(): void
    {
        $this->removeTmpContents();
    }

    private function removeTmpContents()
    {
        $this->removeDir((new Tmp())->value()->pure()->raw());
    }

    private function removeDir(string $dirPath)
    {
        $dirContents = glob(sprintf('%s/*', $dirPath));
        if ($dirContents === false) {
            throw new Exception(sprintf('Can not read %s contents', $dirPath));
        }

        array_map(
            function (string $fullPath) {
                if (is_dir($fullPath)) {
                    $this->removeDir($fullPath);
                    rmdir($fullPath);
                } else {
                    unlink($fullPath);
                }
            },
            $dirContents
        );
    }
}