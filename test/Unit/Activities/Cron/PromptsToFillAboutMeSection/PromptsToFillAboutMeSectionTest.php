<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Activities\Cron\PromptsToFillAboutMeSection;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RC\Activities\Cron\PromptsToFillAboutMeSection\PromptsToFillAboutMeSection;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotId\FromUuid;
use RC\Domain\BotUser\UserStatus\Pure\Registered;
use RC\Domain\BotUser\UserStatus\Pure\RegistrationIsInProgress;
use RC\Domain\Experience\ExperienceId\Pure\LessThanAYear;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Domain\Position\PositionId\Pure\ProductManager;
use RC\Infrastructure\Http\Transport\Indifferent;
use RC\Infrastructure\Logging\Logs\DevNull;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromInteger;
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId;
use RC\Infrastructure\Uuid\FromString;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Stub\Table\Bot;
use RC\Tests\Infrastructure\Stub\Table\BotUser;
use RC\Tests\Infrastructure\Stub\Table\TelegramUser;

class PromptsToFillAboutMeSectionTest extends TestCase
{
    public function test()
    {
        $connection = new ApplicationConnection();
        $this->seedBot($this->botId(), $connection);
        $this->createNonRegisteredUserWithFilledExperienceAndEmptyAboutMe($this->firstInternalTelegramUserId(), $this->botId(), $connection);
        $this->createNonRegisteredUserWithEmptyPositionAndEmptyExperience($this->secondInternalTelegramUserId(), $this->botId(), $connection);
        $this->createRegisteredUser($this->thirdInternalTelegramUserId(), $this->botId(), $connection);
        $this->createNonRegisteredUserWithFilledExperienceAndEmptyAboutMe($this->fourthInternalTelegramUserId(), $this->botId(), $connection);

        $transport = new Indifferent();
        $response =
            (new PromptsToFillAboutMeSection(
                $this->botId(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(2, $transport->sentRequests());
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function botId(): BotId
    {
        return new FromUuid(new FromString('8a998d04-91aa-4aed-bf85-c757e35df4fc'));
    }

    private function firstInternalTelegramUserId(): InternalTelegramUserId
    {
        return new FromInteger(1);
    }

    private function secondInternalTelegramUserId(): InternalTelegramUserId
    {
        return new FromInteger(2);
    }

    private function thirdInternalTelegramUserId(): InternalTelegramUserId
    {
        return new FromInteger(3);
    }

    private function fourthInternalTelegramUserId(): InternalTelegramUserId
    {
        return new FromInteger(4);
    }

    private function seedBot(BotId $botId, OpenConnection $connection)
    {
        (new Bot($connection))
            ->insert([
                ['id' => $botId->value(),]
            ]);
    }

    private function createNonRegisteredUserWithFilledExperienceAndEmptyAboutMe(InternalTelegramUserId $internalTelegramUserId, BotId $botId, OpenConnection $connection)
    {
        $telegramUserId = Uuid::uuid4()->toString();
        (new TelegramUser($connection))
            ->insert([
                ['id' => $telegramUserId, 'telegram_id' => $internalTelegramUserId->value()]
            ]);
        (new BotUser($connection))
            ->insert([
                [
                    'user_id' => $telegramUserId,
                    'bot_id' => $botId->value(),
                    'status' => (new RegistrationIsInProgress())->value(),
                    'position' => (new ProductManager())->value(),
                    'experience' => (new LessThanAYear())->value(),
                    'about' => null
                ]
            ]);
    }

    private function createNonRegisteredUserWithEmptyPositionAndEmptyExperience(InternalTelegramUserId $internalTelegramUserId, BotId $botId, OpenConnection $connection)
    {
        $telegramUserId = Uuid::uuid4()->toString();
        (new TelegramUser($connection))
            ->insert([
                ['id' => $telegramUserId, 'telegram_id' => $internalTelegramUserId->value()]
            ]);
        (new BotUser($connection))
            ->insert([
                [
                    'user_id' => $telegramUserId,
                    'bot_id' => $botId->value(),
                    'status' => (new RegistrationIsInProgress())->value(),
                    'position' => null,
                    'experience' => null,
                    'about' => null
                ]
            ]);
    }

    private function createRegisteredUser(InternalTelegramUserId $internalTelegramUserId, BotId $botId, OpenConnection $connection)
    {
        $telegramUserId = Uuid::uuid4()->toString();
        (new TelegramUser($connection))
            ->insert([
                ['id' => $telegramUserId, 'telegram_id' => $internalTelegramUserId->value()]
            ]);
        (new BotUser($connection))
            ->insert([
                [
                    'user_id' => $telegramUserId,
                    'bot_id' => $botId->value(),
                    'status' => (new Registered())->value(),
                    'position' => (new ProductManager())->value(),
                    'experience' => (new LessThanAYear())->value(),
                    'about' => 'hello everybody!'
                ]
            ]);
    }
}