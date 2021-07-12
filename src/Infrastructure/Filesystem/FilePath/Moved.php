<?php

declare(strict_types=1);

namespace RC\Infrastructure\Filesystem\FilePath;

use RC\Infrastructure\Filesystem\FilePath;
use RC\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Failed;

class Moved extends FilePath
{
    private $origin;
    private $destination;
    private $cached;

    public function __construct(FilePath $origin, FilePath $destination)
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
                        sprintf('Can not move file %s because it does not exist', $this->origin->value()->pure()->raw()),
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
                        sprintf('Can not move file to %s because it already exists', $this->destination->value()->pure()->raw()),
                        []
                    )
                );
        }

        $r = rename($this->origin->value()->pure()->raw(), $this->destination->value()->pure()->raw());

        if ($r === false) {
            return
                new Failed(
                    new SilentDeclineWithDefaultUserMessage(
                        sprintf('Can not move file to %s', $this->destination->value()->pure()->raw()),
                        []
                    )
                );
        }

        return $this->destination->value();
    }
}