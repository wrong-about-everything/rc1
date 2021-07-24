<?php

declare(strict_types=1);

namespace RC\Activities\User\AcceptsInvitation\UserStories\RepliesToRoundInvitation\Domain;

use RC\Domain\BooleanAnswer\BooleanAnswerName\FromUserMessage;
use RC\Domain\BooleanAnswer\BooleanAnswerName\No;
use RC\Domain\Bot\BotId\FromUuid;
use RC\Domain\Bot\ById;
use RC\Domain\RoundInvitation\InvitationId\Impure\FromInvitation;
use RC\Domain\RoundInvitation\ReadModel\LatestByTelegramUserIdAndBotId;
use RC\Domain\RoundInvitation\WriteModel\Declined;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromParsedTelegramMessage as TelegramUserIdFromParsedTelegramMessage;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromParsedTelegramMessage as UserIdFromParsedTelegramMessage;
use RC\Infrastructure\TelegramBot\UserMessage\Impure\FromPure;
use RC\Infrastructure\TelegramBot\UserMessage\Impure\NonSuccessful;
use RC\Infrastructure\TelegramBot\UserMessage\Pure\FromParsedTelegramMessage;
use RC\Infrastructure\TelegramBot\UserMessage\Impure\UserMessage;
use RC\Infrastructure\Uuid\FromString as UuidFromString;

class SavedReplyToRoundInvitation implements UserMessage
{
    private $message;
    private $botId;
    private $connection;
    private $cached;

    public function __construct(array $message, string $botId, OpenConnection $connection)
    {
        $this->message = $message;
        $this->botId = $botId;
        $this->connection = $connection;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        return $this->cached()->value();
    }

    public function exists(): ImpureValue
    {
        return $this->cached()->exists();
    }

    private function cached()
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doCached();
        }

        return $this->cached;
    }

    private function doCached()
    {
        if ((new FromUserMessage(new FromParsedTelegramMessage($this->message)))->equals(new No())) {
            $declinedInvitationValue =
                (new Declined(
                    new FromInvitation(
                        new LatestByTelegramUserIdAndBotId(
                            new UserIdFromParsedTelegramMessage($this->message),
                            new FromUuid(new UuidFromString($this->botId)),
                            $this->connection
                        )
                    ),
                    $this->connection
                ))
                    ->value();
            if (!$declinedInvitationValue->isSuccessful()) {
                return new NonSuccessful($declinedInvitationValue);
            }

            return new FromPure(new FromParsedTelegramMessage($this->message));
        }
    }
}