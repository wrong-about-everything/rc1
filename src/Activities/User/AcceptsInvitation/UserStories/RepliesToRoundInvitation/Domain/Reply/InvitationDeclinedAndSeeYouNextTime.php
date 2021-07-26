<?php

declare(strict_types=1);

namespace RC\Activities\User\AcceptsInvitation\UserStories\RepliesToRoundInvitation\Domain\Reply;

use RC\Domain\Bot\BotToken\Impure\BotToken;
use RC\Domain\Bot\BotToken\Pure\FromImpure;
use RC\Infrastructure\Http\Request\Method\Post;
use RC\Infrastructure\Http\Request\Outbound\OutboundRequest;
use RC\Infrastructure\Http\Request\Url\Query\FromArray;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Domain\TelegramBot\Reply\Reply;
use RC\Infrastructure\TelegramBot\BotApiUrl;
use RC\Infrastructure\TelegramBot\Method\SendMessage;
use RC\Infrastructure\TelegramBot\UserId\Pure\TelegramUserId;

class InvitationDeclinedAndSeeYouNextTime implements Reply
{
    private $telegramUserId;
    private $botToken;
    private $httpTransport;
    private $cached;

    public function __construct(TelegramUserId $telegramUserId, BotToken $botToken, HttpTransport $httpTransport)
    {
        $this->telegramUserId = $telegramUserId;
        $this->botToken = $botToken;
        $this->httpTransport = $httpTransport;
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
                                'chat_id' => $this->telegramUserId->value(),
                                'text' => 'Хорошо, тогда до следующего раза! Если хотите что-то спросить или уточнить, смело пишите на @gorgonzola_support',
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