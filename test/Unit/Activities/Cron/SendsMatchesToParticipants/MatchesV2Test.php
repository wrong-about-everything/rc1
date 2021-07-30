<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Activities\Cron\SendsMatchesToParticipants;

use PHPUnit\Framework\TestCase;
use RC\Activities\Cron\SendsMatchesToParticipants\Matches;

class MatchesV2Test extends TestCase
{
    /**
     * @dataProvider participantInterestsAndPairs
     */
    public function testDifferentCombinations(array $participantsInterests, array $pairsAndDropouts)
    {
        $this->assertEquals(
            $pairsAndDropouts,
            (new Matches($participantsInterests))->value()
        );
    }

    public function participantInterestsAndPairs()
    {
        return [
            [
                [
                    1 => ['a'],
                ],
                [
                    'dropouts' => [1],
                    'matches' => [],
                ]
            ],
            [
                [
                    1 => ['a'],
                    2 => ['a'],
                ],
                [
                    'dropouts' => [],
                    'matches' => [[1, 2]],
                ]
            ],
            [
                [
                    1 => ['a', 'c'],
                    2 => ['a', 'c'],
                ],
                [
                    'dropouts' => [],
                    'matches' => [[1, 2]],
                ]
            ],
            [
                [
                    1 => ['a', 'c', 'b'],
                    2 => ['a', 'c'],
                ],
                [
                    'dropouts' => [],
                    'matches' => [[2, 1]],
                ]
            ],
            [
                [
                    1 => ['a', 'c', 'b', 'd'],
                    2 => ['a', 'c'],
                ],
                [
                    'dropouts' => [],
                    'matches' => [[2, 1]],
                ]
            ],
            [
                [
                    1 => ['d'],
                    2 => ['d', 'c', 'b'],
                    3 => ['d', 'c'],
                ],
                [
                    'dropouts' => [2],
                    'matches' => [[1, 3]],
                ]
            ],
            [
                [
                    4 => ['a'],
                    2 => ['d', 'c', 'b'],
                    3 => ['d', 'c'],
                ],
                [
                    'dropouts' => [4],
                    'matches' => [[3, 2]],
                ]
            ],
            [
                [
                    1 => ['d'],
                    4 => ['a'],
                    2 => ['d', 'c', 'b'],
                    3 => ['d', 'c'],
                ],
                [
                    'dropouts' => [4, 2],
                    'matches' => [[1, 3]],
                ]
            ],
            [
                [
                    1 => ['d', 'c'],
                    2 => ['d', 'c'],
                    3 => ['d', 'c'],
                    4 => ['d', 'c'],
                ],
                [
                    'dropouts' => [],
                    'matches' => [[1, 2], [3, 4]],
                ]
            ],
            [
                [
                    1 => ['d'],
                    2 => ['d'],
                    3 => ['d'],
                ],
                [
                    'dropouts' => [3],
                    'matches' => [[1, 2]],
                ]
            ],
            [
                [
                    1 => ['d'],
                    4 => ['a'],
                    2 => ['d', 'c', 'b'],
                    3 => ['d', 'c'],
                    5 => ['d', 'c'],
                ],
                [
                    'dropouts' => [4],
                    'matches' => [[1, 3], [5, 2]],
                ]
            ],
            [
                [
                    6 => ['c'],
                    1 => ['d'],
                    4 => ['a'],
                    2 => ['d', 'c', 'b'],
                    3 => ['d', 'c'],
                    5 => ['d', 'c'],
                ],
                [
                    'dropouts' => [4, 2],
                    'matches' => [[6, 3], [1, 5]],
                ]
            ],
            [
                [
                    1 => ['c'],
                    2 => ['d'],
                    3 => ['d', 'c', 'b'],
                    4 => ['d', 'c'],
                    5 => ['d', 'c'],
                ],
                [
                    'dropouts' => [3],
                    'matches' => [[1, 4], [2, 5]],
                ]
            ],
            [
                [
                    1 => ['d'],
                    2 => ['a', 'b', 'c', 'd'],
                    3 => ['c'],
                    4 => ['a'],
                    5 => ['d'],
                    6 => ['b', 'c'],
                ],
                [
                    'dropouts' => [],
                    'matches' => [[1, 5], [3, 6], [4, 2]],
                ]
            ],
            [
                [
                    1 => ['d'],
                    5 => ['b'],
                    3 => ['a'],
                    4 => ['a'],
                    6 => ['b',],
                    2 => ['d'],
                ],
                [
                    'dropouts' => [],
                    'matches' => [[1, 2], [5, 6], [3, 4]],
                ]
            ],
            [
                [
                    1 => ['a'],
                    2 => ['a', 'b', 'c'],
                    3 => ['b'],
                    4 => ['a', 'b', 'c'],
                    5 => ['c'],
                    6 => ['a', 'b', 'c'],
                ],
                [
                    'dropouts' => [],
                    'matches' => [[1, 2], [3, 4], [5, 6]],
                ]
            ],
            [
                [
                    2 => ['a', 'b', 'c'],
                    4 => ['a', 'b', 'c'],
                    6 => ['a', 'b', 'c'],
                    1 => ['a'],
                    3 => ['b'],
                    5 => ['c'],
                ],
                [
                    'dropouts' => [],
                    'matches' => [[1, 2], [5, 4], [3, 6]],
                ]
            ],
            [
                [
                    1 => ['a'],
                    3 => ['b'],
                    5 => ['c'],
                    2 => ['a', 'b', 'c'],
                    4 => ['a', 'b', 'c'],
                    6 => ['a', 'b', 'c'],
                ],
                [
                    'dropouts' => [],
                    'matches' => [[1, 2], [3, 4], [5, 6]],
                ]
            ],
            [
                [
                    1 => ['a'],
                    2 => ['b'],
                    3 => ['c'],
                    4 => ['d'],
                    5 => ['e'],
                ],
                [
                    'dropouts' => [1, 2, 3, 4, 5],
                    'matches' => [],
                ]
            ],
        ];
    }
}