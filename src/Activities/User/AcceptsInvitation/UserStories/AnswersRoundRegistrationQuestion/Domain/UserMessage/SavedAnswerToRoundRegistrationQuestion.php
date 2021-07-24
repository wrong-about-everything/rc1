<?php

declare(strict_types=1);

namespace RC\Activities\User\AcceptsInvitation\UserStories\AnswersRoundRegistrationQuestion\Domain\UserMessage;

use Exception;
use RC\Domain\BotId\BotId;
use RC\Domain\Experience\ExperienceId\Pure\FromExperienceName;
use RC\Domain\Experience\ExperienceName\FromString as ExperienceName;
use RC\Domain\Position\PositionId\Pure\FromPositionName;
use RC\Domain\Position\PositionName\FromString;
use RC\Domain\RoundRegistrationQuestion\RoundRegistrationQuestion;
use RC\Domain\RoundRegistrationQuestion\RoundRegistrationQuestionId\Impure\FromRoundRegistrationQuestion as RoundRegistrationQuestionId;
use RC\Domain\RoundRegistrationQuestion\RoundRegistrationQuestionId\Pure\FromImpure;
use RC\Domain\RoundRegistrationQuestion\RoundRegistrationQuestionId\Pure\RoundRegistrationQuestionId as PureRoundRegistrationQuestionId;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\TransactionalQueryFromMultipleQueries;
use RC\Infrastructure\TelegramBot\UserId\Pure\TelegramUserId;
use RC\Infrastructure\TelegramBot\UserMessage\UserMessage;

class SavedAnswerToRoundRegistrationQuestion implements UserMessage
{
    private $telegramUserId;
    private $botId;
    private $userMessage;
    private $question;
    private $connection;

    public function __construct(TelegramUserId $telegramUserId, BotId $botId, UserMessage $userMessage, RoundRegistrationQuestion $question, OpenConnection $connection)
    {
        $this->telegramUserId = $telegramUserId;
        $this->botId = $botId;
        $this->userMessage = $userMessage;
        $this->question = $question;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        $roundRegistrationQuestionId = new RoundRegistrationQuestionId($this->question);
        if (!$roundRegistrationQuestionId->value()->isSuccessful()) {
            return $roundRegistrationQuestionId->value();
        }

        $updateProgressResponse = $this->persistenceResponse(new FromImpure($roundRegistrationQuestionId));
        if (!$updateProgressResponse->isSuccessful()) {
            return $updateProgressResponse;
        }

        return $this->userMessage->value();
    }

    public function exists(): bool
    {
        return $this->userMessage->exists();
    }

    private function persistenceResponse(PureRoundRegistrationQuestionId $roundRegistrationQuestionId)
    {
        return
            (new TransactionalQueryFromMultipleQueries(
                [
                    new SingleMutating(
                        <<<q
insert into user_round_registration_progress (invitation_question_id, user_id)
select ?, id from "user" where telegram_id = ?
q
                        ,
                        [$roundRegistrationQuestionId->value(), $this->telegramUserId->value()],
                        $this->connection
                    ),
                    $this->updateBotUserQuery(),
                ],
                $this->connection
            ))
                ->response();
    }

    private function updateBotUserQuery()
    {
        if ((new ProfileRecordType($this->question))->equals(new FromPure(new Position()))) {
            return
                new SingleMutating(
                    <<<q
update bot_user
set position = ?
from "user"
where "user".id = bot_user.user_id and "user".telegram_id = ? and bot_user.bot_id = ?
q
                    ,
                    [(new FromPositionName(new FromString($this->userMessage->value()->pure()->raw())))->value(), $this->telegramUserId->value(), $this->botId->value()],
                    $this->connection
                );
        } elseif ((new ProfileRecordType($this->question))->equals(new FromPure(new Experience()))) {
            return
                new SingleMutating(
                    <<<q
update bot_user
set experience = ?
from "user"
where "user".id = bot_user.user_id and "user".telegram_id = ? and bot_user.bot_id = ?
q
                    ,
                    [(new FromExperienceName(new ExperienceName($this->userMessage->value()->pure()->raw())))->value(), $this->telegramUserId->value(), $this->botId->value()],
                    $this->connection
                );
        } elseif ((new ProfileRecordType($this->question))->equals(new FromPure(new About()))) {
            return
                new SingleMutating(
                    <<<q
update bot_user
set about = ?
from "user"
where "user".id = bot_user.user_id and "user".telegram_id = ? and bot_user.bot_id = ?
q
                                    ,
                    [$this->userMessage->value()->pure()->raw(), $this->telegramUserId->value(), $this->botId->value()],
                    $this->connection
                );
        }

        throw new Exception(sprintf('Unknown user profile record type given: %s', (new ProfileRecordType($this->question))->value()->pure()->raw()));
    }
}