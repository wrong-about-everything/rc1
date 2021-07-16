<?php

declare(strict_types=1);

namespace RC\Tests\Unit\UserStories\User\PressesStart;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Domain\User\RegisteredInBot;
use RC\Domain\UserProfileRecordType\Experience;
use RC\Domain\UserProfileRecordType\Position;
use RC\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery;
use RC\Infrastructure\Http\Request\Url\Query\FromUrl;
use RC\Infrastructure\Http\Transport\Indifferent;
use RC\Infrastructure\Logging\LogId;
use RC\Infrastructure\Logging\Logs\DevNull;
use RC\Infrastructure\Logging\Logs\InMemory;
use RC\Domain\BotId\BotId;
use RC\Domain\BotId\FromUuid;
use RC\Infrastructure\Logging\Logs\StdOut;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\TelegramBot\UserId\FromInteger;
use RC\Infrastructure\TelegramBot\UserId\TelegramUserId;
use RC\Infrastructure\Uuid\Fixed;
use RC\Infrastructure\Uuid\RandomUUID;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Stub\Bot;
use RC\Tests\Infrastructure\Stub\RegistrationQuestion;
use RC\Tests\Infrastructure\Stub\StartMessage;
use RC\Tests\Infrastructure\Stub\BotUser;
use RC\Tests\Infrastructure\Stub\UserRegistrationProgress;
use RC\UserStories\User\PressesStart\PressesStart;

class PressesStartTest extends TestCase
{
    public function testWhenNewUserPressesStartThenHeSeesTheFirstQuestion()
    {
        $connection = new ApplicationConnection();
        (new Bot($connection))
            ->insert([
                ['id' => $this->botId()->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'vasya_bot']
            ]);
        (new RegistrationQuestion($connection))
            ->insert([
                ['id' => Uuid::uuid4()->toString(), 'profile_record_type' => (new Position())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 1, 'text' => 'Какая у вас должность?'],
                ['id' => Uuid::uuid4()->toString(), 'profile_record_type' => (new Experience())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 2, 'text' => 'А опыт?'],
            ]);
        $transport = new Indifferent();

        $response =
            (new PressesStart(
                (new StartMessage($this->telegramUserId()))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserExists($this->telegramUserId(), $this->botId(), $connection);
        $this->assertEquals(
            'Какая у вас должность?',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenExistingButNotRegisteredUserPressesStartOneMoreTimeThenHeStillSeesTheFirstQuestion()
    {
        $connection = new ApplicationConnection();
        (new Bot($connection))
            ->insert([
                ['id' => $this->botId()->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'vasya_bot']
            ]);
        (new BotUser($this->botId(), $connection))
            ->insert(
                ['id' => Uuid::uuid4()->toString(), 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $this->telegramUserId()->value()->pure()->raw(), 'telegram_handle' => 'dremuchee_bydlo']
            );
        (new RegistrationQuestion($connection))
            ->insert([
                ['id' => Uuid::uuid4()->toString(), 'profile_record_type' => (new Position())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 1, 'text' => 'Какая у вас должность?'],
                ['id' => Uuid::uuid4()->toString(), 'profile_record_type' => (new Experience())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 2, 'text' => 'А опыт?'],
            ]);
        $transport = new Indifferent();

        $response =
            (new PressesStart(
                (new StartMessage($this->telegramUserId()))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserExists($this->telegramUserId(), $this->botId(), $connection);
        $this->assertEquals(
            'Какая у вас должность?',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenExistingButNotRegisteredUserWhoHaveAnsweredOneQuestionPressesStartThenHeSeesTheSecondQuestion()
    {
        $connection = new ApplicationConnection();
        (new Bot($connection))
            ->insert([
                ['id' => $this->botId()->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'vasya_bot']
            ]);
        $userId = Uuid::uuid4()->toString();
        (new BotUser($this->botId(), $connection))
            ->insert(
                ['id' => $userId, 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $this->telegramUserId()->value()->pure()->raw(), 'telegram_handle' => 'dremuchee_bydlo']
            );
        $firstRegistrationQuestionId = Uuid::uuid4()->toString();
        (new RegistrationQuestion($connection))
            ->insert([
                ['id' => $firstRegistrationQuestionId, 'profile_record_type' => (new Position())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 1, 'text' => 'Какая у вас должность?'],
                ['id' => Uuid::uuid4()->toString(), 'profile_record_type' => (new Experience())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 2, 'text' => 'А опыт?'],
            ]);
        (new UserRegistrationProgress($connection))
            ->insert([
                ['registration_question_id' => $firstRegistrationQuestionId, 'user_id' => $userId],
            ]);
        $transport = new Indifferent();

        $response =
            (new PressesStart(
                (new StartMessage($this->telegramUserId()))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserExists($this->telegramUserId(), $this->botId(), $connection);
        $this->assertEquals(
            'А опыт?',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenRegisteredUserPressesStartThenHeSeesWhatHeCanDo()
    {
        $connection = new ApplicationConnection();
        (new Bot($connection))
            ->insert([
                ['id' => $this->botId()->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'vasya_bot']
            ]);
        $userId = Uuid::uuid4()->toString();
        (new BotUser($this->botId(), $connection))
            ->insert(
                ['id' => $userId, 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $this->telegramUserId()->value()->pure()->raw(), 'telegram_handle' => 'dremuchee_bydlo']
            );
        $firstRegistrationQuestionId = Uuid::uuid4()->toString();
        $secondRegistrationQuestionId = Uuid::uuid4()->toString();
        (new RegistrationQuestion($connection))
            ->insert([
                ['id' => $firstRegistrationQuestionId, 'profile_record_type' => (new Position())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 1, 'text' => 'Какая у вас должность?'],
                ['id' => $secondRegistrationQuestionId, 'profile_record_type' => (new Experience())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 2, 'text' => 'А опыт?'],
            ]);
        (new UserRegistrationProgress($connection))
            ->insert([
                ['registration_question_id' => $firstRegistrationQuestionId, 'user_id' => $userId],
                ['registration_question_id' => $secondRegistrationQuestionId, 'user_id' => $userId],
            ]);
        $transport = new Indifferent();

        $response =
            (new PressesStart(
                (new StartMessage($this->telegramUserId()))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserExists($this->telegramUserId(), $this->botId(), $connection);
        $this->assertEquals(
            'Вы уже зарегистрировались. Если вы хотите что-то спросить или уточнить, смело пишите на @gorgonzola_support',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function telegramUserId(): TelegramUserId
    {
        return new FromInteger(654987);
    }

    private function botId(): BotId
    {
        return new FromUuid(new Fixed());
    }

    private function assertUserExists(TelegramUserId $telegramUserId, BotId $botId, OpenConnection $connection)
    {
        $user = (new RegisteredInBot($telegramUserId, $botId, $connection))->value();
        $this->assertTrue($user->pure()->isPresent());
        $this->assertEquals('Vadim', $user->pure()->raw()['first_name']);
        $this->assertEquals('Samokhin', $user->pure()->raw()['last_name']);
        $this->assertEquals('dremuchee_bydlo', $user->pure()->raw()['telegram_handle']);
    }
}