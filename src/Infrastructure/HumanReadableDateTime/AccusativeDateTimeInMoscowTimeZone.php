<?php

declare(strict_types=1);

namespace RC\Infrastructure\HumanReadableDateTime;

use DateTime;
use IntlDateFormatter;
use IntlTimeZone;
use Meringue\ISO8601DateTime;
use Meringue\ISO8601DateTime\AdjustedAccordingToTimeZone;
use Meringue\ISO8601DateTime\PhpSpecificTimeZone\Moscow;
use Meringue\ISO8601DateTime\PhpSpecificTimeZone\UTC;
use Meringue\ISO8601DateTime\TheBeginningOfADay;
use Meringue\ISO8601DateTime\Tomorrow;
use Meringue\ISO8601Interval\Floating\NDays;
use Meringue\ISO8601Interval\WithFixedStartDateTime\FromRange;
use Meringue\ISO8601Interval\WithFixedStartDateTime\FromStartDateTimeAndInterval;
use RC\Domain\RussianGrammar\AccusativeDayOfWeekWithPreposition\FromISODateTime;

class AccusativeDateTimeInMoscowTimeZone
{
    private $now;
    private $dateTime;

    public function __construct(ISO8601DateTime $now, ISO8601DateTime $dateTime)
    {
        $this->now = new AdjustedAccordingToTimeZone($now, new Moscow());
        $this->dateTime = new AdjustedAccordingToTimeZone($dateTime, new Moscow());
    }

    public function value(): string
    {
        if ((new TheBeginningOfADay($this->now))->equalsTo(new TheBeginningOfADay($this->dateTime))) {
            return 'сегодня';
        } elseif ((new TheBeginningOfADay(new Tomorrow($this->now)))->equalsTo(new TheBeginningOfADay($this->dateTime))) {
            return 'завтра';
        } elseif ((new TheBeginningOfADay(new Tomorrow(new Tomorrow($this->now))))->equalsTo(new TheBeginningOfADay($this->dateTime))) {
            return
                (new IntlDateFormatter(
                    'ru-RU',
                    IntlDateFormatter::SHORT,
                    IntlDateFormatter::SHORT,
                    IntlTimeZone::createTimeZone('Europe/Moscow'),
                    IntlDateFormatter::GREGORIAN,
                    sprintf('%s', 'послезавтра, d MMMM (это EEEE)')
                ))
                    ->format(
                        new DateTime($this->dateTime->value())
                    );
        } elseif ((new FromRange($this->now, $this->dateTime))->shorterThan(new FromStartDateTimeAndInterval($this->now, new NDays(7)))) {
            return
                (new IntlDateFormatter(
                    'ru-RU',
                    IntlDateFormatter::SHORT,
                    IntlDateFormatter::SHORT,
                    IntlTimeZone::createTimeZone('Europe/Moscow'),
                    IntlDateFormatter::GREGORIAN,
                    sprintf('%s, d MMMM', (new FromISODateTime($this->dateTime))->value())
                ))
                    ->format(
                        new DateTime($this->dateTime->value())
                    );
        }

        return
            (new IntlDateFormatter(
                'ru-RU',
                IntlDateFormatter::SHORT,
                IntlDateFormatter::SHORT,
                IntlTimeZone::createTimeZone('Europe/Moscow'),
                IntlDateFormatter::GREGORIAN,
                'd MMMM (это EEEE)'
            ))
                ->format(
                    new DateTime($this->dateTime->value())
                );
    }
}