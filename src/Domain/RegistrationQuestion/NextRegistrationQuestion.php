<?php

declare(strict_types=1);

namespace RC\Domain\RegistrationQuestion;

use RC\Domain\User\RegisteredInBot;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Domain\Bot\BotId\BotId;
use RC\Infrastructure\TelegramBot\UserId\Pure\TelegramUserId;

class NextRegistrationQuestion implements RegistrationQuestion
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
        $registrationQuestion =
            (new Selecting(
                <<<q
        select rq.*
        from registration_question rq
            left join user_registration_progress urp on urp.registration_question_id = rq.id and urp.user_id = ?
        where urp.registration_question_id is null
        order by rq.ordinal_number asc
        limit 1
        q
                ,
                [(new RegisteredInBot($this->telegramUserId, $this->botId, $this->connection))->value()->pure()->raw()['id']],
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