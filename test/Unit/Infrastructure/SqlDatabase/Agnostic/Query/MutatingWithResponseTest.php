<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\SqlDatabase\Agnostic\Query;

use Meringue\Timeline\Point\Now;
use RC\Domain\Order\Source\ContactCenter;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\OrdersDeliveryConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\MutatingWithResponse;
use PHPUnit\Framework\TestCase;
use RC\Tests\Infrastructure\Storage\Postgres\ResetOmsEnvironment;

class MutatingWithResponseTest extends TestCase
{
    public function testSuccess()
    {
        $externalOrder = 'aaa111';
        $orderNumber = '6b8b8186-84f0-433d-b783-f9e5270a6e14';
        $friendlyId = '111';

        $r =
            (new MutatingWithResponse(
                <<<q
insert into order_registration_request (source, external_order_id, order_id, friendly_id, timestamp)
values (?, ?, ?, ?, ?)
on conflict do nothing
returning source, external_order_id
q
                ,
                [
                    (new ContactCenter())->value(),
                    $externalOrder,
                    $orderNumber,
                    $friendlyId,
                    (new Now())->value()
                ]
            ))
                ->result((new OrdersDeliveryConnection())->open());

        $this->assertEquals(
            (new ContactCenter())->value(),
            $r->value()[0]['source']
        );
        $this->assertEquals(
            $externalOrder,
            $r->value()[0]['external_order_id']
        );
    }

    protected function setUp()
    {
        (new ResetOmsEnvironment(new OrdersDeliveryConnection()))->run();
    }
}
