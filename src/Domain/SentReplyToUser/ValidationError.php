<?php

declare(strict_types=1);

namespace RC\Domain\SentReplyToUser;

use RC\Domain\Bot\Bot;
use RC\Domain\Bot\BotToken\Impure\FromBot;
use RC\Domain\Bot\SupportBotName\Impure\FromBot as SupportBotName;
use RC\Domain\SentReplyToUser\ReplyOptions\ReplyOptions;
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
use RC\Domain\Bot\BotToken\Pure\FromImpure;
use RC\Infrastructure\TelegramBot\Method\SendMessage;
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId;

class ValidationError implements SentReplyToUser
{
    private $answerOptions;
    private $telegramUserId;
    private $bot;
    private $httpTransport;
    private $cached;

    public function __construct(ReplyOptions $answerOptions, InternalTelegramUserId $telegramUserId, Bot $bot, HttpTransport $httpTransport)
    {
        $this->answerOptions = $answerOptions;
        $this->telegramUserId = $telegramUserId;
        $this->bot = $bot;
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

    private function doValue(): ImpureValue
    {
        if (!$this->bot->value()->isSuccessful()) {
            return $this->bot->value();
        }
        $supportBotName = new SupportBotName($this->bot);
        if (!$supportBotName->value()->isSuccessful() || !$supportBotName->value()->pure()->isPresent()) {
            return $supportBotName->value();
        }

        $response =
            $this->httpTransport
                ->response(
                    new OutboundRequest(
                        new Post(),
                        new BotApiUrl(
                            new SendMessage(),
                            new FromArray(
                                array_merge(
                                    [
                                        'chat_id' => $this->telegramUserId->value(),
                                        'text' => sprintf('?? ??????????????????, ???? ???????? ???? ?????????? ?????????????? ?????????? ?? ???????? ????????????. ?????????????? ????????????????, ????????????????????, ???????? ???? ?????????????????? ????????????. ???????? ???? ???????? ???? ???????????????? ??? ???????????????? ?? @%s', $supportBotName->value()->pure()->raw()),
                                    ],
                                    empty($this->answerOptions->value())
                                        ? []
                                        :
                                            [
                                                'reply_markup' =>
                                                    json_encode([
                                                        'keyboard' => $this->answerOptions->value()->pure()->raw(),
                                                        'resize_keyboard' => true,
                                                        'one_time_keyboard' => false,
                                                    ])
                                            ]
                                )
                            ),
                            new FromImpure(new FromBot($this->bot))
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