<?php

declare(strict_types=1);

namespace RC\Domain\RussianGrammar\AccusativeDayOfWeekWithPreposition;

use Meringue\ISO8601DateTime;
use Meringue\WeekDay\Friday;
use Meringue\WeekDay\LocalDayOfWeek;
use Meringue\WeekDay\Monday;
use Meringue\WeekDay\Saturday;
use Meringue\WeekDay\Sunday;
use Meringue\WeekDay\Thursday;
use Meringue\WeekDay\Tuesday;
use Meringue\WeekDay\Wednesday;

class FromISODateTime extends AccusativeDayOfWeekWithPreposition
{
    private $dateTime;

    public function __construct(ISO8601DateTime $dateTime)
    {
        $this->dateTime = $dateTime;
    }

    public function value(): string
    {
        switch ((new LocalDayOfWeek($this->dateTime))->value()) {
            case ((new Sunday())->value()):
                return 'в воскресенье';

            case ((new Monday())->value()):
                return 'в понедельник';

            case ((new Tuesday())->value()):
                return 'во вторник';

            case ((new Wednesday())->value()):
                return 'в среду';

            case ((new Thursday())->value()):
                return 'в четверг';

            case ((new Friday())->value()):
                return 'в пятницу';

            case ((new Saturday())->value()):
                return 'в субботу';
        }
    }
}