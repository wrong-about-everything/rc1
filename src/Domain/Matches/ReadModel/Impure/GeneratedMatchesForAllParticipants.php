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
    private $participants2PastPairs;
    private $cached;

    public function __construct(PositionsExperiencesParticipantsInterestsMatrix $positionsExperiencesParticipantsInterestsMatrix, array $participants2PastPairs)
    {
        $this->positionsExperiencesParticipantsInterestsMatrix = $positionsExperiencesParticipantsInterestsMatrix;
        $this->participants2PastPairs = $participants2PastPairs;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    private function doValue(): ImpureValue
    {
        if (!$this->positionsExperiencesParticipantsInterestsMatrix->value()->isSuccessful()) {
            return $this->positionsExperiencesParticipantsInterestsMatrix->value();
        }
        if (!$this->positionsExperiencesParticipantsInterestsMatrix->value()->pure()->isPresent()) {
            return $this->positionsExperiencesParticipantsInterestsMatrix->value();
        }

        $allMatches = [];
        $dropouts = [];
        foreach ($this->positionsExperiencesParticipantsInterestsMatrix->value()->pure()->raw() as $positionId => $positionSlice) {
            foreach ($positionSlice as $experienceId => $positionAndExperienceSlice) {
                $segmentMatches = $this->segmentMatches($positionAndExperienceSlice);
                $allMatches = array_merge($allMatches, $segmentMatches['matches']);
                if (!empty($segmentMatches['dropouts'])) {
                    $dropouts[$positionId][$experienceId] = $segmentMatches['dropouts'];
                }
            }
        }
        if (!empty($dropouts)) {
            $matchesWithExperiencesThatTouchEachOther = (new WithExperiencesThatTouchEachOther($dropouts, $this->participants2PastPairs))->value();
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

    private function segmentMatches(array $positionAndExperienceSlice)
    {
        return
            (new WithMatchedDropoutsWithinTheSameSegment(
                new GeneratedMatchesForSegment($positionAndExperienceSlice, $this->participants2PastPairs),
                $this->participants2PastPairs
            ))
                ->value();
    }
}