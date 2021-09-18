<?php

declare(strict_types=1);

namespace RC\Domain\Matches\ReadModel\Pure;

class GeneratedMatchesForSegment implements Matches
{
    private $participants2Interests;
    private $participants2PastPairs;
    private $cached;

    public function __construct(array $participants2Interests, array $participants2PastPairs)
    {
        $this->participants2Interests = $participants2Interests;
        $this->participants2PastPairs = $participants2PastPairs;
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
        $interestIdToInterestIntensityDistribution = $this->interestIdToInterestIntensityDistribution($participants2Interests, $uniqueInterests);
        $theMostIntenseInterestId = $this->theMostNarrowAndMostCommonInterestId($uniqueInterests, $interestIdToInterestIntensityDistribution);
        $intensityDistribution = $interestIdToInterestIntensityDistribution[$theMostIntenseInterestId];

        $currentMatchesAndNonMatchedParticipants = $this->matchesAndNonMatchedParticipantsForCurrentInterest($intensityDistribution);
        $currentMatches = $currentMatchesAndNonMatchedParticipants['matches'];
        $currentDropouts = [];

        if (!empty($currentMatchesAndNonMatchedParticipants['non_matched_participants'])) {
            foreach ($currentMatchesAndNonMatchedParticipants['non_matched_participants'] as $nonMatchedParticipant) {
                // if non-matched participant has a single interest, he is a dropout
                if (count($participants2Interests[$nonMatchedParticipant]) === 1) {
                    unset($participants2Interests[$nonMatchedParticipant]);
                    $currentDropouts[] = $nonMatchedParticipant;
                } else {
                    // non-matched participant has other interests. So I remove this unlucky interest and try to find a match based on other interests.
                    $participants2Interests[$nonMatchedParticipant] =
                        array_filter(
                            $participants2Interests[$nonMatchedParticipant],
                            function ($interestId) use ($theMostIntenseInterestId) {
                                return $interestId !== $theMostIntenseInterestId;
                            }
                        );
                }
            }
        }

        foreach ($currentMatches as $match) {
            unset($participants2Interests[$match[0]]);
            unset($participants2Interests[$match[1]]);
        }

        return $this->doValue($participants2Interests, array_merge($dropouts, $currentDropouts), array_merge($matches, $currentMatches));
    }

    /**
     * return
     * [
     *      'interest_a' => [
     *          // interests qty => participants having this many interests including "interest_a"
     *          1 => [1, 4, 5],
     *          2 => [3],
     *          4 => [2, 6],
     *      ],
     *      'interest_b' => [
     *          // interests qty => participants having this many interests including "interest_b"
     *          1 => [9, 2, 8],
     *          3 => [7],
     *          4 => [3, 5],
     *      ],
     * ]
     */
    private function interestIdToInterestIntensityDistribution(array $participants2Interests, array $uniqueInterests)
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

    /**
     * Это интерес, который наибольшее количество участников указало как единственный.
     * Или, в более общем случае, это интерес, чаще других указываемый с меньшим количеством других интересов. Например,
     * если никто не указал ни один интерес как единственный, то ищем интерес, который наибольшее кол-во участников указало вместе с каким-то другим.
     * И так далее. Такой интерес я называю "узким".
     *
     * Если есть два одинаково узких интереса, то можно выбрать более редкий, то есть тот, который указало меньшее число людей.
     *
     * А можно выбрать более частый, тот есть тот, который указало большее число людей. В этом случае такой интерес я называю наиболее интенсивным.
     *
     * А можно вообще брать первый попавшийся. Как оказалось, если в алгоритме не учитывать дубли, на число неудачников это не влияет.
     * А если учитывать?
     */
    private function theMostNarrowAndMostCommonInterestId(array $uniqueInterests, array $interestIdToInterestIntensityDistribution)
    {
        usort(
            $uniqueInterests,
            function ($leftInterestId, $rightInterestId) use ($uniqueInterests, $interestIdToInterestIntensityDistribution) {
                for ($n = 1; $n <= count($uniqueInterests); $n++) {
                    /* Вариант, когда беру первый попавшийся из двух одинаково узких */
//                    if (!isset($interestIdToInterestIntensityDistribution[$leftInterestId][$n]) && isset($interestIdToInterestIntensityDistribution[$rightInterestId][$n])) {
//                        return 1;
//                    } elseif (isset($interestIdToInterestIntensityDistribution[$leftInterestId][$n]) && !isset($interestIdToInterestIntensityDistribution[$rightInterestId][$n])) {
//                        return -1;
//                    } elseif (!isset($interestIdToInterestIntensityDistribution[$leftInterestId][$n]) && !isset($interestIdToInterestIntensityDistribution[$rightInterestId][$n])) {
//                        continue;
//                    }

                    /* Вариант, когда беру наиболее частый из двух одинаково узких -- текущий на бою */
                    $participantQtyWhoMarkedNInterestsIncludingTheLeft = count($interestIdToInterestIntensityDistribution[$leftInterestId][$n] ?? []);
                    $participantQtyWhoMarkedNInterestsIncludingTheRight = count($interestIdToInterestIntensityDistribution[$rightInterestId][$n] ?? []);
                    if ($participantQtyWhoMarkedNInterestsIncludingTheLeft < $participantQtyWhoMarkedNInterestsIncludingTheRight) {
                        return 1;
                    } elseif ($participantQtyWhoMarkedNInterestsIncludingTheLeft > $participantQtyWhoMarkedNInterestsIncludingTheRight) {
                        return -1;
                    }

                    /* Вариант, когда беру наименее частый из одинаково узких */
//                    if (!isset($interestIdToInterestIntensityDistribution[$leftInterestId][$n]) && isset($interestIdToInterestIntensityDistribution[$rightInterestId][$n])) {
//                        return 1;
//                    } elseif (isset($interestIdToInterestIntensityDistribution[$leftInterestId][$n]) && !isset($interestIdToInterestIntensityDistribution[$rightInterestId][$n])) {
//                        return -1;
//                    } elseif (!isset($interestIdToInterestIntensityDistribution[$leftInterestId][$n]) && !isset($interestIdToInterestIntensityDistribution[$rightInterestId][$n])) {
//                        continue;
//                    }
//                    $participantQtyWhoMarkedNInterestsIncludingTheLeft = count($interestIdToInterestIntensityDistribution[$leftInterestId][$n]);
//                    $participantQtyWhoMarkedNInterestsIncludingTheRight = count($interestIdToInterestIntensityDistribution[$rightInterestId][$n]);
//                    if ($participantQtyWhoMarkedNInterestsIncludingTheLeft < $participantQtyWhoMarkedNInterestsIncludingTheRight) {
//                        return -1;
//                    } elseif ($participantQtyWhoMarkedNInterestsIncludingTheLeft > $participantQtyWhoMarkedNInterestsIncludingTheRight) {
//                        return 1;
//                    }
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
            );
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

    private function matchesAndNonMatchedParticipantsForCurrentInterest(array $intensityDistributionForCurrentInterestId)
    {
        $participantsWithTheMostNarrowInterest = array_values($intensityDistributionForCurrentInterestId)[0];
        if (count($participantsWithTheMostNarrowInterest) % 2 == 0) {
            return $this->matchesAndNonMatchedParticipants($participantsWithTheMostNarrowInterest);
        }

        $lastParticipant = array_values(array_slice($participantsWithTheMostNarrowInterest, count($participantsWithTheMostNarrowInterest) - 1))[0];
        $matchesAndNonMatchedParticipants =
            $this->matchesAndNonMatchedParticipants(
                array_slice(
                    $participantsWithTheMostNarrowInterest,
                    0,
                    count($participantsWithTheMostNarrowInterest) - 1
                )
            );

        $pairToLastParticipant = null;
        foreach (array_slice($intensityDistributionForCurrentInterestId, 1) as $participants) {
            $pairToLastParticipant = $this->pairToCurrentParticipantId($participants, $lastParticipant, []);
            if (!is_null($pairToLastParticipant)) {
                break;
            }
        }

        if (is_null($pairToLastParticipant)) {
            return [
                'matches' => $matchesAndNonMatchedParticipants['matches'],
                'non_matched_participants' => array_merge($matchesAndNonMatchedParticipants['non_matched_participants'], [$lastParticipant])
            ];
        } else {
            return [
                'matches' => array_merge($matchesAndNonMatchedParticipants['matches'], [[$lastParticipant, $pairToLastParticipant]]),
                'non_matched_participants' => $matchesAndNonMatchedParticipants['non_matched_participants']
            ];
        }
    }

    private function matchesAndNonMatchedParticipants(array $participants)
    {
        return $this->matchesAndNonMatchedParticipantsIteration([], [], array_values($participants));
    }

    private function matchesAndNonMatchedParticipantsIteration(array $matches, array $nonMatchedParticipants, array $participants)
    {
        if (empty($participants)) {
            return ['matches' => $matches, 'non_matched_participants' => $nonMatchedParticipants];
        }

        $currentParticipantId = array_shift($participants);
        $pairToCurrentParticipantId = $this->pairToCurrentParticipantId($participants, $currentParticipantId, $matches);

        if ($pairToCurrentParticipantId !== null) {
            unset($participants[array_search($pairToCurrentParticipantId, $participants)]);
            return
                $this->matchesAndNonMatchedParticipantsIteration(
                    array_merge($matches, [[$currentParticipantId, $pairToCurrentParticipantId]]),
                    $nonMatchedParticipants,
                    $participants
                );
        } else {
            return
                $this->matchesAndNonMatchedParticipantsIteration(
                    $matches,
                    array_merge($nonMatchedParticipants, [$currentParticipantId]),
                    $participants
                );
        }
    }

    private function pairToCurrentParticipantId(array $participants, $currentParticipantId, array $matches)
    {
        return
            array_values(
                array_filter(
                    $participants,
                    function (int $participantId) use ($currentParticipantId, $matches) {
                        return
                            !in_array($participantId, $this->participants2PastPairs[$currentParticipantId] ?? [])
                            &&
                            !in_array(
                                $participantId,
                                array_reduce(
                                    $matches,
                                    function (array $allCurrentlyMatchedParticipants, array $pair) {
                                        return array_merge($allCurrentlyMatchedParticipants, $pair);
                                    },
                                    []
                                )
                            );
                    }
                )
            )[0] ?? null;
    }
}