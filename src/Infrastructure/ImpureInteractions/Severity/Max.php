<?php

declare(strict_types=1);

namespace RC\Infrastructure\ImpureInteractions\Severity;

use RC\Infrastructure\ImpureInteractions\Severity;

class Max extends Severity
{
    private $severities;

    public function __construct(Severity ... $severities)
    {
        $this->severities = $severities;
    }

    public function value(): int
    {
        return
            max(
                array_map(
                    function (Severity $severity) {
                        return $severity->value();
                    },
                    $this->severities
                )
            );
    }
}