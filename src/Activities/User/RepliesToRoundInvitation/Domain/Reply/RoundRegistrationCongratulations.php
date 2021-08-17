<?php

declare(strict_types=1);

namespace RC\Activities\User\RepliesToRoundInvitation\Domain\Reply;

use Meringue\Timeline\Point\Now;
use RC\Domain\MeetingRound\ReadModel\MeetingRound;
use RC\Domain\MeetingRound\StartDateTime;
use RC\Infrastructure\Http\Request\Method\Post;
use RC\Infrastructure\Http\Request\Outbound\OutboundRequest;
use RC\Infrastructure\Http\Request\Url\Query\FromArray;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\HumanReadableDateTime\AccusativeDateTimeInMoscowTimeZone;
use RC\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\TelegramBot\BotApiUrl;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotToken\Impure\ByBotId;
use RC\Domain\Bot\BotToken\Pure\FromImpure;
use RC\Infrastructure\TelegramBot\Method\SendMessage;
use RC\Domain\TelegramBot\Reply\Text\ReplyToUser;
use RC\Infrastructure\TelegramBot\UserId\Pure\TelegramUserId;

class RoundRegistrationCongratulations implements ReplyToUser
{
    private $telegramUserId;
    private $botId;
    private $meetingRound;
    private $connection;
    private $httpTransport;

    public function __construct(TelegramUserId $telegramUserId, BotId $botId, MeetingRound $meetingRound, OpenConnection $connection, HttpTransport $httpTransport)
    {
        $this->telegramUserId = $telegramUserId;
        $this->botId = $botId;
        $this->meetingRound = $meetingRound;
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
                                        'Поздравляю, вы зарегистрировались! %s пришлю вам пару для разговора. Если хотите что-то спросить или уточнить, смело пишите на @gorgonzola_support_bot',
                                        $this->ucfirst((new AccusativeDateTimeInMoscowTimeZone(new Now(), new StartDateTime($this->meetingRound)))->value()),
                                    ),
                                'reply_markup' => json_encode(['remove_keyboard' => true])
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

    private function ucfirst(string $s)
    {
        return mb_strtoupper(mb_substr($s, 0, 1)) . mb_substr($s, 1);
    }
}