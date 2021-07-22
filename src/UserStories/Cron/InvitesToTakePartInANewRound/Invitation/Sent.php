<?php

declare(strict_types=1);

namespace RC\UserStories\Cron\InvitesToTakePartInANewRound\Invitation;

use RC\Domain\BooleanAnswer\BooleanAnswerName\No;
use RC\Domain\BooleanAnswer\BooleanAnswerName\Yes;
use RC\Infrastructure\Http\Request\Method\Post;
use RC\Infrastructure\Http\Request\Outbound\OutboundRequest;
use RC\Infrastructure\Http\Request\Url\Query\FromArray;
use RC\Infrastructure\Http\Response\Code\Ok;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\TelegramBot\BotApiUrl;
use RC\Infrastructure\TelegramBot\BotToken\Pure\BotToken;
use RC\Infrastructure\TelegramBot\Method\SendMessage;
use RC\Infrastructure\TelegramBot\UserId\Pure\TelegramUserId;
use RC\Infrastructure\Uuid\UUID;

class Sent implements Invitation
{
    private $meetingRoundInvitation;
    private $telegramUserId;
    private $botToken;
    private $httpTransport;
    private $cached;

    public function __construct(UUID $meetingRoundInvitation, TelegramUserId $telegramUserId, BotToken $botToken, HttpTransport $httpTransport)
    {
        $this->meetingRoundInvitation = $meetingRoundInvitation;
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
        $response =
            $this->httpTransport
                ->response(
                    new OutboundRequest(
                        new Post(),
                        new BotApiUrl(
                            new SendMessage(),
                            new FromArray([
                                'chat_id' => $this->telegramUserId->value(),
                                'text' => 'Привет! Готовы участвовать во встрече на следующей неделе?',
                                'reply_markup' =>
                                    json_encode([
                                        'keyboard' => [
                                            [['text' => (new Yes())->value()]],
                                            [['text' => (new No())->value()]],
                                        ],
                                        'resize_keyboard' => true,
                                        'one_time_keyboard' => true,
                                    ])
                            ]),
                            $this->botToken
                        ),
                        [],
                        ''
                    )
                );
        if (!$response->isAvailable()) {
            return new Failed(new SilentDeclineWithDefaultUserMessage('Response from telegram is not available', []));
        }
        if (!$response->code()->equals(new Ok())) {
            return new Failed(new SilentDeclineWithDefaultUserMessage('Response from telegram is not successful', []));
        }

        return new Successful(new Present($this->meetingRoundInvitation->value()));
    }
}