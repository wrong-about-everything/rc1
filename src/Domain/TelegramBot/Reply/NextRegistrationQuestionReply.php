<?php

declare(strict_types=1);

namespace RC\Domain\TelegramBot\Reply;

use RC\Domain\AvailablePositions\ByBotId as AvailablePositions;
use RC\Domain\AvailableExperiences\ByBotId as AvailableExperiences;
use RC\Domain\Experience\Pure\FromInteger as ExperienceFromInteger;
use RC\Domain\ExperienceName\FromExperience;
use RC\Domain\Position\Pure\FromInteger;
use RC\Domain\PositionName\FromPosition;
use RC\Domain\RegistrationQuestion\NextRegistrationQuestion;
use RC\Domain\RegistrationQuestion\RegistrationQuestion;
use RC\Domain\UserProfileRecordType\Impure\FromPure;
use RC\Domain\UserProfileRecordType\Impure\FromRegistrationQuestion;
use RC\Domain\UserProfileRecordType\Pure\Experience;
use RC\Domain\UserProfileRecordType\Pure\Position;
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
use RC\Domain\BotId\BotId;
use RC\Infrastructure\TelegramBot\BotToken\ByBotId;
use RC\Infrastructure\TelegramBot\BotToken\FromImpure;
use RC\Infrastructure\TelegramBot\BotToken\ImpureBotToken;
use RC\Infrastructure\TelegramBot\Method\SendMessage;
use RC\Infrastructure\TelegramBot\Reply\Reply;
use RC\Infrastructure\TelegramBot\UserId\Pure\TelegramUserId;

// @todo: Добавить логирование при любом завершении скрипта
class NextRegistrationQuestionReply implements Reply
{
    private $telegramUserId;
    private $botId;
    private $connection;
    private $httpTransport;

    public function __construct(TelegramUserId $telegramUserId, BotId $botId, OpenConnection $connection, HttpTransport $httpTransport)
    {
        $this->telegramUserId = $telegramUserId;
        $this->botId = $botId;
        $this->connection = $connection;
        $this->httpTransport = $httpTransport;
    }

    public function value(): ImpureValue
    {
        $nextRegistrationQuestion = new NextRegistrationQuestion($this->telegramUserId, $this->botId, $this->connection);
        if (!$nextRegistrationQuestion->value()->isSuccessful()) {
            return $nextRegistrationQuestion->value();
        }

        $botToken = new ByBotId($this->botId, $this->connection);
        if (!$botToken->value()->isSuccessful() || !$botToken->value()->pure()->isPresent()) {
            return $botToken->value();
        }

        $response = $this->ask($nextRegistrationQuestion, $botToken);
        if (!$response->isAvailable()) {
            return new Failed(new SilentDeclineWithDefaultUserMessage('Response from telegram is not available', []));
        }
        // @todo: validate telegram response!

        return new Successful(new Emptie());
    }

    private function ask(RegistrationQuestion $nextRegistrationQuestion, ImpureBotToken $botToken)
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
                                        'text' => $nextRegistrationQuestion->value()->pure()->raw()['text'],
                                    ],
                                    $this->replyMarkup($nextRegistrationQuestion)
                                )
                            ),
                            new FromImpure($botToken)
                        ),
                        [],
                        ''
                    )
                );
    }

    private function replyMarkup(RegistrationQuestion $nextRegistrationQuestion)
    {
        $answerOptions = $this->answerOptions($nextRegistrationQuestion);

        if (empty($answerOptions)) {
            return [];
        }

        return [
            'reply_markup' =>
                json_encode([
                    'keyboard' => $answerOptions,
                    'resize_keyboard' => true,
                    'one_time_keyboard' => true,
                ])
        ];
    }

    private function answerOptions(RegistrationQuestion $currentRegistrationQuestion)
    {
        if ((new FromRegistrationQuestion($currentRegistrationQuestion))->equals(new FromPure(new Position()))) {
            return
                array_map(
                    function (int $position) {
                        return [['text' => (new FromPosition(new FromInteger($position)))->value()]];
                    },
                    (new AvailablePositions($this->botId, $this->connection))->value()->pure()->raw()
                );
        } elseif ((new FromRegistrationQuestion($currentRegistrationQuestion))->equals(new FromPure(new Experience()))) {
            return
                array_map(
                    function (int $experience) {
                        return [['text' => (new FromExperience(new ExperienceFromInteger($experience)))->value()]];
                    },
                    (new AvailableExperiences($this->botId, $this->connection))->value()->pure()->raw()
                );
        }

        return [];
    }
}