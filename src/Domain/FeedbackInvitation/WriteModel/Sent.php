<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackInvitation\WriteModel;

use RC\Domain\BooleanAnswer\BooleanAnswerName\No;
use RC\Domain\BooleanAnswer\BooleanAnswerName\Yes;
use RC\Domain\Bot\Bot;
use RC\Domain\Bot\BotToken\Impure\FromBot as TokenFromBot;
use RC\Domain\Bot\BotToken\Pure\FromImpure;
use RC\Domain\FeedbackInvitation\FeedbackInvitationId\Pure\FeedbackInvitationId;
use RC\Domain\FeedbackInvitation\Status\Pure\ErrorDuringSending;
use RC\Domain\FeedbackInvitation\Status\Pure\Sent as SentStatus;
use RC\Domain\FeedbackInvitation\Status\Pure\Status;
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
use RC\Infrastructure\Logging\LogItem\ErrorMessage;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use RC\Infrastructure\TelegramBot\BotApiUrl;
use RC\Infrastructure\TelegramBot\Method\SendMessage;
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId;

class Sent implements FeedbackInvitation
{
    private $feedbackInvitationId;
    private $telegramUserId;
    private $bot;
    private $httpTransport;
    private $connection;
    private $logs;
    private $cached;

    public function __construct(FeedbackInvitationId $feedbackInvitationId, InternalTelegramUserId $telegramUserId, Bot $bot, HttpTransport $httpTransport, OpenConnection $connection, Logs $logs)
    {
        $this->feedbackInvitationId = $feedbackInvitationId;
        $this->telegramUserId = $telegramUserId;
        $this->bot = $bot;
        $this->httpTransport = $httpTransport;
        $this->connection = $connection;
        $this->logs = $logs;
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
        $response =
            $this->httpTransport
                ->response(
                    new OutboundRequest(
                        new Post(),
                        new BotApiUrl(
                            new SendMessage(),
                            new FromArray([
                                'chat_id' => $this->telegramUserId->value(),
                                'text' => 'Привет! Удалось пообщаться с парой, которую я вам присылал в прошлый раз?',
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
                            new FromImpure(new TokenFromBot($this->bot))
                        ),
                        [],
                        ''
                    )
                );
        if (!$response->isAvailable() || !$response->code()->equals(new Ok())) {
            $this->updateStatus(new ErrorDuringSending());
            $this->logs->receive(new ErrorMessage('Error during invitation sending!'));
            return new Failed(new SilentDeclineWithDefaultUserMessage('Response from telegram is not successful', []));
        }

        $this->updateStatus(new SentStatus());

        $this->logs->receive(new InformationMessage('Feedback invitation was sent successfully'));

        return new Successful(new Present($this->feedbackInvitationId->value()));
    }

    private function updateStatus(Status $status)
    {
        return
            (new SingleMutating(
                <<<q
update feedback_invitation
set status = ?
where id = ?
q
                ,
                [$status->value(), $this->feedbackInvitationId->value()],
                $this->connection
            ))
                ->response();
    }
}