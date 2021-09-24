<?php

declare(strict_types=1);

namespace RC\Domain\BotUser\ReadModel;

use RC\Domain\BotUser\Id\Impure\BotUserId;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;

class ById implements BotUser
{
    private $botUserId;
    private $connection;
    private $cached;

    public function __construct(BotUserId $botUserId, OpenConnection $connection)
    {
        $this->botUserId = $botUserId;
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
        if (!$this->botUserId->value()->isSuccessful()) {
            return $this->botUserId->value();
        }

        $response =
            (new Selecting(
                'select bu.* from bot_user bu where bu.id = ?',
                [$this->botUserId->value()->pure()->raw()],
                $this->connection
            ))
                ->response();
        if (!$response->isSuccessful()) {
            return $response;
        }
        if (!isset($response->pure()->raw()[0])) {
            return new Successful(new Emptie());
        }

        return new Successful(new Present($response->pure()->raw()[0]));
    }
}