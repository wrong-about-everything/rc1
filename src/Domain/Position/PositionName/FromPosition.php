<?php

declare(strict_types=1);

namespace RC\Domain\Position\PositionName;

use RC\Domain\Position\PositionId\Pure\CEO;
use RC\Domain\Position\PositionId\Pure\Marketer;
use RC\Domain\Position\PositionId\Pure\ProductAnalyst;
use RC\Domain\Position\PositionId\Pure\ProjectManager;
use RC\Domain\Position\PositionId\Pure\SystemOrBusinessAnalyst;
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
            (new SystemOrBusinessAnalyst())->value() => new SystemOrBusinessAnalystName(),
            (new CEO())->value() => new CEOName(),
            (new ProjectManager())->value() => new ProjectManagerName(),
            (new ProductAnalyst())->value() => new ProductAnalystName(),
            (new Marketer())->value() => new MarketerName(),
        ][$position->value()] ?? new NonExistent();
    }
}