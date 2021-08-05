<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\HumanReadableDateTime;

use Meringue\ISO8601DateTime\FromISO8601;
use Meringue\ISO8601DateTime\Tomorrow;
use Meringue\ISO8601Interval\Floating\NDays;
use Meringue\ISO8601Interval\Floating\OneHour;
use Meringue\Timeline\Point\Future;
use Meringue\Timeline\Point\Now;
use PHPUnit\Framework\TestCase;
use RC\Infrastructure\HumanReadableDateTime\AccusativeDateTimeInMoscowTimeZone;

class AccusativeDateTimeTest extends TestCase
{
    public function testToday()
    {
        $this->assertEquals(
            'сегодня',
            (new AccusativeDateTimeInMoscowTimeZone(new Now(), new Future(new Now(), new OneHour())))->value()
        );
    }

    public function testTheSameDay()
    {
        $this->assertEquals(
            'сегодня',
            (new AccusativeDateTimeInMoscowTimeZone(
                new FromISO8601('2021-08-05T21:39:41-06:00'),
                new Future(
                    new FromISO8601('2021-08-06T06:39:41+03:00'),
                    new OneHour()
                )
            ))
                ->value()
        );
    }

    public function testOneMoreSameDay()
    {
        $this->assertEquals(
            'сегодня',
            (new AccusativeDateTimeInMoscowTimeZone(new FromISO8601('2021-08-06T00:39:41+12:00'), new FromISO8601('2021-08-04T23:40:41-13:00')))->value()
        );
    }

    public function testTomorrow()
    {
        $this->assertEquals(
            'завтра',
            (new AccusativeDateTimeInMoscowTimeZone(new FromISO8601('2021-08-06T00:39:41+12:00'), new FromISO8601('2021-08-06T10:40:41+13:00')))->value()
        );
    }

    public function testDayAfterTomorrow()
    {
        $now = new FromISO8601('2021-08-05T00:28:31+03');
        $this->assertEquals(
            'послезавтра, 7 августа (это суббота)',
            (new AccusativeDateTimeInMoscowTimeZone($now, new Tomorrow(new Tomorrow($now))))->value()
        );
    }

    public function testDuringAWeek()
    {
        $now = new FromISO8601('2021-08-04T21:28:31+00');
        $this->assertEquals(
            'в среду, 11 августа',
            (new AccusativeDateTimeInMoscowTimeZone($now, new Future($now, new NDays(6))))->value()
        );
    }

    public function testMoreThanAWeekLater()
    {
        $now = new FromISO8601('2021-08-05T00:28:31+03');
        $this->assertEquals(
            '13 августа (это пятница)',
            (new AccusativeDateTimeInMoscowTimeZone($now, new Future($now, new NDays(8))))->value()
        );
    }
}