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
use RC\Infrastructure\Logging\Logs\InMemory;
use RC\Domain\BotId\BotId;
use RC\Domain\BotId\FromUuid;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\TelegramBot\UserId\FromInteger;
use RC\Infrastructure\TelegramBot\UserId\TelegramUserId;
use RC\Infrastructure\Uuid\Fixed;
use RC\Infrastructure\Uuid\RandomUUID;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Stub\Bot;
use RC\Tests\Infrastructure\Stub\RegistrationQuestion;
use RC\Tests\Infrastructure\Stub\StartMessage;
use RC\Tests\Infrastructure\Stub\User;
use RC\UserStories\User\PressesStart\PressesStart;

class PressesStartTest extends TestCase
{
    public function testWhenNewUserPressesStartThenGreetingMessageIsSent()
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
        $logs = new InMemory(new LogId(new RandomUUID()));
        $transport = new Indifferent();

        $response =
            (new PressesStart(
                (new StartMessage($this->telegramUserId()))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                $logs
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserExists($this->telegramUserId(), $this->botId(), $connection);
        $this->assertEquals(
            'Какая у вас должность?',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenExistingButNotRegisteredUserPressesStartThenShowAnActualRegistrationQuestion()
    {

    }

    public function testWhenRegisteredUserPressesStartThenShowAMessageTellingWhatThisUserCanDo()
    {

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