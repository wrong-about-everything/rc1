<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Domain\Matches;

use PHPUnit\Framework\TestCase;
use RC\Domain\Matches\ReadModel\Pure\FromArray;
use RC\Domain\Matches\ReadModel\Pure\WithMatchedDropoutsWithinTheSameSegment;

class WithMatchedDropoutsWithinTheSameSegmentTest extends TestCase
{
    /**
     * @dataProvider originalMatchesAndMatchesWithMatchedDropouts
     */
    public function testDifferentCombinations(array $originalMatches, array $participants2PastPairs, array $matchesWithMatchedDropouts)
    {
        $this->assertEquals(
            $matchesWithMatchedDropouts,
            (new WithMatchedDropoutsWithinTheSameSegment(
                new FromArray($originalMatches),
                $participants2PastPairs
            ))
                ->value()
        );
    }

    public function originalMatchesAndMatchesWithMatchedDropouts()
    {
        return [
            [
                [
                    'dropouts' => [1],
                    'matches' => [],
                ],
                [],
                [
                    'dropouts' => [1],
                    'matches' => [],
                ],
            ],
            [
                [
                    'dropouts' => [],
                    'matches' => [[1, 2]],
                ],
                [],
                [
                    'dropouts' => [],
                    'matches' => [[1, 2]],
                ],
            ],
            [
                [
                    'dropouts' => [2],
                    'matches' => [[1, 3]],
                ],
                [],
                [
                    'dropouts' => [2],
                    'matches' => [[1, 3]],
                ],
            ],
            [
                [
                    'dropouts' => [4, 2],
                    'matches' => [[1, 3]],
                ],
                [],
                [
                    'dropouts' => [],
                    'matches' => [[1, 3], [4, 2]],
                ],
            ],
            [
                [
                    'dropouts' => [4, 7, 6],
                    'matches' => [[1, 3], [5, 2]],
                ],
                [],
                [
                    'dropouts' => [6],
                    'matches' => [[1, 3], [5, 2], [4, 7]],
                ]
            ],
            [
                [
                    'dropouts' => [4, 7, 6],
                    'matches' => [[1, 3], [5, 2]],
                ],
                [
                    4 => [7],
                    7 => [4],
                ],
                [
                    'dropouts' => [7],
                    'matches' => [[1, 3], [5, 2], [4, 6]],
                ]
            ],
            [
                [
                    'dropouts' => [4, 7, 6],
                    'matches' => [[1, 3], [5, 2]],
                ],
                [
                    4 => [6],
                    6 => [4],
                ],
                [
                    'dropouts' => [6],
                    'matches' => [[1, 3], [5, 2], [4, 7]],
                ]
            ],
        ];
    }
}