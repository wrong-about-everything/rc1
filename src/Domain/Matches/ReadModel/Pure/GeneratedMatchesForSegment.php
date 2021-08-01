<?php

declare(strict_types=1);

namespace RC\Domain\Matches\ReadModel\Pure;

class GeneratedMatchesForSegment implements Matches
{
    private $participants2Interests;
    private $cached;

    public function __construct(array $participants2Interests)
    {
        $this->participants2Interests = $participants2Interests;
        $this->cached = null;
    }

    public function value(): array
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue($this->participants2Interests, [], []);
        }

        return $this->cached;
    }

    private function doValue(array $participants2Interests, array $dropouts, array $matches)
    {
        if (empty($participants2Interests)) {
            return ['dropouts' => $dropouts, 'matches' => $matches];
        }

        $uniqueInterests = $this->uniqueInterests($participants2Interests);
        $interestIdToInterestQtyMatrix = $this->interestIdToInterestQtyMatrix($participants2Interests, $uniqueInterests);
        $theMostIntenseInterestId = $this->theMostIntenseInterestId($uniqueInterests, $interestIdToInterestQtyMatrix);
        $intensityDistribution = $interestIdToInterestQtyMatrix[$theMostIntenseInterestId];

        $currentMatches = $this->matchesForCurrentInterest($intensityDistribution);

        if (count($currentMatches[count($currentMatches) - 1]) === 1) {
            $nonMatchedParticipant = $currentMatches[count($currentMatches) - 1][0];
            // if non-matched participant has a single interest, he is a dropout
            if (count($participants2Interests[$nonMatchedParticipant]) === 1) {
                unset($participants2Interests[$nonMatchedParticipant]);
                $currentDropouts = [$nonMatchedParticipant];
            } else {
                // non-matched participant has other interests. So I remove this unlucky interest and try to find a match based on other interests.
                $participants2Interests[$nonMatchedParticipant] =
                    array_filter(
                        $participants2Interests[$nonMatchedParticipant],
                        function ($interestId) use ($theMostIntenseInterestId) {
                            return $interestId !== $theMostIntenseInterestId;
                        }
                    );
                $currentDropouts = [];
            }

            $currentMatchesWithoutOutliers = array_slice($currentMatches, 0, count($currentMatches) - 1);
        } else {
            $currentDropouts = [];;
            $currentMatchesWithoutOutliers = $currentMatches;
        }

        foreach ($currentMatchesWithoutOutliers as $match) {
            unset($participants2Interests[$match[0]]);
            unset($participants2Interests[$match[1]]);
        }

        return $this->doValue($participants2Interests, array_merge($dropouts, $currentDropouts), array_merge($matches, $currentMatchesWithoutOutliers));
    }

    /**
     * [
     *      'interest_a' => [
     *          // interests qty => participants
     *          1 => [1, 4, 5],
     *          2 => [3],
     *          4 => [2, 6],
     *      ],
     *      'interest_b' => [
     *          // interests qty => participants
     *          1 => [1, 2, 8],
     *          3 => [7],
     *          4 => [3, 5],
     *      ],
     * ]
     */
    private function interestIdToInterestQtyMatrix(array $participants2Interests, array $uniqueInterests)
    {
        $matrix = [];
        foreach ($uniqueInterests as $interestId) {
            $matrix[$interestId] = [];
            for ($i = 1; $i <= count($uniqueInterests); $i++) {
                $participantsWhoMarkedNInterestsIncludingThePassedOne = $this->participantsWhoMarkedNInterestsIncludingThePassedOne($i, $interestId, $participants2Interests);
                if (!empty($participantsWhoMarkedNInterestsIncludingThePassedOne)) {
                    $matrix[$interestId][$i] = $participantsWhoMarkedNInterestsIncludingThePassedOne;
                }
            }
        }

        return $matrix;
    }

    private function theMostIntenseInterestId(array $uniqueInterests, array $interestIdToInterestQtyMatrix)
    {
        usort(
            $uniqueInterests,
            function ($leftInterestId, $rightInterestId) use ($uniqueInterests, $interestIdToInterestQtyMatrix) {
                for ($i = 1; $i <= count($uniqueInterests); $i++) {
                    $participantQtyWhoMarkedNInterestsIncludingTheLeft = count($interestIdToInterestQtyMatrix[$leftInterestId][$i] ?? []);
                    $participantQtyWhoMarkedNInterestsIncludingTheRight = count($interestIdToInterestQtyMatrix[$rightInterestId][$i] ?? []);
                    if ($participantQtyWhoMarkedNInterestsIncludingTheLeft < $participantQtyWhoMarkedNInterestsIncludingTheRight) {
                        return 1;
                    } elseif ($participantQtyWhoMarkedNInterestsIncludingTheLeft > $participantQtyWhoMarkedNInterestsIncludingTheRight) {
                        return -1;
                    }
                }

                return 0;
            }
        );

        return $uniqueInterests[0];
    }

    private function participantsWhoMarkedNInterestsIncludingThePassedOne(int $nInterests, $includingThisInterestId, array $participants2Interests)
    {
        return
            array_keys(
                array_filter(
                    $participants2Interests,
                    function (array $interests) use ($nInterests, $includingThisInterestId) {
                        return count($interests) === $nInterests && in_array($includingThisInterestId, $interests);
                    }
                )
            )
            ;
    }

    private function uniqueInterests(array $participants2Interests)
    {
        return
            array_unique(
                array_reduce(
                    $participants2Interests,
                    function (array $carry, array $interests) {
                        return array_merge($carry, $interests);
                    },
                    []
                )
            );
    }

    private function matchesForCurrentInterest(array $intensityDistributionForCurrentInterestId)
    {
        $matches = [];
        foreach ($intensityDistributionForCurrentInterestId as $participants) {
            if (!empty($matches) && count($matches[count($matches) - 1]) === 2) {
                return $matches;
            } elseif (!empty($matches) && count($matches[count($matches) - 1]) === 1) {
                $matches[count($matches) - 1][] = $participants[0];
                return $matches;
            }

            foreach ($participants as $participant) {
                if (empty($matches) || count($matches[count($matches) - 1]) === 2) {
                    $matches[][] = $participant;
                } elseif (!empty($matches) && count($matches[count($matches) - 1]) === 1) {
                    $matches[count($matches) - 1][] = $participant;
                }
            }
        }

        return $matches;
    }
}