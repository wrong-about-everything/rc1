<?php

declare(strict_types=1);

namespace RC\Activities\Cron\SendsMatchesToParticipants;

use RC\Domain\About\Pure\About;
use RC\Domain\UserInterest\InterestId\Pure\Single\FromInteger;
use RC\Domain\UserInterest\InterestName\Pure\FromInterestId;
use RC\Infrastructure\TelegramBot\MessageToUser\MarkdownV2;

class Text
{
    private $participantFirstName;
    private $matchFirstName;
    private $matchTelegramHandle;
    private $participantInterestedIn;
    private $matchInterestedIn;
    private $aboutMatch;
    private $isInitiator;
    private $meetingTips;

    public function __construct(
        string $participantFirstName,
        string $matchFirstName,
        string $matchTelegramHandle,
        array $participantInterestedIn,
        array $matchInterestedIn,
        About $aboutMatch,
        bool $isInitiator,
        ?string $meetingTips
    )
    {
        $this->participantFirstName = $participantFirstName;
        $this->matchFirstName = $matchFirstName;
        $this->matchTelegramHandle = $matchTelegramHandle;
        $this->participantInterestedIn = $participantInterestedIn;
        $this->matchInterestedIn = $matchInterestedIn;
        $this->aboutMatch = $aboutMatch;
        $this->isInitiator = $isInitiator;
        $this->meetingTips = $meetingTips;
    }

    public function value(): string
    {
        $interestsInCommon = array_values(array_intersect($this->participantInterestedIn, $this->matchInterestedIn));
        if (empty($interestsInCommon)) {
            return
                $this->heyThere()
                    .
                $this->hereIsYourMatchWithNoInterestsInCommon()
                    .
                $this->hereIsWhatYouMatchToldAboutHerself()
                    .
                $this->textForInitiators()
                    .
                $this->meetingTips()
                    .
                $this->haveAGoodTime();
        } elseif (count($interestsInCommon) === 1) {
            return
                $this->heyThere()
                .
                $this->hereIsYourMatchWithOneInterestInCommon((int) $interestsInCommon[0])
                .
                $this->hereIsWhatYouMatchToldAboutHerself()
                .
                $this->textForInitiators()
                .
                $this->meetingTips()
                .
                $this->haveAGoodTime();
        }

        return
            $this->heyThere()
            .
            $this->hereIsYourMatch() . ' ' . $this->youHaveMultipleInterestInCommon($interestsInCommon)
            .
            $this->hereIsWhatYouMatchToldAboutHerself()
            .
            $this->textForInitiators()
            .
            $this->meetingTips()
            .
            $this->haveAGoodTime();
    }

    private function multipleInterests(array $interestsInCommon)
    {
        $interestNames =
            array_map(
                function (int $interestId) {
                    return (new FromInterestId(new FromInteger($interestId)))->value();
                },
                $interestsInCommon
            );
        $implodedInterests = '';
        for ($i = 0; $i < count($interestNames); $i++) {
            $separator =
                $i === 0
                    ? ''
                    : ($i === count($interestNames) - 1 ? ' и ' : ', ')
            ;
            $implodedInterests .= $separator . $interestNames[$i];
        }

        return $implodedInterests;
    }

    private function newLine()
    {
        return PHP_EOL;
    }

    private function heyThere()
    {
        return
            sprintf(
                'Привет, %s\!',
                (new MarkdownV2($this->participantFirstName))->value()
            )
                .
            $this->newLine() . $this->newLine()
            ;
    }

    private function hereIsYourMatch()
    {
        return
            sprintf(
                'Ваша пара на этой неделе — %s \(@%s\)\.',
                (new MarkdownV2($this->matchFirstName))->value(),
                (new MarkdownV2($this->matchTelegramHandle))->value()
            );
    }

    private function hereIsYourMatchWithNoInterestsInCommon()
    {
        return
            $this->hereIsYourMatch()
                .
            $this->newLine();
    }

    private function hereIsYourMatchWithOneInterestInCommon(int $interestId)
    {
        return
            $this->hereIsYourMatch()
                . ' ' .
            sprintf(
                'Среди ваших общих интересов — %s\.',
                (new MarkdownV2(
                    (new FromInterestId(
                        new FromInteger($interestId)
                    ))
                        ->value()
                ))
                    ->value()
            )
            .
            $this->newLine();
    }

    private function youHaveMultipleInterestInCommon(array $interestsInCommon)
    {
        return
            sprintf(
                'У вас совпали такие интересы\: %s\.',
                (new MarkdownV2($this->multipleInterests($interestsInCommon)))->value()
            )
            .
            $this->newLine();
    }

    private function hereIsWhatYouMatchToldAboutHerself()
    {
        if ($this->aboutMatch->empty()) {
            return $this->newLine();
        }

        return
            sprintf(
                'Вот что ваш собеседник написал о себе\:

_«%s»_'
                ,
                (new MarkdownV2($this->aboutMatch->value()))->value()
            )
            .
            $this->newLine() . $this->newLine()
            ;
    }

    private function textForInitiators()
    {
        if ($this->isInitiator) {
            return
                (new MarkdownV2(
                    sprintf(
                        'Каждый раз мы рандомно выбираем одного человека из пары, кто должен написать собеседнику и договориться о встрече, онлайн или оффлайн. В этот раз ответственный — вы. Советуем не откладывать и написать @%s прямо сейчас — так больше шансов не забыть про встречу.',
                        $this->matchTelegramHandle
                    )
                ))
                    ->value()
                . $this->newLine() . $this->newLine();
        }

        return '';
    }

    private function meetingTips()
    {
        if (is_null($this->meetingTips)) {
            return '';
        }

        return $this->meetingTips . $this->newLine() . $this->newLine();
    }

    private function haveAGoodTime()
    {
        return 'Приятного общения\!';
    }
}