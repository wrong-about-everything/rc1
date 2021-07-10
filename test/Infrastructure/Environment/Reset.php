<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\Environment;

use Exception;

class Reset
{
    public function run(): void
    {
        $this->removeTmpContents();
    }

    private function removeTmpContents()
    {
        $tmpContents = glob( '/tmp/*');
        if ($tmpContents === false) {
            throw new Exception('Can not read tmp contents');
        }

        // @todo: recursive remove
        array_map(
            function (string $fullPath) {
                if (is_dir($fullPath)) {
                    rmdir($fullPath);
                } else {
                    unlink($fullPath);
                }
            },
            $tmpContents
        );
    }
}