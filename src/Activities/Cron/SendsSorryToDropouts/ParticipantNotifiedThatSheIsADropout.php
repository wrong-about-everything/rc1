<?php

declare(strict_types=1);

namespace RC\Activities\Cron\SendsSorryToDropouts;

use RC\Domain\Bot\BotToken\Impure\BotToken;
use RC\Domain\Bot\BotToken\Pure\FromImpure;
use RC\Domain\Participant\ParticipantId\Pure\ParticipantId;
use RC\Domain\Participant\WriteModel\Participant;
use RC\Infrastructure\Http\Request\Method\Post;
use RC\Infrastructure\Http\Request\Outbound\OutboundRequest;
use RC\Infrastructure\Http\Request\Url\Query\FromArray;
use RC\Infrastructure\Http\Response\Code\Ok;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use RC\Infrastructure\TelegramBot\BotApiUrl;
use RC\Infrastructure\TelegramBot\Method\SendMessage;
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId;

class ParticipantNotifiedThatSheIsADropout implements Participant
{
    private $participantId;
    private $dropoutTelegramId;
    private $botToken;
    private $transport;
    private $connection;

    public function __construct(ParticipantId $participantId, InternalTelegramUserId $dropoutTelegramId, BotToken $botToken, HttpTransport $transport, OpenConnection $connection)
    {
        $this->participantId = $participantId;
        $this->dropoutTelegramId = $dropoutTelegramId;
        $this->botToken = $botToken;
        $this->transport = $transport;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        $response =
            $this->transport
                ->response(
                    new OutboundRequest(
                        new Post(),
                        new BotApiUrl(
                            new SendMessage(),
                            new FromArray([
                                'chat_id' => $this->dropoutTelegramId->value(),
                                'text' =>
                                    <<<text
К сожалению, в этот раз у нас нечетное количество участников и вам не повезло получить собеседника. 

Бот пришлет вам приглашение на следующий раунд встреч — участвуйте дальше, и мы обязательно подберем вам пару для разговора.
text
                            ]),
                            new FromImpure($this->botToken)
                        ),
                        [],
                        ''
                    )
                );
        if (!$response->isAvailable() || !$response->code()->equals(new Ok())) {
            return new Failed(new SilentDeclineWithDefaultUserMessage('Response from telegram is not available', []));
        }

        return
            (new SingleMutating(
                'update meeting_round_dropout set sorry_is_sent = true where dropout_participant_id = ?',
                [$this->participantId->value()],
                $this->connection
            ))
                ->response();

    }
}