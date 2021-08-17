<?php

declare(strict_types=1);

namespace RC\Activities\User\RepliesToRoundInvitation\Domain\Reply;

use RC\Domain\AnswerOptions\FromRoundRegistrationQuestion as AnswerOptionsFromRoundRegistrationQuestion;
use RC\Domain\RoundInvitation\InvitationId\Impure\InvitationId;
use RC\Domain\RoundRegistrationQuestion\NextRoundRegistrationQuestion;
use RC\Domain\RoundRegistrationQuestion\RoundRegistrationQuestion;
use RC\Infrastructure\Http\Request\Method\Post;
use RC\Infrastructure\Http\Request\Outbound\OutboundRequest;
use RC\Infrastructure\Http\Request\Url\Query\FromArray;
use RC\Infrastructure\Http\Transport\HttpTransport;
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
use RC\Domain\Bot\BotToken\Impure\BotToken;
use RC\Infrastructure\TelegramBot\Method\SendMessage;
use RC\Domain\TelegramBot\Reply\Text\ReplyToUser;
use RC\Infrastructure\TelegramBot\UserId\Pure\TelegramUserId;

class NextRoundRegistrationQuestionReplyToUser implements ReplyToUser
{
    private $invitationId;
    private $telegramUserId;
    private $botId;
    private $connection;
    private $httpTransport;

    public function __construct(InvitationId $invitationId, TelegramUserId $telegramUserId, BotId $botId, OpenConnection $connection, HttpTransport $httpTransport)
    {
        $this->invitationId = $invitationId;
        $this->telegramUserId = $telegramUserId;
        $this->botId = $botId;
        $this->connection = $connection;
        $this->httpTransport = $httpTransport;
    }

    public function value(): ImpureValue
    {
        $nextRoundRegistrationQuestion = new NextRoundRegistrationQuestion($this->invitationId, $this->connection);
        if (!$nextRoundRegistrationQuestion->value()->isSuccessful()) {
            return $nextRoundRegistrationQuestion->value();
        }

        $botToken = new ByBotId($this->botId, $this->connection);
        if (!$botToken->value()->isSuccessful() || !$botToken->value()->pure()->isPresent()) {
            return $botToken->value();
        }

        $response = $this->ask($nextRoundRegistrationQuestion, $botToken);
        if (!$response->isAvailable()) {
            return new Failed(new SilentDeclineWithDefaultUserMessage('Response from telegram is not available', []));
        }
        // @todo: use the single infrastructure Reply class with retries

        return new Successful(new Emptie());
    }

    private function ask(RoundRegistrationQuestion $nextRoundRegistrationQuestion, BotToken $botToken)
    {
        return
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
                                        'text' => $nextRoundRegistrationQuestion->value()->pure()->raw()['text'],
                                    ],
                                    $this->replyMarkup($nextRoundRegistrationQuestion)
                                )
                            ),
                            new FromImpure($botToken)
                        ),
                        [],
                        ''
                    )
                );
    }

    private function replyMarkup(RoundRegistrationQuestion $nextRegistrationQuestion)
    {
        $answerOptions = new AnswerOptionsFromRoundRegistrationQuestion($nextRegistrationQuestion, $this->invitationId, $this->connection);

        if (empty($answerOptions->value()->pure()->raw())) {
            return [];
        }

        return [
            'reply_markup' =>
                json_encode([
                    'keyboard' => $answerOptions->value()->pure()->raw(),
                    'resize_keyboard' => true,
                    'one_time_keyboard' => true,
                ])
        ];
    }
}