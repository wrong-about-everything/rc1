<?php

declare(strict_types=1);

namespace RC\Domain\RoundRegistrationQuestion;

use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Domain\BotId\BotId;
use RC\Infrastructure\TelegramBot\UserId\Pure\TelegramUserId;

class NextRoundRegistrationQuestion implements RoundRegistrationQuestion
{
    private $telegramUserId;
    private $botId;
    private $connection;

    private $cached;

    public function __construct(TelegramUserId $telegramUserId, BotId $botId, OpenConnection $connection)
    {
        $this->telegramUserId = $telegramUserId;
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
        $roundRegistrationQuestion =
            (new Selecting(
                <<<q
        select mriq.*
        from meeting_round_registration_question mriq
            left join user_round_registration_progress urrp on mriq.id = urrp.invitation_question_id
            left join "user" u on urrp.user_id = u.id and u.telegram_id = ?
        where mriq.bot_id = ? and urrp.invitation_question_id is null
        order by mriq.ordinal_number asc
        limit 1
        q
                ,
                [$this->telegramUserId->value(), $this->botId->value()],
                $this->connection
            ))
                ->response();
        if (!$roundRegistrationQuestion->isSuccessful()) {
            return $roundRegistrationQuestion;
        }
        if (!isset($roundRegistrationQuestion->pure()->raw()[0])) {
            return new Successful(new Emptie());
        }

        return new Successful(new Present($roundRegistrationQuestion->pure()->raw()[0]));
    }
}