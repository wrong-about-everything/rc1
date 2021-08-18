<?php

declare(strict_types=1);

namespace RC\Domain\About\Pure;

class FromMatchingPairArray implements About
{
    private $matchingPairArray;
    private $concrete;

    public function __construct(array $matchingPairArray)
    {
        $this->matchingPairArray = $matchingPairArray;
        $this->concrete = null;
    }

    public function value(): string
    {
        return $this->concrete()->value();
    }

    public function empty(): bool
    {
        return $this->concrete()->empty();
    }

    public function exists(): bool
    {
        return $this->concrete()->exists();
    }

    private function concrete(): About
    {
        if (is_null($this->concrete)) {
            $this->concrete = $this->doConcrete();
        }

        return $this->concrete;
    }

    private function doConcrete(): About
    {
        if (is_null($this->matchingPairArray['about_match'])) {
            return new Emptie();
        }

        return new FromString($this->matchingPairArray['about_match']);
    }
}