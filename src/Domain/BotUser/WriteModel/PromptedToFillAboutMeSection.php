<?php

declare(strict_types=1);

namespace RC\Domain\BotUser\WriteModel;

use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotToken\Impure\ByBotId;
use RC\Domain\Bot\BotToken\Pure\FromImpure;
use RC\Domain\BotUser\Id\Impure\FromReadModelBotUser;
use RC\Domain\BotUser\ReadModel\ByInternalTelegramUserIdAndBotId;
use RC\Infrastructure\Http\Request\Method\Post;
use RC\Infrastructure\Http\Request\Outbound\OutboundRequest;
use RC\Infrastructure\Http\Request\Url\Query\FromArray;
use RC\Infrastructure\Http\Response\Code\Ok;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\TelegramBot\BotApiUrl;
use RC\Infrastructure\TelegramBot\Method\SendMessage;
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId;

class PromptedToFillAboutMeSection implements BotUser
{
    private $internalTelegramUserId;
    private $botId;
    private $transport;
    private $connection;

    private $cached;

    public function __construct(InternalTelegramUserId $internalTelegramUserId, BotId $botId, HttpTransport $transport, OpenConnection $connection)
    {
        $this->internalTelegramUserId = $internalTelegramUserId;
        $this->botId = $botId;
        $this->transport = $transport;
        $this->connection = $connection;

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
        $token = new ByBotId($this->botId, $this->connection);
        if (!$token->value()->isSuccessful()) {
            return $token->value();
        }

        $response =
            $this->transport
                ->response(
                    new OutboundRequest(
                        new Post(),
                        new BotApiUrl(
                            new SendMessage(),
                            new FromArray([
                                'chat_id' => $this->internalTelegramUserId->value(),
                                'text' =>
                                    <<<text
Привет! Чтобы закончить регистрацию и участвовать в нетворкинге, напишите, пожалуйста, пару слов о себе для вашего собеседника.

Например: Сергей, 27, работаю в "Рога и копыта", выстраиваю процесс доставки еды. Развожу хомячков.
text
                            ]),
                            new FromImpure($token)
                        ),
                        [],
                        ''
                    )
                );
        if (!$response->isAvailable() || !$response->code()->equals(new Ok())) {
            return new Failed(new SilentDeclineWithDefaultUserMessage('Response from telegram is not available', []));
        }

        return
            (new FromReadModelBotUser(
                new ByInternalTelegramUserIdAndBotId(
                    $this->internalTelegramUserId,
                    $this->botId,
                    $this->connection
                )
            ))
                ->value();
    }
}