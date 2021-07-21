<?php

declare(strict_types=1);

namespace RC\Domain\Position\PositionName;

use RC\Domain\Position\PositionId\Pure\Analyst;
use RC\Domain\Position\PositionId\Pure\Position;
use RC\Domain\Position\PositionId\Pure\ProductDesigner;
use RC\Domain\Position\PositionId\Pure\ProductManager;

class FromPosition extends PositionName
{
    private $positionName;

    public function __construct(Position $position)
    {
        $this->positionName = $this->concrete($position);
    }

    public function value(): string
    {
        return $this->positionName->value();
    }

    public function exists(): bool
    {
        return $this->positionName->exists();
    }

    private function concrete(Position $position): PositionName
    {
        return [
            (new ProductManager())->value() => new ProductManagerName(),
            (new ProductDesigner())->value() => new ProductDesignerName(),
            (new Analyst())->value() => new AnalystName(),
        ][$position->value()] ?? new NonExistent();
    }
}