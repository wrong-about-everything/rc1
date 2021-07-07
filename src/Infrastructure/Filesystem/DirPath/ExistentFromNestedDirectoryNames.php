<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem\DirPath;

use Exception;
use RC\Infrastructure\Filesystem\DirPath;
use RC\Infrastructure\Filesystem\Filename;

class ExistentFromNestedDirectoryNames extends DirPath
{
    private $path;

    public function __construct(DirPath $path, Filename ... $filenames)
    {
        $canonicalized =
            realpath(
                array_reduce(
                    array_map(
                        function (Filename $filename) {
                            return $filename->value();
                        },
                        $filenames
                    ),
                    function (string $path, string $currentDirName) {
                        return $path . '/' . $currentDirName;
                    },
                    $path->value()
                )
            );
        if ($canonicalized === false) {
            throw new Exception(sprintf('%s does not exist', $path));
        }
        if (!is_dir($canonicalized)) {
            throw new Exception(sprintf('%s is not a directory', $path));
        }

        $this->path = $canonicalized;
    }

    public function value(): string
    {
        return $this->path;
    }

    public function exists(): bool
    {
        return true;
    }
}