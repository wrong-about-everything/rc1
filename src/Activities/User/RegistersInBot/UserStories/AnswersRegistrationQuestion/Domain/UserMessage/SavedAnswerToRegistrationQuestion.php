<?php

declare(strict_types=1);

namespace RC\Activities\User\RegistersInBot\UserStories\AnswersRegistrationQuestion\Domain\UserMessage;

use Exception;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Experience\ExperienceId\Pure\FromExperienceName;
use RC\Domain\Experience\ExperienceName\FromString as ExperienceName;
use RC\Domain\Position\PositionId\Pure\FromPositionName;
use RC\Domain\Position\PositionName\FromString;
use RC\Domain\RegistrationQuestion\RegistrationQuestion;
use RC\Domain\RegistrationQuestion\RegistrationQuestionId\Impure\FromRegistrationQuestion as RegistrationQuestionId;
use RC\Domain\RegistrationQuestion\RegistrationQuestionId\Pure\FromImpure;
use RC\Domain\RegistrationQuestion\RegistrationQuestionId\Pure\RegistrationQuestionId as PureRegistrationQuestionId;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Impure\FromPure;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Impure\FromRegistrationQuestion as ProfileRecordType;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\About;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Experience;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Position;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\EmptyQuery;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\TransactionalQueryFromMultipleQueries;
use RC\Infrastructure\TelegramBot\UserId\Pure\TelegramUserId;
use RC\Infrastructure\TelegramBot\UserMessage\Impure\UserMessage;
use RC\Domain\TelegramBot\UserMessage\Pure\Skipped;
use RC\Infrastructure\TelegramBot\UserMessage\Pure\UserMessage as PureUserMessage;

class SavedAnswerToRegistrationQuestion implements UserMessage
{
    private $telegramUserId;
    private $botId;
    private $userMessage;
    private $question;
    private $connection;

    public function __construct(TelegramUserId $telegramUserId, BotId $botId, PureUserMessage $userMessage, RegistrationQuestion $question, OpenConnection $connection)
    {
        $this->telegramUserId = $telegramUserId;
        $this->botId = $botId;
        $this->userMessage = $userMessage;
        $this->question = $question;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        $registrationQuestionId = new RegistrationQuestionId($this->question);
        if (!$registrationQuestionId->value()->isSuccessful()) {
            return $registrationQuestionId->value();
        }

        $updateProgressResponse = $this->persistenceResponse(new FromImpure($registrationQuestionId));
        if (!$updateProgressResponse->isSuccessful()) {
            return $updateProgressResponse;
        }

        return new Successful(new Present($this->userMessage->value()));
    }

    public function exists(): ImpureValue
    {
        return new Successful(new Present($this->userMessage->exists()));
    }

    private function persistenceResponse(PureRegistrationQuestionId $registrationQuestionId)
    {
        return
            (new TransactionalQueryFromMultipleQueries(
                [
                    new SingleMutating(
                        <<<q
insert into user_registration_progress (registration_question_id, user_id)
select ?, id from "telegram_user" where telegram_id = ?
q
                        ,
                        [$registrationQuestionId->value(), $this->telegramUserId->value()],
                        $this->connection
                    ),
                    $this->updateBotUserQuery(),
                ],
                $this->connection
            ))
                ->response();
    }

    private function updateBotUserQuery(): Query
    {
        if ((new ProfileRecordType($this->question))->equals(new FromPure(new Position()))) {
            return
                new SingleMutating(
                    <<<q
update bot_user
set position = ?
from "telegram_user"
where "telegram_user".id = bot_user.user_id and "telegram_user".telegram_id = ? and bot_user.bot_id = ?
q
                    ,
                    [(new FromPositionName(new FromString($this->userMessage->value())))->value(), $this->telegramUserId->value(), $this->botId->value()],
                    $this->connection
                );
        } elseif ((new ProfileRecordType($this->question))->equals(new FromPure(new Experience()))) {
            return
                new SingleMutating(
                    <<<q
update bot_user
set experience = ?
from "telegram_user"
where "telegram_user".id = bot_user.user_id and "telegram_user".telegram_id = ? and bot_user.bot_id = ?
q
                    ,
                    [(new FromExperienceName(new ExperienceName($this->userMessage->value())))->value(), $this->telegramUserId->value(), $this->botId->value()],
                    $this->connection
                );
        } elseif ((new ProfileRecordType($this->question))->equals(new FromPure(new About()))) {
            if ($this->userMessage->equals(new Skipped())) {
                return new EmptyQuery();
            }

            return
                new SingleMutating(
                    <<<q
update bot_user
set about = ?
from "telegram_user"
where "telegram_user".id = bot_user.user_id and "telegram_user".telegram_id = ? and bot_user.bot_id = ?
q
                    ,
                    [$this->userMessage->value(), $this->telegramUserId->value(), $this->botId->value()],
                    $this->connection
                );
        }

        throw new Exception(sprintf('Unknown user profile record type given: %s', (new ProfileRecordType($this->question))->value()->pure()->raw()));
    }
}