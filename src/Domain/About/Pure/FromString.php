<?php

declare(strict_types=1);

namespace RC\Domain\About\Pure;

class FromString implements About
{
    private $about;
    private $concrete;

    public function __construct(string $about)
    {
        $this->about = $about;
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
        if ($this->about === '') {
            return new Emptie();
        }

        return new NonEmpty($this->about);
    }
}