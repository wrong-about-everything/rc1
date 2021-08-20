<?php

declare(strict_types=1);

namespace RC\Activities\User\RegistersInBot\UserStories\Domain\Reply;

use Meringue\Timeline\Point\Now;
use RC\Domain\BooleanAnswer\BooleanAnswerName\NoMaybeNextTime;
use RC\Domain\BooleanAnswer\BooleanAnswerName\Sure;
use RC\Domain\MeetingRound\ReadModel\MeetingRound;
use RC\Domain\MeetingRound\StartDateTime;
use RC\Domain\RoundInvitation\WriteModel\CreatedSent;
use RC\Infrastructure\Http\Request\Method\Post;
use RC\Infrastructure\Http\Request\Outbound\OutboundRequest;
use RC\Infrastructure\Http\Request\Url\Query\FromArray;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\HumanReadableDateTime\AccusativeDateTimeInMoscowTimeZone;
use RC\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\TelegramBot\BotApiUrl;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotToken\Impure\ByBotId;
use RC\Domain\Bot\BotToken\Pure\FromImpure;
use RC\Infrastructure\TelegramBot\Method\SendMessage;
use RC\Domain\SentReplyToUser\SentReplyToUser;
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId;

class MeetingRoundInvitation implements SentReplyToUser
{
    private $meetingRound;
    private $telegramUserId;
    private $botId;
    private $connection;
    private $httpTransport;

    public function __construct(MeetingRound $meetingRound, InternalTelegramUserId $telegramUserId, BotId $botId, OpenConnection $connection, HttpTransport $httpTransport)
    {
        $this->meetingRound = $meetingRound;
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

        $telegramResponse =
            $this->httpTransport
                ->response(
                    new OutboundRequest(
                        new Post(),
                        new BotApiUrl(
                            new SendMessage(),
                            new FromArray([
                                'chat_id' => $this->telegramUserId->value(),
                                'text' =>
                                    sprintf(
                                        <<<q
Спасибо за ответы!

У нас уже намечаются встречи, готовы поучаствовать? Пришлю вам пару %s, а когда и как встретиться, онлайн или оффлайн, договоритесь между собой.
q
                                        ,
                                        $this->meetingRoundHumanReadableStartDate()
                                    ),
                                'reply_markup' =>
                                    json_encode([
                                        'keyboard' => [
                                            [['text' => (new Sure())->value()]],
                                            [['text' => (new NoMaybeNextTime())->value()]],
                                        ],
                                        'resize_keyboard' => true,
                                        'one_time_keyboard' => true,
                                    ])
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

        return (new CreatedSent($this->telegramUserId, $this->meetingRound, $this->connection))->value();
    }

    private function meetingRoundHumanReadableStartDate()
    {
        return (new AccusativeDateTimeInMoscowTimeZone(new Now(), new StartDateTime($this->meetingRound)))->value();
    }
}