<?php

declare(strict_types=1);

namespace RC\Domain\Matches\ReadModel\Pure;

class PairToCurrentParticipantId
{
    private $participants;
    private $currentParticipantId;
    private $participants2PastPairs;

    public function __construct(array $participants, $currentParticipantId, array $participants2PastPairs)
    {
        $this->participants = $participants;
        $this->currentParticipantId = $currentParticipantId;
        $this->participants2PastPairs = $participants2PastPairs;
    }

    public function value()
    {
        foreach ($this->participants as $participantId) {
            if (!isset(($this->participants2PastPairs[$this->currentParticipantId] ?? [])[$participantId])) {
                return $participantId;
            }
        }

        return null;
    }
}