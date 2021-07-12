<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem\DirPath;

use RC\Infrastructure\Filesystem\DirPath;
use RC\Infrastructure\Filesystem\Filename;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class FromNestedDirectoryNames extends DirPath
{
    private $path;
    private $filenames;
    private $cached;

    public function __construct(DirPath $path, Filename ... $filenames)
    {
        $this->path = $path;
        $this->filenames = $filenames;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    public function exists(): bool
    {
        return is_dir($this->value()->pure()->raw());
    }

    private function doValue(): ImpureValue
    {
        if (!$this->path->value()->isSuccessful()) {
            return $this->path->value();
        }

        return
            new Successful(
                new Present(
                    array_reduce(
                        array_map(
                            function (Filename $filename) {
                                return $filename->value();
                            },
                            $this->filenames
                        ),
                        function (string $path, string $currentDirName) {
                            return $path . '/' . $currentDirName;
                        },
                        $this->path->value()->pure()->raw()
                    )
                )
            );
    }
}