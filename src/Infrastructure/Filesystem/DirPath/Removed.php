<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem\DirPath;

use RC\Infrastructure\Filesystem\DirPath;
use RC\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Combined;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;

class Removed extends DirPath
{
    private $dirPath;
    private $cached;

    public function __construct(DirPath $dirPath)
    {
        $this->dirPath = $dirPath;
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
        $this->value();
        return $this->dirPath->exists();
    }

    private function doValue(): ImpureValue
    {
        if (!$this->dirPath->value()->isSuccessful()) {
            return $this->dirPath->value();
        }
        if (!$this->dirPath->exists()) {
            return
                new Failed(
                    new SilentDeclineWithDefaultUserMessage(
                        sprintf('Can not remove dir %s because it does not exist', $this->dirPath->value()->pure()->raw()),
                        []
                    )
                );
        }

        $r = $this->removeDir($this->dirPath->value()->pure()->raw());

        if (!$r->isSuccessful()) {
            return $r;
        }

        return new Successful(new Emptie());
    }

    private function removeDir(string $dirPath): ImpureValue
    {
        $dirContents = glob(sprintf('%s/*', $dirPath));
        if ($dirContents === false) {
            return new Failed(new SilentDeclineWithDefaultUserMessage(sprintf('Can not read %s contents', $dirPath), []));
        }

        $combinedResult =
            array_reduce(
                array_map(
                    function (string $fullPath) {
                        if (is_dir($fullPath)) {
                            return $this->removeDir($fullPath);
                        } else {
                            return
                                unlink($fullPath) === false
                                    ? new Failed(new SilentDeclineWithDefaultUserMessage(sprintf('Can not remove %s', $fullPath), []))
                                    : new Successful(new Emptie());
                        }
                    },
                    $dirContents
                ),
                function (ImpureValue $carry, ImpureValue $current) {
                    return new Combined($carry, $current);
                },
                new Successful(new Emptie())
            );
        if (rmdir($dirPath) === false) {
            return
                new Combined(
                    $combinedResult,
                    new Failed(
                        new SilentDeclineWithDefaultUserMessage(sprintf('Can not remove %s', $dirPath), [])
                    )
                );
        }

        return $combinedResult;
    }
}