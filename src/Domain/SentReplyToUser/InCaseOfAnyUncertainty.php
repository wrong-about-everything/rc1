<?php

declare(strict_types=1);

namespace RC\Domain\SentReplyToUser;

use RC\Domain\Bot\BotToken\Impure\FromBot;
use RC\Domain\Bot\ById;
use RC\Domain\Bot\SupportBotName\Impure\FromBot as SupportBotName;
use RC\Infrastructure\Http\Request\Method\Post;
use RC\Infrastructure\Http\Request\Outbound\OutboundRequest;
use RC\Infrastructure\Http\Request\Url\Query\FromArray;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\TelegramBot\BotApiUrl;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotToken\Pure\FromImpure;
use RC\Infrastructure\TelegramBot\Method\SendMessage;
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId;

class InCaseOfAnyUncertainty implements SentReplyToUser
{
    private $telegramUserId;
    private $botId;
    private $connection;
    private $httpTransport;

    public function __construct(InternalTelegramUserId $telegramUserId, BotId $botId, OpenConnection $connection, HttpTransport $httpTransport)
    {
        $this->telegramUserId = $telegramUserId;
        $this->botId = $botId;
        $this->connection = $connection;
        $this->httpTransport = $httpTransport;
    }

    public function value(): ImpureValue
    {
        $bot = new ById($this->botId, $this->connection);
        $botToken = new FromBot($bot);
        if (!$botToken->value()->isSuccessful() || !$botToken->value()->pure()->isPresent()) {
            return $botToken->value();
        }
        $supportBotName = new SupportBotName($bot);
        if (!$supportBotName->value()->isSuccessful() || !$supportBotName->value()->pure()->isPresent()) {
            return $supportBotName->value();
        }

        $telegramResponse =
            $this->httpTransport
                ->response(
                    new OutboundRequest(
                        new Post(),
                        new BotApiUrl(
                            new SendMessage(),
                            new FromArray([
                                'chat_id' => $this->telegramUserId->value(),
                                'text' => sprintf('Хотите что-то уточнить? Смело пишите на @%s!', $supportBotName->value()->pure()->raw()),
                            ]),
                            new FromImpure($botToken)
                        ),
                        [],
                        ''
                    )
                );

        if (!$telegramResponse->isAvailable()) {
            return new Failed(new SilentDeclineWithDefaultUserMessage('Response from telegram is not available', []));
        }

        return new Successful(new Emptie());
    }
}