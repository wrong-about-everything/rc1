<?php

declare(strict_types=1);

namespace RC\Activities\User\RegistersInBot\UserStories\AnswersRegistrationQuestion\Domain\Reply;

use DateTime;
use IntlDateFormatter;
use IntlTimeZone;
use Meringue\ISO8601DateTime\FromISO8601;
use Meringue\ISO8601DateTime\TheBeginningOfADay;
use Meringue\ISO8601DateTime\Tomorrow;
use Meringue\Timeline\Point\Now;
use RC\Domain\BooleanAnswer\BooleanAnswerName\No;
use RC\Domain\BooleanAnswer\BooleanAnswerName\Yes;
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
use RC\Domain\TelegramBot\Reply\Reply;
use RC\Infrastructure\TelegramBot\UserId\Pure\TelegramUserId;

class MeetingRoundInvitation implements Reply
{
    private $meetingRound;
    private $telegramUserId;
    private $botId;
    private $connection;
    private $httpTransport;

    public function __construct(MeetingRound $meetingRound, TelegramUserId $telegramUserId, BotId $botId, OpenConnection $connection, HttpTransport $httpTransport)
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
                                        'Спасибо за ответы! Кстати, у нас уже намечаются встречи, давайте может сразу запишу вас? Пришлю вам пару %s, а по времени уже вдвоём договоритесь. Ну что, готовы?',
                                        $this->meetingRoundHumanReadableStartDate()
                                    ),
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