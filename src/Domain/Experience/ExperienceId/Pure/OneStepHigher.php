<?php

declare(strict_types=1);

namespace RC\Domain\Experience\ExperienceId\Pure;

class OneStepHigher extends Experience
{
    private $experience;

    public function __construct(Experience $experience)
    {
        $this->experience = new FromInteger($experience->value() + 1);
    }

    public function value(): int
    {
        return $this->experience->value();
    }

    public function exists(): bool
    {
        return $this->experience->exists();
    }
}