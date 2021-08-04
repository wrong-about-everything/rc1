<?php

declare(strict_types=1);

namespace RC\Domain\Position\PositionId\Pure;

use RC\Domain\Position\PositionName\CEOName;
use RC\Domain\Position\PositionName\MarketerName;
use RC\Domain\Position\PositionName\ProductAnalystName;
use RC\Domain\Position\PositionName\ProjectManagerName;
use RC\Domain\Position\PositionName\SystemOrBusinessAnalystName;
use RC\Domain\Position\PositionName\PositionName;
use RC\Domain\Position\PositionName\ProductDesignerName;
use RC\Domain\Position\PositionName\ProductManagerName;

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
            (new SystemOrBusinessAnalystName())->value() => new SystemOrBusinessAnalyst(),
            (new CEOName())->value() => new CEO(),
            (new ProjectManagerName())->value() => new ProjectManager(),
            (new ProductAnalystName())->value() => new ProductAnalyst(),
            (new MarketerName())->value() => new Marketer(),
        ];
    }
}