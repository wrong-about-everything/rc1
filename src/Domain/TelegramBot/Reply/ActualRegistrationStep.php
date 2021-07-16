<?php

declare(strict_types=1);

namespace RC\Domain\TelegramBot\Reply;

use RC\Domain\RegistrationQuestion\CurrentRegistrationQuestion;
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
use RC\Infrastructure\TelegramBot\BotId\BotId;
use RC\Infrastructure\TelegramBot\BotToken\ByBotId;
use RC\Infrastructure\TelegramBot\BotToken\FromImpure;
use RC\Infrastructure\TelegramBot\BotToken\ImpureBotToken;
use RC\Infrastructure\TelegramBot\Method\SendMessage;
use RC\Infrastructure\TelegramBot\Reply\Reply;
use RC\Infrastructure\TelegramBot\UserId\TelegramUserId;

class ActualRegistrationStep implements Reply
{
    private $telegramUserId;
    private $botId;
    private $connection;
    private $httpTransport;

    public function __construct(TelegramUserId $telegramUserId, BotId $botId, OpenConnection $connection, HttpTransport $httpTransport)
    {
        $this->telegramUserId = $telegramUserId;
        $this->botId = $botId;
        $this->connection = $connection;
        $this->httpTransport = $httpTransport;
    }

    public function value(): ImpureValue
    {
        $botToken = new ByBotId($this->botId, $this->connection);
        if (!$botToken->value()->isSuccessful() || !$botToken->value()->pure()->isPresent()) {
            return $botToken->value();
        }

        $currentRegistrationQuestionResponse = new CurrentRegistrationQuestion($this->telegramUserId, $this->botId, $this->connection);
        if (!$currentRegistrationQuestionResponse->value()->isSuccessful()) {
            return $currentRegistrationQuestionResponse->value();
        }
        if (!$currentRegistrationQuestionResponse->value()->pure()->isPresent()) {
            return $this->userIsAllSet($botToken);
        }

        $response =
            $this->httpTransport
                ->response(
                    new OutboundRequest(
                        new Post(),
                        new BotApiUrl(
                            new SendMessage(),
                            new FromArray([
                                'chat_id' => $this->telegramUserId->value(),
                                'text' => $currentRegistrationQuestionResponse->value()->pure()->raw()['text'],
                            ]),
                            new FromImpure($botToken)
                        ),
                        [],
                        ''
                    )
                );
        if (!$response->isAvailable()) {
            return new Failed(new SilentDeclineWithDefaultUserMessage('Response from telegram is not available', []));
        }
        // @todo: validate telegram response!

        return new Successful(new Emptie());
    }

    private function userIsAllSet(ImpureBotToken $botToken)
    {
        $response =
            $this->httpTransport
                ->response(
                    new OutboundRequest(
                        new Post(),
                        new BotApiUrl(
                            new SendMessage(),
                            new FromArray([
                                'chat_id' => $this->telegramUserId->value(),
                                'text' => 'Вы уже заполнили всю информацию о себе. <Дать инфу, зареган ли чувак на какое-то событие или нет. Если нет -- написать что пинганём>️',
                            ]),
                            new FromImpure($botToken)
                        ),
                        [],
                        ''
                    )
                );
        if (!$response->isAvailable()) {
            return new Failed(new SilentDeclineWithDefaultUserMessage('Response from telegram is not available', []));
        }

        return new Successful(new Emptie());
    }
}