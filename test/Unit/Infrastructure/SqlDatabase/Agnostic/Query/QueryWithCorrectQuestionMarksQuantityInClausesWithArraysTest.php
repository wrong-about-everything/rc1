<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\SqlDatabase\Agnostic\Query;

use PHPUnit\Framework\TestCase;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\QueryWithCorrectQuestionMarksQuantityInClausesWithArrays;

class QueryWithCorrectQuestionMarksQuantityInClausesWithArraysTest extends TestCase
{
    public function testQueryWithSingleStrictEqualityCondition()
    {
        $queryValue =
            (new QueryWithCorrectQuestionMarksQuantityInClausesWithArrays(
                'select from _order where id = ?',
                [1]
            ))
                ->value()
        ;

        $this->assertEquals('select from _order where id = ?', $queryValue);
    }

    public function testQueryWithSingleInCondition()
    {
        $queryValue =
            (new QueryWithCorrectQuestionMarksQuantityInClausesWithArrays(
                'select from _order where id in (?)',
                [[1, 2]]
            ))
                ->value();

        $this->assertEquals('select from _order where id in (?, ?)', $queryValue);
    }

    public function testQueryWithMixedConditions()
    {
        $queryValue =
            (new QueryWithCorrectQuestionMarksQuantityInClausesWithArrays(
                'select from _order where vasya in (?) and fedya = ? and tolya in (?) and jenya = ?',
                [[1, 2], 3, [4, 5], 6]
            ))
                ->value()
        ;

        $this->assertEquals('select from _order where vasya in (?, ?) and fedya = ? and tolya in (?, ?) and jenya = ?', $queryValue);
    }

    public function testQueryWithNamedParameter()
    {
        $queryValue =
            (new QueryWithCorrectQuestionMarksQuantityInClausesWithArrays(
                'select status from _order where data#>>\'{guest,phone}\' = :phone order by registered_at desc limit :limit',
                [[1, 2], 3, [4, 5], 6]
            ))
                ->value()
        ;

        $this->assertEquals('select status from _order where data#>>\'{guest,phone}\' = :phone order by registered_at desc limit :limit', $queryValue);
    }
}
