<?php

declare(strict_types=1);

namespace RC\Domain\Matches\ReadModel\Pure;

use RC\Domain\Experience\ExperienceId\Pure\FromInteger;
use RC\Domain\Experience\ExperienceId\Pure\OneStepHigher;

/**
 * I try to match participants with the highest experience first.
 */
class WithExperiencesThatTouchEachOther implements Matches
{
    private $positionsExperiencesParticipantsMatrix;
    private $cached;

    public function __construct(array $positionsExperiencesParticipantMatrix)
    {
        $this->positionsExperiencesParticipantsMatrix = $positionsExperiencesParticipantMatrix;
        $this->cached = null;
    }

    public function value(): array
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    private function doValue()
    {
        $matches = [];
        $dropouts = [];
        foreach ($this->positionsExperiencesParticipantsMatrix as $positionSlice) {
            $descSortedExperiences = $this->descSortedExperiences($positionSlice);
            $positionMatches = [];
            $positionDropouts = [];
            for ($i = 0; $i < count($descSortedExperiences); $i++) {
                $currentExperience = new FromInteger(array_keys($descSortedExperiences)[$i]);
                if (!isset(array_keys($descSortedExperiences)[$i + 1])) {
                    $positionDropouts[] = $descSortedExperiences[$currentExperience->value()];
                    continue;
                }
                $nextIterationExperience = new FromInteger(array_keys($descSortedExperiences)[$i + 1]);
                if ($currentExperience->equals(new OneStepHigher($nextIterationExperience))) {
                    $i++;
                    $positionMatches[] = [$descSortedExperiences[$currentExperience->value()], $descSortedExperiences[$nextIterationExperience->value()]];
                } else {
                    $positionDropouts[] = $descSortedExperiences[$currentExperience->value()];
                }
            }
            $matches = array_merge($matches, $positionMatches);
            $dropouts = array_merge($dropouts, $positionDropouts);
        }

        return [
            'matches' => $matches,
            'dropouts' => $dropouts,
        ];
    }

    private function descSortedExperiences(array $positionSlice)
    {
        uksort(
            $positionSlice,
            function (int $leftExperience, int $rightExperience) {
                if ((new FromInteger($leftExperience))->equals(new FromInteger($rightExperience))) {
                    return 0;
                }

                return
                    (new FromInteger($leftExperience))->greater(new FromInteger($rightExperience))
                        ? -1
                        : 1;
            }
        );

        return $positionSlice;
    }
}