<?php

declare(strict_types=1);

namespace RC\Domain\Matches\ReadModel\Pure;

class WithMatchedDropoutsWithinTheSameSegment implements Matches
{
    private $matches;

    public function __construct(Matches $matches)
    {
        $this->matches = $matches;
    }

    public function value(): array
    {
        $originalDropouts = $this->matches->value()['dropouts'];
        if (empty($originalDropouts)) {
            return $this->matches->value();
        }

        $matchesMadeOfDropouts = [];
        foreach ($originalDropouts as $dropout) {
            if (empty($matchesMadeOfDropouts) || count($matchesMadeOfDropouts[count($matchesMadeOfDropouts) - 1]) === 2) {
                $matchesMadeOfDropouts[] = [$dropout];
            } else {
                $matchesMadeOfDropouts[count($matchesMadeOfDropouts) - 1][] = $dropout;
            }
        }

        if (count($matchesMadeOfDropouts[count($matchesMadeOfDropouts) - 1]) === 1) {
            $lastDropout = array_pop($matchesMadeOfDropouts)[0];
            return [
                'matches' => array_merge($this->matches->value()['matches'], $matchesMadeOfDropouts),
                'dropouts' => [$lastDropout]
            ];
        }

        return [
            'matches' => array_merge($this->matches->value()['matches'], $matchesMadeOfDropouts),
            'dropouts' => []
        ];
    }
}