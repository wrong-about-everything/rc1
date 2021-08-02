<?php

declare(strict_types=1);

namespace RC\Activities\Cron\SendsMatchesToParticipants;

use RC\Domain\UserInterest\InterestId\Pure\Single\FromInteger;
use RC\Domain\UserInterest\InterestName\Pure\FromInterestId;

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
Привет, %s!

Ваша пара на этой неделе -- %s (@%s).
Вот что ваш собеседник написал о себе:

__«%s»__.

Приятного общения!
t
                    ,
                    $this->participantFirstName,
                    $this->matchFirstName,
                    $this->matchTelegramHandle,
                    $this->aboutMatch
                );
        } elseif (count($interestsInCommon) === 1) {
            return
                sprintf(
                    <<<t
Привет, %s!

Ваша пара на этой неделе -- %s (@%s). Среди ваших общих интересов -- %s.
Вот что ваш собеседник написал о себе:

__«%s»__.

Приятного общения!
t
                    ,
                    $this->participantFirstName,
                    $this->matchFirstName,
                    $this->matchTelegramHandle,
                    (new FromInterestId(new FromInteger((int) $interestsInCommon[0])))->value(),
                    $this->aboutMatch
                );
        }

        return
            sprintf(
                <<<t
Привет, %s!

Ваша пара на этой неделе -- %s (@%s). У вас совпали такие интересы: %s.
Вот что ваш собеседник написал о себе:

__«%s»__.

Приятного общения!
t
                ,
                $this->participantFirstName,
                $this->matchFirstName,
                $this->matchTelegramHandle,
                $this->multipleInterests($interestsInCommon),
                $this->aboutMatch
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