<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem\DirPath;

use RC\Infrastructure\Filesystem\DirPath;
use RC\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Failed;

class Copied extends DirPath
{
    private $origin;
    private $destination;
    private $cached;

    public function __construct(DirPath $origin, DirPath $destination)
    {
        $this->origin = $origin;
        $this->destination = $destination;
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
        return $this->destination->exists();
    }

    private function doValue(): ImpureValue
    {
        if (!$this->origin->value()->isSuccessful()) {
            return $this->origin->value();
        }
        if (!$this->origin->exists()) {
            return
                new Failed(
                    new SilentDeclineWithDefaultUserMessage(
                        sprintf('Can not copy dir %s because it does not exist', $this->origin->value()->pure()->raw()),
                        []
                    )
                );
        }
        if (!$this->destination->value()->isSuccessful()) {
            return $this->destination->value();
        }
        if ($this->destination->exists()) {
            return
                new Failed(
                    new SilentDeclineWithDefaultUserMessage(
                        sprintf('Can not copy dir to %s because it already exists', $this->destination->value()->pure()->raw()),
                        []
                    )
                );
        }

        // @todo: implement!

        if ($r === false) {
            return
                new Failed(
                    new SilentDeclineWithDefaultUserMessage(
                        sprintf('Can not copy dir to %s', $this->destination->value()->pure()->raw()),
                        []
                    )
                );
        }

        return $this->destination->value();
    }
}