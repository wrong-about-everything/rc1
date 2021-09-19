<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Domain\Matches\ReadModel;

use PHPUnit\Framework\TestCase;
use RC\Domain\Experience\ExperienceId\Pure\BetweenAYearAndThree;
use RC\Domain\Experience\ExperienceId\Pure\BetweenThreeYearsAndSix;
use RC\Domain\Experience\ExperienceId\Pure\GreaterThanSix;
use RC\Domain\Experience\ExperienceId\Pure\LessThanAYear;
use RC\Domain\Matches\PositionExperienceParticipantsInterestsMatrix\FromArray;
use RC\Domain\Matches\ReadModel\Impure\GeneratedMatchesForAllParticipants;
use RC\Domain\Position\PositionId\Pure\ProductDesigner;
use RC\Domain\Position\PositionId\Pure\ProductManager;

class GeneratedMatchesForAllParticipantsTest extends TestCase
{
    /**
     * @dataProvider matrixAndMatches
     */
    public function test(array $matrix, array $matches)
    {
        $this->assertEquals(
            $matches,
            (new GeneratedMatchesForAllParticipants(
                new FromArray($matrix),
                []
            ))
                ->value()->pure()->raw()
        );
    }

    public function matrixAndMatches()
    {
        return [
            [
                [
                    (new ProductDesigner())->value() => [
                        (new LessThanAYear())->value() => [
                            1 => ['a', 'b', ],
                            2 => ['b', ],
                            3 => ['c', ],
                            5 => ['e',],
                        ],
                        (new BetweenAYearAndThree())->value() => [
                            6 => ['a', 'b', ],
                            7 => ['b', ],
                            8 => ['c', ],
                            9 => ['d', ],
                            10 => ['e',],
                        ],
                        (new BetweenThreeYearsAndSix())->value() => [
                            11 => ['a', 'b', ],
                            12 => ['b', ],
                            13 => ['c', ],
                            14 => ['d', ],
                            15 => ['e',],
                        ],
                        (new GreaterThanSix())->value() => [
                            16 => ['e', 'f', ],
                            17 => ['e', ],
                            18 => ['g', ],
                            19 => ['h', ],
                            20 => ['i',],
                        ],
                    ],
                    (new ProductManager())->value() => [
                        (new LessThanAYear())->value() => [
                            21 => ['a', 'b', ],
                            22 => ['b', ],
                            23 => ['c', ],
                            25 => ['e',],
                            31 => ['f',],
                        ],
                        (new GreaterThanSix())->value() => [
                            26 => ['a', 'b', ],
                            27 => ['b', ],
                            28 => ['c', ],
                            29 => ['d', ],
                            30 => ['e',],
                        ],
                    ]
                ],
                [
                    'dropouts' => [10, 30, 31],
                    'matches' => [[2, 1], [3, 5], [7, 6], [8, 9], [12, 11], [13, 14], [17, 16], [18, 19], [22, 21], [23, 25], [27, 26], [28, 29], [20, 15]]
                ],
            ],
            [
                [
                    (new ProductDesigner())->value() => [
                        (new LessThanAYear())->value() => [
                            2 => ['b', ],
                            3 => ['c', ],
                            5 => ['e',],
                        ],
                        (new BetweenThreeYearsAndSix())->value() => [
                            13 => ['c', ],
                            14 => ['d', ],
                            15 => ['e',],
                        ],
                    ],
                    (new ProductManager())->value() => [
                        (new LessThanAYear())->value() => [
                            23 => ['c', ],
                            25 => ['e',],
                            31 => ['f',],
                        ],
                        (new GreaterThanSix())->value() => [
                            28 => ['c', ],
                            29 => ['d', ],
                            30 => ['e',],
                        ],
                    ]
                ],
                [
                    'dropouts' => [15, 5, 30, 31, ],
                    'matches' => [[2, 3], [13, 14], [23, 25], [28, 29]]
                ],
            ],
        ];
    }
}