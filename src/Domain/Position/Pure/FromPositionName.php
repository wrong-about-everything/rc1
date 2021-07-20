<?php

declare(strict_types=1);

namespace RC\Domain\Position\Pure;

use RC\Domain\PositionName\AnalystName;
use RC\Domain\PositionName\PositionName;
use RC\Domain\PositionName\ProductDesignerName;
use RC\Domain\PositionName\ProductManagerName;

class FromPositionName extends Position
{
    private $concrete;

    public function __construct(PositionName $positionName)
    {
        $this->concrete = isset($this->all()[$positionName->value()]) ? $this->all()[$positionName->value()] : new NonExistent();
    }

    public function value(): int
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }

    private function all()
    {
        return [
            (new ProductManagerName())->value() => new ProductManager(),
            (new ProductDesignerName())->value() => new ProductDesigner(),
            (new AnalystName())->value() => new Analyst(),
        ];
    }
}