<?php

declare(strict_types=1);

namespace RC\Tests\Unit\UserActions\PressesStart;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Domain\BotUser\ByTelegramUserId;
use RC\Domain\User\RegisteredInBot;
use RC\Domain\UserProfileRecordType\Pure\Experience;
use RC\Domain\UserProfileRecordType\Pure\Position;
use RC\Domain\User\UserStatus\Pure\Registered;
use RC\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery;
use RC\Infrastructure\Http\Request\Url\Query\FromUrl;
use RC\Infrastructure\Http\Transport\Indifferent;
use RC\Infrastructure\Logging\Logs\DevNull;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotId\FromUuid;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromInteger;
use RC\Infrastructure\TelegramBot\UserId\Pure\TelegramUserId;
use RC\Infrastructure\Uuid\Fixed;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Stub\Table\Bot;
use RC\Tests\Infrastructure\Stub\Table\RegistrationQuestion;
use RC\Tests\Infrastructure\Stub\TelegramMessage\StartCommandMessage;
use RC\Tests\Infrastructure\Stub\Table\BotUser;
use RC\Tests\Infrastructure\Stub\Table\UserRegistrationProgress;
use RC\UserActions\PressesStart\PressesStart;

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
                (new StartCommandMessage($this->telegramUserId()))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserExists($this->telegramUserId(), $this->botId(), $connection);
        $this->assertCount(1, $transport->sentRequests());
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
                ['id' => Uuid::uuid4()->toString(), 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $this->telegramUserId()->value(), 'telegram_handle' => 'dremuchee_bydlo'],
                []
            );
        (new RegistrationQuestion($connection))
            ->insert([
                ['id' => Uuid::uuid4()->toString(), 'profile_record_type' => (new Position())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 1, 'text' => 'Какая у вас должность?'],
                ['id' => Uuid::uuid4()->toString(), 'profile_record_type' => (new Experience())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 2, 'text' => 'А опыт?'],
            ]);
        $transport = new Indifferent();

        $response =
            (new PressesStart(
                (new StartCommandMessage($this->telegramUserId()))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserExists($this->telegramUserId(), $this->botId(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Какая у вас должность?',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenExistingButNotRegisteredUserWhoHasAnsweredOneQuestionPressesStartThenHeSeesTheSecondQuestion()
    {
        $connection = new ApplicationConnection();
        (new Bot($connection))
            ->insert([
                ['id' => $this->botId()->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'vasya_bot']
            ]);
        $userId = Uuid::uuid4()->toString();
        (new BotUser($this->botId(), $connection))
            ->insert(
                ['id' => $userId, 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $this->telegramUserId()->value(), 'telegram_handle' => 'dremuchee_bydlo'],
                []
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
                (new StartCommandMessage($this->telegramUserId()))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserExists($this->telegramUserId(), $this->botId(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'А опыт?',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenExistingButNotYetRegisteredUserWhoHasAnsweredAllButOneQuestionPressesStartThenHeSeesTheLastQuestion()
    {
        $connection = new ApplicationConnection();
        (new Bot($connection))
            ->insert([
                ['id' => $this->botId()->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'vasya_bot']
            ]);
        $userId = Uuid::uuid4()->toString();
        (new BotUser($this->botId(), $connection))
            ->insert(
                ['id' => $userId, 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $this->telegramUserId()->value(), 'telegram_handle' => 'dremuchee_bydlo'],
                []
            );
        $firstRegistrationQuestionId = Uuid::uuid4()->toString();
        $secondRegistrationQuestionId = Uuid::uuid4()->toString();
        (new RegistrationQuestion($connection))
            ->insert([
                ['id' => $firstRegistrationQuestionId, 'profile_record_type' => (new Position())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 1, 'text' => 'Какая у вас должность?'],
                ['id' => $secondRegistrationQuestionId, 'profile_record_type' => (new Position())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 2, 'text' => 'А где работаете?'],
                ['id' => Uuid::uuid4()->toString(), 'profile_record_type' => (new Experience())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 3, 'text' => 'А опыт?'],
            ]);
        (new UserRegistrationProgress($connection))
            ->insert([
                ['registration_question_id' => $firstRegistrationQuestionId, 'user_id' => $userId],
                ['registration_question_id' => $secondRegistrationQuestionId, 'user_id' => $userId],
            ]);
        $transport = new Indifferent();

        $response =
            (new PressesStart(
                (new StartCommandMessage($this->telegramUserId()))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserExists($this->telegramUserId(), $this->botId(), $connection);
        $this->assertCount(1, $transport->sentRequests());
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
                ['id' => $userId, 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $this->telegramUserId()->value(), 'telegram_handle' => 'dremuchee_bydlo'],
                ['status' => (new Registered())->value()]
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
                (new StartCommandMessage($this->telegramUserId()))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserExists($this->telegramUserId(), $this->botId(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Хотите что-то уточнить? Смело пишите на @gorgonzola_support!',
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
        $profile = (new ByTelegramUserId($telegramUserId, $botId, $connection))->value();
        $this->assertTrue($profile->pure()->isPresent());
    }
}