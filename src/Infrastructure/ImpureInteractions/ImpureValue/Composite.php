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

    public function pure(): PureValue
    {
        if (!$this->isSuccessful()) {
            throw new Exception('At least one of impure values is not successful, so you can not have their combined value');
        }

        return
            new Present(
                array_merge(
                    (
                        $this->left->pure()->isPresent()
                            ? (is_scalar($this->left->pure()->raw()) ? [$this->left->pure()->raw()] : $this->left->pure()->raw())
                            : []
                    ),
                    (
                        $this->right->pure()->isPresent()
                            ? (is_scalar($this->right->pure()->raw()) ? [$this->right->pure()->raw()] : $this->right->pure()->raw())
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