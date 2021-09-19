<?php

declare(strict_types=1);

namespace RC\Domain\Matches\ReadModel\Pure;

class GeneratedMatchesWithinSingleInterest implements Matches
{
    private $participants;
    private $participants2PastPairs;

    public function __construct(array $participants, array $participants2PastPairs)
    {
        $this->participants = $participants;
        $this->participants2PastPairs = $participants2PastPairs;
    }

    public function value(): array
    {
        return $this->matchesAndNonMatchedParticipantsIteration([], [], $this->participants);
    }

    private function matchesAndNonMatchedParticipantsIteration(array $matches, array $nonMatchedParticipants, array $participants)
    {
        if (empty($participants)) {
            return ['matches' => $matches, 'non_matched_participants' => $nonMatchedParticipants];
        }

        $currentParticipantId = array_values(array_slice($participants, 0, 1))[0];
        $allTheRest = array_slice($participants, 1, null, true);
        $pairToCurrentParticipantId = (new PairToCurrentParticipantId($allTheRest, $currentParticipantId, $this->participants2PastPairs))->value();

        if ($pairToCurrentParticipantId !== null) {
            unset($allTheRest[$pairToCurrentParticipantId]);
            return
                $this->matchesAndNonMatchedParticipantsIteration(
                    array_merge($matches, [[$currentParticipantId, $pairToCurrentParticipantId]]),
                    $nonMatchedParticipants,
                    $allTheRest
                );
        } else {
            return
                $this->matchesAndNonMatchedParticipantsIteration(
                    $matches,
                    array_merge($nonMatchedParticipants, [$currentParticipantId]),
                    $allTheRest
                );
        }
    }

}