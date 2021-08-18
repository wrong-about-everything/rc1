<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Activities\Cron\SendsMatchesToParticipants;

use PHPUnit\Framework\TestCase;
use RC\Activities\Cron\SendsMatchesToParticipants\Text;
use RC\Domain\About\Pure\Emptie;
use RC\Domain\About\Pure\FromString;
use RC\Domain\UserInterest\InterestId\Pure\Single\DayDreaming;
use RC\Domain\UserInterest\InterestId\Pure\Single\Networking;
use RC\Domain\UserInterest\InterestId\Pure\Single\SkySurfing;

class TextTest extends TestCase
{
    public function testNoInterestsInCommon()
    {
        $this->assertEquals(
            <<<t
Привет, Василий\!

Ваша пара на этой неделе — Полина \(@polzzza\)\.
Вот что ваш собеседник написал о себе\:

_«Моя жизнь в огне\!»_

Приятного общения\!
t
            ,
            (new Text(
                'Василий',
                'Полина',
                'polzzza',
                [(new SkySurfing())->value(), (new DayDreaming())->value()],
                [(new Networking())->value()],
                new FromString('Моя жизнь в огне!')
            ))
                ->value()
        );
    }

    public function testSingleInterestInCommon()
    {
        $this->assertEquals(
            <<<t
Привет, Василий\!

Ваша пара на этой неделе — Полина \(@polzzza\)\. Среди ваших общих интересов — Daydreaming\.
Вот что ваш собеседник написал о себе\:

_«Моя жизнь в огне\!»_

Приятного общения\!
t
            ,
            (new Text(
                'Василий',
                'Полина',
                'polzzza',
                [(new SkySurfing())->value(), (new DayDreaming())->value()],
                [(new Networking())->value(), (new DayDreaming())->value()],
                new FromString('Моя жизнь в огне!')
            ))
                ->value()
        );
    }

    public function testMultipleInterestInCommon()
    {
        $this->assertEquals(
            <<<t
Привет, Василий\!

Ваша пара на этой неделе — Полина \(@polzzza\)\. У вас совпали такие интересы\: Нетворкинг без определенной темы, Sky surfing и Daydreaming\.
Вот что ваш собеседник написал о себе\:

_«Моя жизнь в огне\!»_

Приятного общения\!
t
            ,
            (new Text(
                'Василий',
                'Полина',
                'polzzza',
                [(new Networking())->value(), (new SkySurfing())->value(), (new DayDreaming())->value()],
                [(new Networking())->value(), (new SkySurfing())->value(), (new DayDreaming())->value()],
                new FromString('Моя жизнь в огне!')
            ))
                ->value()
        );
    }

    public function testMultipleInterestInCommonAndAboutMeIsEmpty()
    {
        $this->assertEquals(
            <<<t
Привет, Василий\!

Ваша пара на этой неделе — Полина \(@polzzza\)\. У вас совпали такие интересы\: Нетворкинг без определенной темы, Sky surfing и Daydreaming\.

Приятного общения\!
t
            ,
            (new Text(
                'Василий',
                'Полина',
                'polzzza',
                [(new Networking())->value(), (new SkySurfing())->value(), (new DayDreaming())->value()],
                [(new Networking())->value(), (new SkySurfing())->value(), (new DayDreaming())->value()],
                new Emptie()
            ))
                ->value()
        );
    }
}