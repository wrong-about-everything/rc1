<?php

declare(strict_types=1);

namespace RC\Activities\Cron\SendsMatchesToParticipants;

class Matches
{
    private $participants2Interests;

    public function __construct(array $participants2Interests)
    {
        $this->participants2Interests = $participants2Interests;
    }

    public function value(): array
    {
        return $this->doValue($this->participants2Interests, [], []);
    }

    private function doValue(array $participants2Interests, array $dropouts, array $matches)
    {
        if (empty($participants2Interests)) {
            return ['dropouts' => $dropouts, 'matches' => $matches];
        }

        $participantsGroupedByInterest = [];
        foreach ($participants2Interests as $participantId => $interests) {
            foreach ($interests as $interestId) {
                $participantsGroupedByInterest[$interestId] =
                    isset($participantsGroupedByInterest[$interestId])
                        ? array_merge($participantsGroupedByInterest[$interestId], [$participantId])
                        : [$participantId];
            }
        }
        // sort interests by weight asc
        $participantsGroupedByInterest = $this->interestsSortedByWeight($participantsGroupedByInterest, $participants2Interests);

        //sort participants in each interest by interest qty asc
        $participantsGroupedByInterestAndSortedByInterestQty = [];
        foreach ($participantsGroupedByInterest as $interestId => $participants) {
            usort(
                $participants,
                function ($left, $right) use ($participants2Interests) {
                    if (count($participants2Interests[$left]) === count($participants2Interests[$right])) {
                        return 0;
                    }

                    return count($participants2Interests[$left]) < count($participants2Interests[$right]) ? -1 : 1;
                }
            );
            $participantsGroupedByInterestAndSortedByInterestQty[$interestId] = $participants;
        }

        $participantsWithTheLeastOftenEncounteredInterest = array_values($participantsGroupedByInterestAndSortedByInterestQty)[0];
        $theLeastOftenEncounteredInterest = array_keys($participantsGroupedByInterestAndSortedByInterestQty)[0];
        if (count($participantsWithTheLeastOftenEncounteredInterest) === 1) {
            $singleParticipantId = array_values($participantsWithTheLeastOftenEncounteredInterest)[0];
            if (count($participants2Interests[$singleParticipantId]) === 1) {
                // 1. he has no spare participants that share his interests
                // 2. and he has a single interest, so I can't find him anyone else.
                // So he is a dropout.
                unset($participants2Interests[$singleParticipantId]);
                return $this->doValue($participants2Interests, array_merge($dropouts, [$singleParticipantId]), $matches);
            } else {
                // 1. he has no spare participants that share his interests
                // 2. but he has more than one interests, so I can try to find him someone else.
                // So I remove that interest from the that participant interests, and move on.
                $participants2Interests[$singleParticipantId] =
                    array_filter(
                        $participants2Interests[$singleParticipantId],
                        function ($interestId) use ($theLeastOftenEncounteredInterest) {
                            return $interestId !== $theLeastOftenEncounteredInterest;
                        }
                    );
                return $this->doValue($participants2Interests, $dropouts, $matches);
            }
        }

        $newMatches =
            array_reduce(
                $participantsWithTheLeastOftenEncounteredInterest,
                function (array $carry, $participantId) {
                    if (empty($carry) || count($carry[count($carry) - 1]) === 2) {
                        $carry[] = [$participantId];
                        return $carry;
                    } else {
                        $carry[count($carry) - 1][] = $participantId;
                        return $carry;
                    }
                },
                []
            );
        foreach ($newMatches as $pair) {
            if (count($pair) === 2) {
                unset($participants2Interests[$pair[0]]);
                unset($participants2Interests[$pair[1]]);
            }
        }

        if (count($newMatches[count($newMatches) - 1]) === 1) {
            // COPY-PASTE!!
            $singleParticipantId = array_values($newMatches[count($newMatches) - 1])[0];
            if (count($participants2Interests[$singleParticipantId]) === 1) {
                unset($participants2Interests[$singleParticipantId]);
                return $this->doValue($participants2Interests, array_merge($dropouts, [$singleParticipantId]), array_merge($matches, array_slice($newMatches, 0, count($newMatches) - 1)));
            } else {
                $participants2Interests[$singleParticipantId] =
                    array_filter(
                        $participants2Interests[$singleParticipantId],
                        function ($interestId) use ($theLeastOftenEncounteredInterest) {
                            return $interestId !== $theLeastOftenEncounteredInterest;
                        }
                    );
                return $this->doValue($participants2Interests, $dropouts, array_merge($matches, array_slice($newMatches, 0, count($newMatches) - 1)));
            }
        } else {
            return $this->doValue($participants2Interests, $dropouts, array_values(array_merge($matches, $newMatches)));
        }
    }

    /**
     * This function is focused on interest intensity. Interests with the highest weight go first.
     * When some interest is marked as the single interest often, it must gain precedence above others,
     * because such interest are the hardest to find pairs for.
     */
    private function interestsSortedByWeight(array $participantsGroupedByInterest, array $participants2Interests)
    {
        uksort(
            $participantsGroupedByInterest,
            function ($leftInterest, $rightInterest) use ($participantsGroupedByInterest, $participants2Interests) {
                if ($this->interestWeight($leftInterest, $participantsGroupedByInterest, $participants2Interests) === $this->interestWeight($rightInterest, $participantsGroupedByInterest, $participants2Interests)) {
                    return 0;
                }

                return
                    $this->interestWeight($leftInterest, $participantsGroupedByInterest, $participants2Interests) < $this->interestWeight($rightInterest, $participantsGroupedByInterest, $participants2Interests)
                        ? 1
                        : -1
                    ;
            }
        );
        return $participantsGroupedByInterest;
    }

    private function interestWeight($interestId, array $participantsGroupedByInterest, $participants2Interests)
    {
        return
            array_reduce(
                $participantsGroupedByInterest[$interestId],
                function (int $carry, $participantId) use ($participantsGroupedByInterest, $participants2Interests) {
                    return $carry * ((count($participantsGroupedByInterest) - count($participants2Interests[$participantId])) + 1);
                },
                1
            );
    }
}