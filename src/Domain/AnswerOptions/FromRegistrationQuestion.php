<?php

declare(strict_types=1);

namespace RC\Domain\AnswerOptions;

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
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Experience;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Position;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;

class FromRegistrationQuestion implements AnswerOptions
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

    private function doValue()
    {
        if (!$this->registrationQuestion->value()->isSuccessful()) {
            return $this->registrationQuestion->value();
        }

        if ((new RegistrationQuestionType($this->registrationQuestion))->equals(new FromPure(new Position()))) {
            return
                new Successful(
                    new Present(
                        array_map(
                            function (int $position) {
                                return [['text' => (new FromPosition(new FromInteger($position)))->value()]];
                            },
                            (new AvailablePositions($this->botId, $this->connection))->value()->pure()->raw()
                        )
                    )
                );
        } elseif ((new RegistrationQuestionType($this->registrationQuestion))->equals(new FromPure(new Experience()))) {
            return
                new Successful(
                    new Present(
                        array_map(
                            function (int $experience) {
                                return [['text' => (new FromExperience(new ExperienceFromInteger($experience)))->value()]];
                            },
                            (new AvailableExperiences($this->botId, $this->connection))->value()->pure()->raw()
                        )
                    )
                );
        }

        return new Successful(new Present([]));
    }
}