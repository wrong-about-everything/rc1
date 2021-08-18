<?php

declare(strict_types=1);

namespace RC\Domain\SentReplyToUser\ReplyOptions;

use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Experience\AvailableExperiences\ByBotId as AvailableExperiences;
use RC\Domain\Experience\ExperienceId\Pure\FromInteger as ExperienceFromInteger;
use RC\Domain\Experience\ExperienceName\FromExperience;
use RC\Domain\Position\AvailablePositions\ByBotId as AvailablePositions;
use RC\Domain\Position\PositionId\Pure\FromInteger;
use RC\Domain\Position\PositionName\FromPosition;
use RC\Domain\RegistrationQuestion\RegistrationQuestion;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Impure\FromPure;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Impure\FromRegistrationQuestion as RegistrationQuestionType;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\About;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Experience;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Position;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Domain\TelegramBot\UserMessage\Pure\Skipped;

class FromRegistrationQuestion implements ReplyOptions
{
    private $registrationQuestion;
    private $botId;
    private $connection;
    private $cached;

    public function __construct(RegistrationQuestion $registrationQuestion, BotId $botId, OpenConnection $connection)
    {
        $this->registrationQuestion = $registrationQuestion;
        $this->botId = $botId;
        $this->connection = $connection;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    private function doValue(): ImpureValue
    {
        if (!$this->registrationQuestion->value()->isSuccessful()) {
            return $this->registrationQuestion->value();
        }

        if ((new RegistrationQuestionType($this->registrationQuestion))->equals(new FromPure(new Position()))) {
            return new Successful(new Present($this->twoPositionsInARow()));
        } elseif ((new RegistrationQuestionType($this->registrationQuestion))->equals(new FromPure(new Experience()))) {
            return new Successful(new Present($this->twoExperiencesInARow()));
        } elseif ((new RegistrationQuestionType($this->registrationQuestion))->equals(new FromPure(new About()))) {
            return new Successful(new Present($this->skip()));
        }

        return new Successful(new Present([]));
    }

    private function twoPositionsInARow()
    {
        return
            array_reduce(
                (new AvailablePositions($this->botId, $this->connection))->value()->pure()->raw(),
                function (array $carry, int $position) {
                    if (empty($carry) || count($carry[count($carry) - 1]) === 2) {
                        $carry[] = [['text' => (new FromPosition(new FromInteger($position)))->value()]];
                    } else {
                        $carry[count($carry) - 1][] = ['text' => (new FromPosition(new FromInteger($position)))->value()];
                    }

                    return $carry;
                },
                []
            );
    }

    private function twoExperiencesInARow()
    {
        return
            array_reduce(
                (new AvailableExperiences($this->botId, $this->connection))->value()->pure()->raw(),
                function (array $carry, int $experience) {
                    if (empty($carry) || count($carry[count($carry) - 1]) === 2) {
                        $carry[] = [['text' => (new FromExperience(new ExperienceFromInteger($experience)))->value()]];
                    } else {
                        $carry[count($carry) - 1][] = ['text' => (new FromExperience(new ExperienceFromInteger($experience)))->value()];
                    }

                    return $carry;
                },
                []
            );
    }

    private function skip()
    {
        return [[['text' => (new Skipped())->value()]]];
    }
}