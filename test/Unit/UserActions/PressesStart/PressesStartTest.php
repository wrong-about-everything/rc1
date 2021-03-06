<?php

declare(strict_types=1);

namespace RC\Tests\Unit\UserActions\PressesStart;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Domain\BotUser\ReadModel\ByInternalTelegramUserIdAndBotId;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Experience;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Position;
use RC\Domain\TelegramUser\ByTelegramId;
use RC\Domain\TelegramUser\RegisteredInBot;
use RC\Domain\TelegramUser\UserId\Pure\FromUuid as UserIdFromUuid;
use RC\Domain\TelegramUser\UserId\Pure\TelegramUserId;
use RC\Domain\BotUser\UserStatus\Pure\Registered;
use RC\Domain\BotUser\UserStatus\Pure\RegistrationIsInProgress;
use RC\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery;
use RC\Infrastructure\Http\Request\Url\Query\FromUrl;
use RC\Infrastructure\Http\Transport\Indifferent;
use RC\Infrastructure\Logging\Logs\DevNull;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotId\FromUuid;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromInteger;
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId;
use RC\Infrastructure\Uuid\Fixed;
use RC\Infrastructure\Uuid\FromString;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Stub\Table\Bot;
use RC\Tests\Infrastructure\Stub\Table\RegistrationQuestion;
use RC\Tests\Infrastructure\Stub\Table\TelegramUser;
use RC\Tests\Infrastructure\Stub\TelegramMessage\StartCommandMessage;
use RC\Tests\Infrastructure\Stub\Table\BotUser;
use RC\Tests\Infrastructure\Stub\Table\UserRegistrationProgress;
use RC\Tests\Infrastructure\Stub\TelegramMessage\StartCommandMessageWithEmptyUsername;
use RC\UserActions\PressesStart\PressesStart;

class PressesStartTest extends TestCase
{
    public function testWhenNewUserDoesNotHaveUsernameThenHeSeesAPromptMessageToSetIt()
    {
        $connection = new ApplicationConnection();
        (new Bot($connection))
            ->insert([
                ['id' => $this->botId()->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'vasya_bot']
            ]);
        $transport = new Indifferent();

        $response =
            (new PressesStart(
                (new StartCommandMessageWithEmptyUsername($this->telegramUserId()))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserDoesNotExist($this->telegramUserId(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            <<<t
???? ???????????????? ???? ???????????????? ???????????????????? ?? ???????????????? ????????, ???? ?? ???????? ?????? ?????????????? ????????????. ?????? ????????, ?????????? ???? ???????????? ???????????????? ???????? ???????????????? ?????????????? ????????????????????????, ?????? ?????????? ?????????? ?????? ??????, ?? ???? ?? ?????? ???? ????????????. ???????? ???? ????????????, ?????? ???????????? ?????? ?????? ???????? ??????????????, ?????? ?????????????????? ????????????????????: https://aboutmessengers.ru/kak-pomenyat-imya-v-telegramme/. 

???????? ???? ????????????, ?????????? ?????? ??????????????, ???????????????????? ???????????? ?????????? ????????. ????????????????, ?????????? ??? {$this->time()}. ?????????????? ???? ????????, ????, ??????????, ???? ????????????????.

?????? ?????????? ????????????, ?????????? ?????????????? /start. 
t
            ,
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testGivenNoUsersAreRegisteredInBotWhenNewUserPressesStartThenHeSeesTheFirstQuestion()
    {
        $connection = new ApplicationConnection();
        (new Bot($connection))
            ->insert([
                ['id' => $this->botId()->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'vasya_bot']
            ]);
        (new RegistrationQuestion($connection))
            ->insert([
                ['id' => Uuid::uuid4()->toString(), 'profile_record_type' => (new Position())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 1, 'text' => '?????????? ?? ?????? ???????????????????'],
                ['id' => Uuid::uuid4()->toString(), 'profile_record_type' => (new Experience())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 2, 'text' => '?? ?????????'],
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
            '?????????? ?? ?????? ???????????????????',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testGivenSomeUsersAreRegisteredInBotWhenNewUserPressesStartThenHeSeesTheFirstQuestion()
    {
        $connection = new ApplicationConnection();
        (new Bot($connection))
            ->insert([
                ['id' => $this->botId()->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'vasya_bot']
            ]);
        (new TelegramUser($connection))
            ->insert([
                ['id' => $this->userId()->value(), 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => 987654321, 'telegram_handle' => 'dremuchee_bydlo'],
            ]);
        (new BotUser($connection))
            ->insert([
                ['bot_id' => $this->botId()->value(), 'user_id' => $this->userId()->value(), 'status' => (new Registered())->value()]
            ]);
        (new UserRegistrationProgress($connection))
            ->insert([
                ['registration_question_id' => $this->firstRegistrationQuestionId(), 'user_id' => $this->userId()->value()],
                ['registration_question_id' => $this->secondRegistrationQuestionId(), 'user_id' => $this->userId()->value()],
            ]);
        (new RegistrationQuestion($connection))
            ->insert([
                ['id' => $this->firstRegistrationQuestionId(), 'profile_record_type' => (new Position())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 1, 'text' => '?????????? ?? ?????? ???????????????????'],
                ['id' => $this->secondRegistrationQuestionId(), 'profile_record_type' => (new Experience())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 2, 'text' => '?? ?????????'],
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
            '?????????? ?? ?????? ???????????????????',
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
        (new TelegramUser($connection))
            ->insert([
                ['id' => $this->userId()->value(), 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $this->telegramUserId()->value(), 'telegram_handle' => 'dremuchee_bydlo'],
            ]);
        (new BotUser($connection))
            ->insert([
                ['bot_id' => $this->botId()->value(), 'user_id' => $this->userId()->value(), 'status' => (new RegistrationIsInProgress())->value()]
            ]);
        (new RegistrationQuestion($connection))
            ->insert([
                ['id' => Uuid::uuid4()->toString(), 'profile_record_type' => (new Position())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 1, 'text' => '?????????? ?? ?????? ???????????????????'],
                ['id' => Uuid::uuid4()->toString(), 'profile_record_type' => (new Experience())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 2, 'text' => '?? ?????????'],
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
            '?????????? ?? ?????? ???????????????????',
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
        (new TelegramUser($connection))
            ->insert([
                ['id' => $this->userId()->value(), 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $this->telegramUserId()->value(), 'telegram_handle' => 'dremuchee_bydlo'],
            ]);
        (new BotUser($connection))
            ->insert([
                ['bot_id' => $this->botId()->value(), 'user_id' => $this->userId()->value(), 'status' => (new RegistrationIsInProgress())->value()]
            ]);
        (new RegistrationQuestion($connection))
            ->insert([
                ['id' => $this->firstRegistrationQuestionId(), 'profile_record_type' => (new Position())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 1, 'text' => '?????????? ?? ?????? ???????????????????'],
                ['id' => Uuid::uuid4()->toString(), 'profile_record_type' => (new Experience())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 2, 'text' => '?? ?????????'],
            ]);
        (new UserRegistrationProgress($connection))
            ->insert([
                ['registration_question_id' => $this->firstRegistrationQuestionId(), 'user_id' => $this->userId()->value()],
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
            '?? ?????????',
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
        (new TelegramUser($connection))
            ->insert([
                ['id' => $this->userId()->value(), 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $this->telegramUserId()->value(), 'telegram_handle' => 'dremuchee_bydlo'],
            ]);
        (new BotUser($connection))
            ->insert([
                ['bot_id' => $this->botId()->value(), 'user_id' => $this->userId()->value(), 'status' => (new RegistrationIsInProgress())->value()]
            ]);
        (new RegistrationQuestion($connection))
            ->insert([
                ['id' => $this->firstRegistrationQuestionId(), 'profile_record_type' => (new Position())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 1, 'text' => '?????????? ?? ?????? ???????????????????'],
                ['id' => $this->secondRegistrationQuestionId(), 'profile_record_type' => (new Position())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 2, 'text' => '?? ?????? ???????????????????'],
                ['id' => Uuid::uuid4()->toString(), 'profile_record_type' => (new Experience())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 3, 'text' => '?? ?????????'],
            ]);
        (new UserRegistrationProgress($connection))
            ->insert([
                ['registration_question_id' => $this->firstRegistrationQuestionId(), 'user_id' => $this->userId()->value()],
                ['registration_question_id' => $this->secondRegistrationQuestionId(), 'user_id' => $this->userId()->value()],
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
            '?? ?????????',
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
        (new TelegramUser($connection))
            ->insert([
                ['id' => $this->userId()->value(), 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $this->telegramUserId()->value(), 'telegram_handle' => 'dremuchee_bydlo'],
            ]);
        (new BotUser($connection))
            ->insert([
                ['bot_id' => $this->botId()->value(), 'user_id' => $this->userId()->value(), 'status' => (new Registered())->value()]
            ]);
        (new RegistrationQuestion($connection))
            ->insert([
                ['id' => $this->firstRegistrationQuestionId(), 'profile_record_type' => (new Position())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 1, 'text' => '?????????? ?? ?????? ???????????????????'],
                ['id' => $this->secondRegistrationQuestionId(), 'profile_record_type' => (new Experience())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 2, 'text' => '?? ?????????'],
            ]);
        (new UserRegistrationProgress($connection))
            ->insert([
                ['registration_question_id' => $this->firstRegistrationQuestionId(), 'user_id' => $this->userId()->value()],
                ['registration_question_id' => $this->secondRegistrationQuestionId(), 'user_id' => $this->userId()->value()],
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
            '???????????? ??????-???? ????????????????? ?????????? ???????????? ???? @gorgonzola_support_bot!',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenUserRegisteredInOneBotPressesStartInAnotherOneThenHeSeesTheFirstQuestion()
    {
        $connection = new ApplicationConnection();
        (new Bot($connection))
            ->insert([
                ['id' => $this->botId()->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'vasya_bot']
            ]);
        (new TelegramUser($connection))
            ->insert([
                ['id' => $this->userId()->value(), 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $this->telegramUserId()->value(), 'telegram_handle' => 'dremuchee_bydlo'],
            ]);
        (new BotUser($connection))
            ->insert([
                ['bot_id' => $this->botId()->value(), 'user_id' => $this->userId()->value(), 'status' => (new Registered())->value()]
            ]);

        (new Bot($connection))
            ->insert([
                ['id' => $this->anotherBotId()->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'fedya_bot']
            ]);
        (new RegistrationQuestion($connection))
            ->insert([
                ['id' => Uuid::uuid4()->toString(), 'profile_record_type' => (new Position())->value(), 'bot_id' => $this->anotherBotId()->value(), 'ordinal_number' => 1, 'text' => '?????????? ?? ?????? ???????????????????'],
                ['id' => Uuid::uuid4()->toString(), 'profile_record_type' => (new Experience())->value(), 'bot_id' => $this->anotherBotId()->value(), 'ordinal_number' => 2, 'text' => '?? ?????????'],
            ]);

        $transport = new Indifferent();

        $response =
            (new PressesStart(
                (new StartCommandMessage($this->telegramUserId()))->value(),
                $this->anotherBotId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserExists($this->telegramUserId(), $this->anotherBotId(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            '?????????? ?? ?????? ???????????????????',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function telegramUserId(): InternalTelegramUserId
    {
        return new FromInteger(654987);
    }

    private function botId(): BotId
    {
        return new FromUuid(new Fixed());
    }

    private function anotherBotId(): BotId
    {
        return new FromUuid(new FromString('18c73371-497a-44ed-94eb-89071a2d04c3'));
    }

    private function firstRegistrationQuestionId()
    {
        return 'a2737a5d-9f02-4a62-886d-6f29cfbbccef';
    }

    private function secondRegistrationQuestionId()
    {
        return 'ddd7969c-02a3-447e-ab34-42cbea41a5d3';
    }

    private function userId(): TelegramUserId
    {
        return new UserIdFromUuid(new FromString('103729d6-330c-4123-b856-d5196812d509'));
    }

    private function assertUserExists(InternalTelegramUserId $telegramUserId, BotId $botId, OpenConnection $connection)
    {
        $user = (new RegisteredInBot($telegramUserId, $botId, $connection))->value();
        $this->assertTrue($user->pure()->isPresent());
        $this->assertEquals('Vadim', $user->pure()->raw()['first_name']);
        $this->assertEquals('Samokhin', $user->pure()->raw()['last_name']);
        $this->assertEquals('dremuchee_bydlo', $user->pure()->raw()['telegram_handle']);
        $profile = (new ByInternalTelegramUserIdAndBotId($telegramUserId, $botId, $connection))->value();
        $this->assertTrue($profile->pure()->isPresent());
    }

    private function assertUserDoesNotExist(InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        $this->assertFalse(
            (new ByTelegramId($telegramUserId, $connection))->value()->pure()->isPresent()
        );
    }

    private function time()
    {
        return time();
    }
}