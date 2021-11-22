<?php

declare(strict_types=1);

namespace RC\Domain\RegistrationQuestion;

use RC\Domain\TelegramUser\ByTelegramId;
use RC\Domain\TelegramUser\UserId\Impure\FromTelegramUser;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Domain\Bot\BotId\BotId;
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId;

class NextRegistrationQuestion implements RegistrationQuestion
{
    private $telegramUserId;
    private $connection;

    private $cached;

    public function __construct(InternalTelegramUserId $telegramUserId, BotId $botId, OpenConnection $connection)
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
        $registrationQuestion =
            (new Selecting(
                <<<q
        select rq.*
        from registration_question rq
            left join user_registration_progress urp on urp.registration_question_id = rq.id and urp.user_id = ?
        where urp.registration_question_id is null and rq.bot_id = ?
        order by rq.ordinal_number asc
        limit 1
        q
                ,
                [
                    (new FromTelegramUser(new ByTelegramId($this->telegramUserId, $this->connection)))->value()->pure()->raw(),
                    $this->botId->value()
                ],
                $this->connection
            ))
                ->response();
        if (!$registrationQuestion->isSuccessful()) {
            return $registrationQuestion;
        }
        if (!isset($registrationQuestion->pure()->raw()[0])) {
            return new Successful(new Emptie());
        }

        return new Successful(new Present($registrationQuestion->pure()->raw()[0]));
    }
}