<?php

declare(strict_types=1);

namespace RC\Domain\Matches\ReadModel\Pure;

class WithMatchedDropoutsWithinTheSameSegment implements Matches
{
    private $matches;
    private $participants2PastPairs;

    public function __construct(Matches $matches, array $participants2PastPairs)
    {
        $this->matches = $matches;
        $this->participants2PastPairs =
            array_combine(
                array_keys($participants2PastPairs),
                array_reduce(
                    array_values($participants2PastPairs),
                    function (array $pastPairsWithKeys, array $pastPairs) {
                        $pastPairsWithKeys[] = array_combine($pastPairs, $pastPairs);
                        return $pastPairsWithKeys;
                    },
                    []
                )
            );
    }

    public function value(): array
    {
        $originalDropouts = $this->matches->value()['dropouts'];
        if (empty($originalDropouts)) {
            return $this->matches->value();
        }

        $matchesMadeOfDropouts = $this->matchesMadeOfDropouts($originalDropouts);
        return [
            'matches' => array_merge($this->matches->value()['matches'], $matchesMadeOfDropouts['matches']),
            'dropouts' => $matchesMadeOfDropouts['non_matched_participants']
        ];
    }

    private function matchesMadeOfDropouts(array $originalDropouts)
    {
        return
            (new GeneratedMatchesWithinSingleInterest(
                array_reduce(
                    $originalDropouts,
                    function (array $indexedDropouts, $dropout) {
                        return $indexedDropouts + [$dropout => $dropout];
                    },
                    []
                ),
                $this->participants2PastPairs
            ))
                ->value();
    }
}