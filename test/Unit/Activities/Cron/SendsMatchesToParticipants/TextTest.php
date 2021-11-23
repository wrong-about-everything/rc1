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
    public function testNonInitiatorHasNoInterestsInCommon()
    {
        $this->assertEquals(
            <<<t
Привет, Василий\!

Ваша пара на этой неделе — Полина \(@polzzza\)\.
Вот что ваш собеседник написал о себе\:

_«Моя жизнь в огне\!»_

Чтобы встреча прошла интересно и продуктивно, посмотрите нашу [статью о том, как назначить встречу и о чем на ней говорить](https://telegra.ph/Kak-podgotovitsya-i-provesti-vstrechu-09-06)\.

Приятного общения\!
t
            ,
            (new Text(
                'Василий',
                'Полина',
                'polzzza',
                [(new SkySurfing())->value(), (new DayDreaming())->value()],
                [(new Networking())->value()],
                new FromString('Моя жизнь в огне!'),
                false,
                'Чтобы встреча прошла интересно и продуктивно, посмотрите нашу [статью о том, как назначить встречу и о чем на ней говорить](https://telegra.ph/Kak-podgotovitsya-i-provesti-vstrechu-09-06)\.'
            ))
                ->value()
        );
    }

    public function testInitiatorHasNoInterestsInCommon()
    {
        $this->assertEquals(
            <<<t
Привет, Василий\!

Ваша пара на этой неделе — Полина \(@polzzza\)\.
Вот что ваш собеседник написал о себе\:

_«Моя жизнь в огне\!»_

Каждый раз мы рандомно выбираем одного человека из пары, кто должен написать собеседнику и договориться о встрече, онлайн или оффлайн\. В этот раз ответственный — вы\. Советуем не откладывать и написать @polzzza прямо сейчас — так больше шансов не забыть про встречу\.

Приятного общения\!
t
            ,
            (new Text(
                'Василий',
                'Полина',
                'polzzza',
                [(new SkySurfing())->value(), (new DayDreaming())->value()],
                [(new Networking())->value()],
                new FromString('Моя жизнь в огне!'),
                true,
                null
            ))
                ->value()
        );
    }

    public function testNonInitiatorHasSingleInterestInCommon()
    {
        $this->assertEquals(
            <<<t
Привет, Василий\!

Ваша пара на этой неделе — Полина \(@polzzza\)\. Среди ваших общих интересов — Daydreaming\.
Вот что ваш собеседник написал о себе\:

_«Моя жизнь в огне\!»_

Чтобы встреча прошла интересно и продуктивно, посмотрите нашу [статью о том, как назначить встречу и о чем на ней говорить](https://telegra.ph/Kak-podgotovitsya-i-provesti-vstrechu-09-06)\.

Приятного общения\!
t
            ,
            (new Text(
                'Василий',
                'Полина',
                'polzzza',
                [(new SkySurfing())->value(), (new DayDreaming())->value()],
                [(new Networking())->value(), (new DayDreaming())->value()],
                new FromString('Моя жизнь в огне!'),
                false,
                'Чтобы встреча прошла интересно и продуктивно, посмотрите нашу [статью о том, как назначить встречу и о чем на ней говорить](https://telegra.ph/Kak-podgotovitsya-i-provesti-vstrechu-09-06)\.'
            ))
                ->value()
        );
    }

    public function testInitiatorHasSingleInterestInCommon()
    {
        $this->assertEquals(
            <<<t
Привет, Василий\!

Ваша пара на этой неделе — Полина \(@polzzza\)\. Среди ваших общих интересов — Daydreaming\.
Вот что ваш собеседник написал о себе\:

_«Моя жизнь в огне\!»_

Каждый раз мы рандомно выбираем одного человека из пары, кто должен написать собеседнику и договориться о встрече, онлайн или оффлайн\. В этот раз ответственный — вы\. Советуем не откладывать и написать @polzzza прямо сейчас — так больше шансов не забыть про встречу\.

Приятного общения\!
t
            ,
            (new Text(
                'Василий',
                'Полина',
                'polzzza',
                [(new SkySurfing())->value(), (new DayDreaming())->value()],
                [(new Networking())->value(), (new DayDreaming())->value()],
                new FromString('Моя жизнь в огне!'),
                true,
                null
            ))
                ->value()
        );
    }

    public function testNonInitiatorHasMultipleInterestInCommon()
    {
        $this->assertEquals(
            <<<t
Привет, Василий\!

Ваша пара на этой неделе — Полина \(@polzzza\)\. У вас совпали такие интересы\: Нетворкинг без определенной темы, Sky surfing и Daydreaming\.
Вот что ваш собеседник написал о себе\:

_«Моя жизнь в огне\!»_

Чтобы встреча прошла интересно и продуктивно, посмотрите нашу [статью о том, как назначить встречу и о чем на ней говорить](https://telegra.ph/Kak-podgotovitsya-i-provesti-vstrechu-09-06)\.

Приятного общения\!
t
            ,
            (new Text(
                'Василий',
                'Полина',
                'polzzza',
                [(new Networking())->value(), (new SkySurfing())->value(), (new DayDreaming())->value()],
                [(new Networking())->value(), (new SkySurfing())->value(), (new DayDreaming())->value()],
                new FromString('Моя жизнь в огне!'),
                false,
                'Чтобы встреча прошла интересно и продуктивно, посмотрите нашу [статью о том, как назначить встречу и о чем на ней говорить](https://telegra.ph/Kak-podgotovitsya-i-provesti-vstrechu-09-06)\.'
            ))
                ->value()
        );
    }

    public function testInitiatorHasMultipleInterestInCommon()
    {
        $this->assertEquals(
            <<<t
Привет, Василий\!

Ваша пара на этой неделе — Полина \(@polzzza\)\. У вас совпали такие интересы\: Нетворкинг без определенной темы, Sky surfing и Daydreaming\.
Вот что ваш собеседник написал о себе\:

_«Моя жизнь в огне\!»_

Каждый раз мы рандомно выбираем одного человека из пары, кто должен написать собеседнику и договориться о встрече, онлайн или оффлайн\. В этот раз ответственный — вы\. Советуем не откладывать и написать @polzzza прямо сейчас — так больше шансов не забыть про встречу\.

Приятного общения\!
t
            ,
            (new Text(
                'Василий',
                'Полина',
                'polzzza',
                [(new Networking())->value(), (new SkySurfing())->value(), (new DayDreaming())->value()],
                [(new Networking())->value(), (new SkySurfing())->value(), (new DayDreaming())->value()],
                new FromString('Моя жизнь в огне!'),
                true,
                null
            ))
                ->value()
        );
    }

    public function testNonInitiatorHasMultipleInterestInCommonAndAboutMeIsEmpty()
    {
        $this->assertEquals(
            <<<t
Привет, Василий\!

Ваша пара на этой неделе — Полина \(@polzzza\)\. У вас совпали такие интересы\: Нетворкинг без определенной темы, Sky surfing и Daydreaming\.

Чтобы встреча прошла интересно и продуктивно, посмотрите нашу [статью о том, как назначить встречу и о чем на ней говорить](https://telegra.ph/Kak-podgotovitsya-i-provesti-vstrechu-09-06)\.

Приятного общения\!
t
            ,
            (new Text(
                'Василий',
                'Полина',
                'polzzza',
                [(new Networking())->value(), (new SkySurfing())->value(), (new DayDreaming())->value()],
                [(new Networking())->value(), (new SkySurfing())->value(), (new DayDreaming())->value()],
                new Emptie(),
                false,
                'Чтобы встреча прошла интересно и продуктивно, посмотрите нашу [статью о том, как назначить встречу и о чем на ней говорить](https://telegra.ph/Kak-podgotovitsya-i-provesti-vstrechu-09-06)\.'
            ))
                ->value()
        );
    }

    public function testInitiatorHasMultipleInterestInCommonAndAboutMeIsEmpty()
    {
        $this->assertEquals(
            <<<t
Привет, Василий\!

Ваша пара на этой неделе — Полина \(@polzzza\)\. У вас совпали такие интересы\: Нетворкинг без определенной темы, Sky surfing и Daydreaming\.

Каждый раз мы рандомно выбираем одного человека из пары, кто должен написать собеседнику и договориться о встрече, онлайн или оффлайн\. В этот раз ответственный — вы\. Советуем не откладывать и написать @polzzza прямо сейчас — так больше шансов не забыть про встречу\.

Приятного общения\!
t
            ,
            (new Text(
                'Василий',
                'Полина',
                'polzzza',
                [(new Networking())->value(), (new SkySurfing())->value(), (new DayDreaming())->value()],
                [(new Networking())->value(), (new SkySurfing())->value(), (new DayDreaming())->value()],
                new Emptie(),
                true,
                null
            ))
                ->value()
        );
    }
}