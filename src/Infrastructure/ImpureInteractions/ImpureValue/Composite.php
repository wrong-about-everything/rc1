<?php

declare(strict_types=1);

namespace RC\Infrastructure\ImpureInteractions\ImpureValue;

use Exception;
use RC\Infrastructure\ImpureInteractions\Error;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\PureValue;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class Composite implements ImpureValue
{
    private $left;
    private $right;

    public function __construct(ImpureValue $left, ImpureValue $right)
    {
        $this->left = $left;
        $this->right = $right;
    }

    public function isSuccessful(): bool
    {
        return $this->left->isSuccessful() && $this->right->isSuccessful();
    }

    public function value(): PureValue
    {
        if (!$this->isSuccessful()) {
            throw new Exception('At least one of impure values is not successful, so you can not have their combined value');
        }

        return
            new Present(
                array_merge(
                    (
                        $this->left->value()->isPresent()
                            ? (is_scalar($this->left->value()->raw()) ? [$this->left->value()->raw()] : $this->left->value()->raw())
                            : []
                    ),
                    (
                        $this->right->value()->isPresent()
                            ? (is_scalar($this->right->value()->raw()) ? [$this->right->value()->raw()] : $this->right->value()->raw())
                            : []
                    )
                )
            );
    }

    public function error(): Error
    {
        if ($this->isSuccessful()) {
            throw new Exception('Successful impure value does not have an error.');
        }

        return
            !$this->left->isSuccessful()
                ? $this->left->error()
                : $this->right->error();
    }
}