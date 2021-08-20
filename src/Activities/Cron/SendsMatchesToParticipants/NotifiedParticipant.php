<?php

declare(strict_types=1);

namespace RC\Activities\Cron\SendsMatchesToParticipants;

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
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use RC\Infrastructure\TelegramBot\BotApiUrl;
use RC\Infrastructure\TelegramBot\Method\SendMessage;
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId;

class NotifiedParticipant implements Participant
{
    private $participantId;
    private $participantTelegramId;
    private $text;
    private $botToken;
    private $httpTransport;
    private $connection;

    public function __construct(ParticipantId $participantId, InternalTelegramUserId $participantTelegramId, string $text, BotToken $botToken, HttpTransport $httpTransport, OpenConnection $connection)
    {
        $this->participantId = $participantId;
        $this->participantTelegramId = $participantTelegramId;
        $this->text = $text;
        $this->botToken = $botToken;
        $this->httpTransport = $httpTransport;
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        $response =
            $this->httpTransport
                ->response(
                    new OutboundRequest(
                        new Post(),
                        new BotApiUrl(
                            new SendMessage(),
                            new FromArray([
                                'chat_id' => $this->participantTelegramId->value(),
                                'text' => $this->text,
                                'parse_mode' => 'MarkdownV2'
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
                'update meeting_round_pair set match_participant_contacts_sent = true where participant_id = ?',
                [$this->participantId->value()],
                $this->connection
            ))
                ->response();
    }
}