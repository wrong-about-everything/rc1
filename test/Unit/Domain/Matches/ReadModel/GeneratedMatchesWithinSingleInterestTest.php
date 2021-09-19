<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Domain\Matches\ReadModel;

use PHPUnit\Framework\TestCase;
use RC\Domain\Matches\ReadModel\Pure\GeneratedMatchesWithinSingleInterest;

class GeneratedMatchesWithinSingleInterestTest extends TestCase
{
    /**
     * @dataProvider participants()
     */
    public function test(array $participants, array $participants2PastPairs, array $expectedMatches)
    {
        $this->assertEquals(
            $expectedMatches,
            (new GeneratedMatchesWithinSingleInterest($participants, $participants2PastPairs))->value()
        );
    }

    public function participants(): array
    {
        return [
            [
                [11 => 11, 22 => 22, 33 => 33],
                [],
                [
                    'matches' => [[11, 22]],
                    'non_matched_participants' => [33],
                ]
            ],
            [
                [1 => 1, 2 => 2, 3 => 3],
                [],
                [
                    'matches' => [[1, 2]],
                    'non_matched_participants' => [3],
                ]
            ],
            [
                [1 => 1, 2 => 2, 3 => 3],
                [
                    1 => [2 => 2],
                    2 => [1 => 1]
                ],
                [
                    'matches' => [[1, 3]],
                    'non_matched_participants' => [2],
                ]
            ],
        ];
    }
}