<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\Reply;

use RC\Infrastructure\Http\Request\Method\Post;
use RC\Infrastructure\Http\Request\Outbound\OutboundRequest;
use RC\Infrastructure\Http\Request\Url\Query\FromArray;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\TelegramBot\BotApiUrl;
use RC\Infrastructure\TelegramBot\BotToken\FromImpure;
use RC\Infrastructure\TelegramBot\BotToken\ImpureBotToken;
use RC\Infrastructure\TelegramBot\ChatId\ChatId;
use RC\Infrastructure\TelegramBot\Method\SendMessage;

// @todo: завести супортового бота!
class Sorry implements Reply
{
    private $chatId;
    private $botToken;
    private $httpTransport;

    public function __construct(ChatId $chatId, ImpureBotToken $botToken, HttpTransport $httpTransport)
    {
        $this->chatId = $chatId;
        $this->botToken = $botToken;
        $this->httpTransport = $httpTransport;
    }

    public function value(): ImpureValue
    {
        if (!$this->botToken->value()->isSuccessful()) {
            return $this->botToken->value();
        }

        $response =
            $this->httpTransport
                ->response(
                    new OutboundRequest(
                        new Post(),
                        new BotApiUrl(
                            new SendMessage(),
                            new FromArray([
                                'chat_id' => $this->chatId->value(),
                                'text' => 'Простите, у нас что-то сломалось. Попробуйте ещё пару раз, и если не заработает -- напишите, пожалуйста, в @gorgonzola_support_bot',
                            ]),
                            new FromImpure($this->botToken)
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