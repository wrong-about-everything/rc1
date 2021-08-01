<?php

declare(strict_types=1);

namespace RC\Domain\Matches\ReadModel\Impure;

use RC\Domain\Matches\PositionExperienceParticipantsInterestsMatrix\PositionsExperiencesParticipantsInterestsMatrix;
use RC\Domain\Matches\ReadModel\Pure\GeneratedMatchesForSegment;
use RC\Domain\Matches\ReadModel\Pure\WithExperiencesThatTouchEachOther;
use RC\Domain\Matches\ReadModel\Pure\WithMatchedDropoutsWithinTheSameSegment;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class GeneratedMatchesForAllParticipants implements Matches
{
    private $positionsExperiencesParticipantsInterestsMatrix;
    private $cached;

    public function __construct(PositionsExperiencesParticipantsInterestsMatrix $positionsExperiencesParticipantsInterestsMatrix)
    {
        $this->positionsExperiencesParticipantsInterestsMatrix = $positionsExperiencesParticipantsInterestsMatrix;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    private function doValue()
    {
        if (!$this->positionsExperiencesParticipantsInterestsMatrix->value()->isSuccessful()) {
            return $this->positionsExperiencesParticipantsInterestsMatrix->value();
        }

        $allMatches = [];
        $dropouts = [];
        foreach ($this->positionsExperiencesParticipantsInterestsMatrix->value()->pure()->raw() as $positionId => $positionSlice) {
            foreach ($positionSlice as $experienceId => $positionAndExperienceSlice) {
                $segmentMatches = (new WithMatchedDropoutsWithinTheSameSegment(new GeneratedMatchesForSegment($positionAndExperienceSlice)))->value();
                $allMatches = array_merge($allMatches, $segmentMatches['matches']);
                if (!empty($segmentMatches['dropouts'])) {
                    $dropouts[$positionId][$experienceId] = $segmentMatches['dropouts'][0];
                }
            }
        }
        if (!empty($dropouts)) {
            $matchesWithExperiencesThatTouchEachOther = (new WithExperiencesThatTouchEachOther($dropouts))->value();
            return
                new Successful(
                    new Present([
                        'matches' => array_merge($allMatches, $matchesWithExperiencesThatTouchEachOther['matches']),
                        'dropouts' => $matchesWithExperiencesThatTouchEachOther['dropouts'],
                    ])
                );
        }

        return new Successful(new Present(['matches' => $allMatches, 'dropouts' => []]));
    }
}