<?php

declare(strict_types=1);

namespace RC\Domain\Position\PositionName;

class FromString extends PositionName
{
    private $concrete;

    public function __construct(string $positionName)
    {
        $this->concrete = $this->concrete($positionName);
    }

    public function value(): string
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }

    private function concrete(string $positionName): PositionName
    {
        return [
            (new ProductManagerName())->value() => new ProductManagerName(),
            (new ProductDesignerName())->value() => new ProductDesignerName(),
            (new SystemOrBusinessAnalystName())->value() => new SystemOrBusinessAnalystName(),
            (new CEOName())->value() => new CEOName(),
            (new ProjectManagerName())->value() => new ProjectManagerName(),
            (new ProductAnalystName())->value() => new ProductAnalystName(),
            (new MarketerName())->value() => new MarketerName(),
        ][$positionName] ?? new NonExistent();
    }
}