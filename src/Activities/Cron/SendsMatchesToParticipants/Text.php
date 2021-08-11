<?php

declare(strict_types=1);

namespace RC\Activities\Cron\SendsMatchesToParticipants;

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

    public function __construct(string $participantFirstName, string $matchFirstName, string $matchTelegramHandle, array $participantInterestedIn, array $matchInterestedIn, string $aboutMatch)
    {
        $this->participantFirstName = $participantFirstName;
        $this->matchFirstName = $matchFirstName;
        $this->matchTelegramHandle = $matchTelegramHandle;
        $this->participantInterestedIn = $participantInterestedIn;
        $this->matchInterestedIn = $matchInterestedIn;
        $this->aboutMatch = $aboutMatch;
    }

    public function value(): string
    {
        $interestsInCommon = array_values(array_intersect($this->participantInterestedIn, $this->matchInterestedIn));
        if (empty($interestsInCommon)) {
            return
                sprintf(
                    <<<t
Привет, %s\!

Ваша пара на этой неделе — %s \(@%s\)\.
Вот что ваш собеседник написал о себе\:

_«%s»_

Приятного общения\!
t
                    ,
                    (new MarkdownV2($this->participantFirstName))->value(),
                    (new MarkdownV2($this->matchFirstName))->value(),
                    (new MarkdownV2($this->matchTelegramHandle))->value(),
                    (new MarkdownV2($this->aboutMatch))->value()
                );
        } elseif (count($interestsInCommon) === 1) {
            return
                sprintf(
                    <<<t
Привет, %s\!

Ваша пара на этой неделе — %s \(@%s\)\. Среди ваших общих интересов — %s\.
Вот что ваш собеседник написал о себе\:

_«%s»_

Приятного общения\!
t
                    ,
                    (new MarkdownV2($this->participantFirstName))->value(),
                    (new MarkdownV2($this->matchFirstName))->value(),
                    (new MarkdownV2($this->matchTelegramHandle))->value(),
                    (new MarkdownV2(
                        (new FromInterestId(
                            new FromInteger((int) $interestsInCommon[0])
                        ))
                            ->value()
                    ))
                        ->value(),
                    (new MarkdownV2($this->aboutMatch))->value()
                );
        }

        return
            sprintf(
                <<<t
Привет, %s\!

Ваша пара на этой неделе — %s \(@%s\)\. У вас совпали такие интересы\: %s\.
Вот что ваш собеседник написал о себе\:

_«%s»_

Приятного общения\!
t
                ,
                (new MarkdownV2($this->participantFirstName))->value(),
                (new MarkdownV2($this->matchFirstName))->value(),
                (new MarkdownV2($this->matchTelegramHandle))->value(),
                (new MarkdownV2($this->multipleInterests($interestsInCommon)))->value(),
                (new MarkdownV2($this->aboutMatch))->value()
            );
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
}