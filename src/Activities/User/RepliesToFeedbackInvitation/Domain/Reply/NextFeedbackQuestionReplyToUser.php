<?php

declare(strict_types=1);

namespace RC\Activities\User\RepliesToFeedbackInvitation\Domain\Reply;

use RC\Domain\FeedbackQuestion\FeedbackQuestion;
use RC\Infrastructure\Http\Request\Method\Post;
use RC\Infrastructure\Http\Request\Outbound\OutboundRequest;
use RC\Infrastructure\Http\Request\Url\Query\FromArray;
use RC\Infrastructure\Http\Response\Code\Ok;
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
use RC\Domain\SentReplyToUser\SentReplyToUser;
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId;

class NextFeedbackQuestionReplyToUser implements SentReplyToUser
{
    private $feedbackQuestion;
    private $telegramUserId;
    private $botId;
    private $connection;
    private $httpTransport;

    public function __construct(FeedbackQuestion $feedbackInvitation, InternalTelegramUserId $telegramUserId, BotId $botId, OpenConnection $connection, HttpTransport $httpTransport)
    {
        $this->feedbackQuestion = $feedbackInvitation;
        $this->telegramUserId = $telegramUserId;
        $this->botId = $botId;
        $this->connection = $connection;
        $this->httpTransport = $httpTransport;
    }

    public function value(): ImpureValue
    {
        if (!$this->feedbackQuestion->value()->isSuccessful()) {
            return $this->feedbackQuestion->value();
        }

        $botToken = new ByBotId($this->botId, $this->connection);
        if (!$botToken->value()->isSuccessful() || !$botToken->value()->pure()->isPresent()) {
            return $botToken->value();
        }

        $response = $this->ask($botToken);
        if (!$response->isAvailable() || !$response->code()->equals(new Ok())) {
            return new Failed(new SilentDeclineWithDefaultUserMessage('Response from telegram is not successful', []));
        }

        return new Successful(new Emptie());
    }

    private function ask(BotToken $botToken)
    {
        return
            $this->httpTransport
                ->response(
                    new OutboundRequest(
                        new Post(),
                        new BotApiUrl(
                            new SendMessage(),
                            new FromArray([
                                'chat_id' => $this->telegramUserId->value(),
                                'text' => $this->feedbackQuestion->value()->pure()->raw()['text'],
                            ]),
                            new FromImpure($botToken)
                        ),
                        [],
                        ''
                    )
                );
    }
}