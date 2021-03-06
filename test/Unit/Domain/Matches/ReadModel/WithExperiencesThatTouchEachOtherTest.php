<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Domain\Matches\ReadModel;

use PHPUnit\Framework\TestCase;
use RC\Domain\Experience\ExperienceId\Pure\BetweenAYearAndThree;
use RC\Domain\Experience\ExperienceId\Pure\BetweenThreeYearsAndSix;
use RC\Domain\Experience\ExperienceId\Pure\GreaterThanSix;
use RC\Domain\Experience\ExperienceId\Pure\LessThanAYear;
use RC\Domain\Matches\ReadModel\Pure\WithExperiencesThatTouchEachOther;
use RC\Domain\Position\PositionId\Pure\SystemOrBusinessAnalyst;
use RC\Domain\Position\PositionId\Pure\ProductDesigner;
use RC\Domain\Position\PositionId\Pure\ProductManager;

class WithExperiencesThatTouchEachOtherTest extends TestCase
{
    /**
     * @dataProvider originalMatchesAndMatchesWithMatchedDropoutsHavingDifferentExperience
     */
    public function testDifferentCombinations(array $originalMatches, array $participants2PastPairs, array $matchesWithMatchedDropouts)
    {
        $this->assertEquals(
            $matchesWithMatchedDropouts,
            (new WithExperiencesThatTouchEachOther($originalMatches, $participants2PastPairs))->value()
        );
    }

    public function originalMatchesAndMatchesWithMatchedDropoutsHavingDifferentExperience()
    {
        return [
            [
                [
                    (new ProductDesigner())->value() => [
                        (new GreaterThanSix())->value() => [1],
                        (new BetweenAYearAndThree())->value() => [2],
                        (new LessThanAYear())->value() => [3],
                        (new BetweenThreeYearsAndSix())->value() => [4],
                    ],
                ],
                [],
                [
                    'dropouts' => [],
                    'matches' => [[1, 4], [2, 3]],
                ],
            ],
            [
                [
                    (new ProductDesigner())->value() => [
                        (new GreaterThanSix())->value() => [1],
                        (new BetweenAYearAndThree())->value() => [2],
                        (new LessThanAYear())->value() => [3],
                        (new BetweenThreeYearsAndSix())->value() => [4],
                    ],
                ],
                [
                    1 => [2],
                    2 => [1],
                ],
                [
                    'dropouts' => [],
                    'matches' => [[1, 4], [2, 3]],
                ],
            ],
            [
                [
                    (new ProductDesigner())->value() => [
                        (new GreaterThanSix())->value() => [1],
                        (new BetweenAYearAndThree())->value() => [2],
                        (new LessThanAYear())->value() => [3],
                        (new BetweenThreeYearsAndSix())->value() => [4],
                    ],
                ],
                [
                    1 => [4],
                    4 => [1]
                ],
                [
                    'dropouts' => [1, 4],
                    'matches' => [[2, 3]],
                ],
            ],
            [
                [
                    (new ProductDesigner())->value() => [
                        (new GreaterThanSix())->value() => [1],
                        (new BetweenAYearAndThree())->value() => [2],
                        (new LessThanAYear())->value() => [3],
                    ],
                ],
                [],
                [
                    'dropouts' => [1],
                    'matches' => [[2, 3]],
                ],
            ],
            [
                [
                    (new ProductDesigner())->value() => [
                        (new GreaterThanSix())->value() => [1],
                        (new BetweenAYearAndThree())->value() => [2],
                        (new LessThanAYear())->value() => [3],
                    ],
                ],
                [
                    2 => [3],
                    3 => [2],
                ],
                [
                    'dropouts' => [1, 2, 3],
                    'matches' => [],
                ],
            ],
            [
                [
                    (new ProductDesigner())->value() => [
                        (new BetweenAYearAndThree())->value() => [2],
                        (new LessThanAYear())->value() => [3],
                        (new BetweenThreeYearsAndSix())->value() => [4],
                    ],
                ],
                [],
                [
                    'dropouts' => [3],
                    'matches' => [[4, 2]],
                ],
            ],
            [
                [
                    (new ProductDesigner())->value() => [
                        (new GreaterThanSix())->value() => [1],
                        (new BetweenAYearAndThree())->value() => [2],
                        (new LessThanAYear())->value() => [3],
                        (new BetweenThreeYearsAndSix())->value() => [4],
                    ],
                    (new ProductManager())->value() => [
                        (new GreaterThanSix())->value() => [5],
                        (new LessThanAYear())->value() => [6],
                        (new BetweenThreeYearsAndSix())->value() => [7],
                    ],
                    (new SystemOrBusinessAnalyst())->value() => [
                        (new GreaterThanSix())->value() => [9],
                        (new LessThanAYear())->value() => [8],
                    ],
                ],
                [],
                [
                    'dropouts' => [6, 9, 8],
                    'matches' => [[1, 4], [2, 3], [5, 7]],
                ],
            ],
            [
                [
                    (new ProductDesigner())->value() => [
                        (new GreaterThanSix())->value() => [1],
                        (new BetweenAYearAndThree())->value() => [2],
                        (new LessThanAYear())->value() => [3],
                        (new BetweenThreeYearsAndSix())->value() => [4],
                    ],
                    (new ProductManager())->value() => [
                        (new GreaterThanSix())->value() => [5],
                        (new LessThanAYear())->value() => [6],
                        (new BetweenThreeYearsAndSix())->value() => [7],
                    ],
                    (new SystemOrBusinessAnalyst())->value() => [
                        (new GreaterThanSix())->value() => [9],
                        (new LessThanAYear())->value() => [8],
                    ],
                ],
                [
                    1 => [4, 3],
                    3 => [4, 1],
                    4 => [3, 1],
                ],
                [
                    'dropouts' => [1, 4, 6, 9, 8],
                    'matches' => [[2, 3], [5, 7]],
                ],
            ],
        ];
    }
}