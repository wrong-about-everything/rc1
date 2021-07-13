<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\SqlDatabase\Agnostic\Search\Query;

use RC\Infrastructure\SqlDatabase\Agnostic\Search\QueryExpressions\DefaultExpressions;
use RC\Infrastructure\SqlDatabase\Agnostic\Search\Query\OrderBy;
use RC\Infrastructure\SqlDatabase\Agnostic\Search\Query\Select;
use RC\Infrastructure\Http\Request\Url\ParsedQuery\DefaultParsedQuery;
use RC\Infrastructure\SqlDatabase\Agnostic\Search\Query\WithPagination;
use RC\Infrastructure\SqlDatabase\Agnostic\Search\Query\MaxRecordsLimit;
use PHPUnit\Framework\TestCase;
use RC\Infrastructure\Http\Request\Url\Parts\Query\FromString;
use RC\Infrastructure\SqlDatabase\Agnostic\Search\Expression\EmptyExpression;
use \Throwable;

class WithPaginationTest extends TestCase
{
    public function testCorrectValues()
    {
        $query = $this->query($this->currentPage(), $this->recordsPerPage());

        $paginator =
            new WithPagination(
                new OrderBy(
                    new DefaultExpressions(new EmptyExpression()),
                    new Select()
                ),
                $query
            );

        $this->assertEquals(
            sprintf(
                '%s limit %s offset %s',
                $this->previousString(),
                $this->recordsPerPage(),
                ($this->currentPage() - 1) * $this->recordsPerPage()
            ),
            $paginator->string()
        );
    }

    public function testWithMissingPageSize()
    {
        $query = $this->queryWithoutPageSize($this->currentPage());

        $paginator =
            new WithPagination(
                new OrderBy(
                    new DefaultExpressions(new EmptyExpression()),
                    new Select()
                ),
                $query,
                19
            );

        $this->assertEquals(
            sprintf(
                '%s limit %s offset %s',
                $this->previousString(),
                19,
                ($this->currentPage() - 1) * 19
            ),
            $paginator->string()
        );
    }

    public function testRequestedRecordsPerPageExceedsReasonableNumber()
    {
        $query = $this->query($this->currentPage(), '1000000');

        $paginator =
            new WithPagination(
                new OrderBy(
                    new DefaultExpressions(new EmptyExpression()),
                    new Select()
                ),
                $query
            );

        $this->assertEquals(
            sprintf(
                '%s limit %s offset %s',
                $this->previousString(),
                (new MaxRecordsLimit())->defaultMaxRecords(),
                ($this->currentPage() - 1) * (new MaxRecordsLimit())->defaultMaxRecords()
            ),
            $paginator->string()
        );
    }

    /**
     * @dataProvider badValues
     */
    public function testBadCurrentPageValues($currentPage)
    {
        try {
            $q =
                new WithPagination(
                    new OrderBy(
                        new DefaultExpressions(new EmptyExpression()),
                        new Select()
                    ),
                    $this->query($currentPage, $this->recordsPerPage())
                );
        } catch (Throwable $exception) {
            $this->fail('Reasonable defaults should have been set');
            return;
        }

        $this->assertEquals('select * limit 12 offset 0', $q->string());
    }

    /**
     * @dataProvider badValues
     */
    public function testBadPageSize($recordsPerPage)
    {
        $query = $this->query($this->currentPage(), $recordsPerPage);
        try {
            $q =
                new WithPagination(
                    new OrderBy(
                        new DefaultExpressions(new EmptyExpression()),
                        new Select()
                    ),
                    $query
                );
        } catch (Throwable $exception) {
            $this->fail('Reasonable defaults should have been set');
            return;
        }

        $this->assertEquals('select * limit 100 offset 3300', $q->string());
    }

    private function query(string $page, string $recordsPerPage): DefaultParsedQuery
    {
        return
            new DefaultParsedQuery(
                new FromString(
                    sprintf(
                        'statuses=40,50,70&page_size=%s&page=%s&sort=order_id:desc',
                        $recordsPerPage,
                        $page
                    )
                )
            );
    }

    private function queryWithoutPageSize(string $page): DefaultParsedQuery
    {
        return
            new DefaultParsedQuery(
                new FromString(
                    sprintf(
                        'statuses=40,50,70&page=%s&sort=order_id:desc',
                        $page
                    )
                )
            );
    }

    public function badValues()
    {
        return [['-1'], ['0'], [''], ['aaa']];
    }

    private function recordsPerPage(): string
    {
        return '12';
    }

    private function currentPage(): string
    {
        return '34';
    }

    private function previousString(): string
    {
        return 'select *';
    }
}
