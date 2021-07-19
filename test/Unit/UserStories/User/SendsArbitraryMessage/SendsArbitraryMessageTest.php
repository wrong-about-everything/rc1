<?php

declare(strict_types=1);

namespace RC\Tests\Unit\UserStories\User\SendsArbitraryMessage;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RC\Domain\BotUser\ByTelegramUserId;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Domain\Position\Impure\FromBotUser;
use RC\Domain\Position\Impure\FromPure;
use RC\Domain\Position\Pure\Position as UserPosition;
use RC\Domain\Position\Pure\ProductManager;
use RC\Domain\RegistrationQuestionId\Impure\FromString as RegistrationQuestionIdFromString;
use RC\Domain\RegistrationQuestionId\Impure\RegistrationQuestionId;
use RC\Domain\User\RegisteredInBot;
use RC\Domain\User\User;
use RC\Domain\UserId\FromUuid as UserIdFromUuid;
use RC\Domain\UserId\UserId;
use RC\Domain\UserProfileRecordType\Pure\Experience;
use RC\Domain\UserProfileRecordType\Pure\Position;
use RC\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery;
use RC\Infrastructure\Http\Request\Url\Query\FromUrl;
use RC\Infrastructure\Http\Transport\Indifferent;
use RC\Infrastructure\Logging\LogId;
use RC\Infrastructure\Logging\Logs\DevNull;
use RC\Domain\BotId\BotId;
use RC\Domain\BotId\FromUuid;
use RC\Infrastructure\Logging\Logs\StdOut;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromInteger;
use RC\Infrastructure\TelegramBot\UserId\Pure\TelegramUserId;
use RC\Infrastructure\Uuid\Fixed;
use RC\Infrastructure\Uuid\FromString;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Stub\Table\Bot;
use RC\Tests\Infrastructure\Stub\Table\BotUser;
use RC\Tests\Infrastructure\Stub\Table\RegistrationQuestion;
use RC\Tests\Infrastructure\Stub\TelegramMessage\UserMessage;
use RC\UserStories\User\SendsArbitraryMessage\SendsArbitraryMessage;

class SendsArbitraryMessageTest extends TestCase
{
    public function testWhenNewUserAnswersTheFirstQuestionThenHisAnswerIsPersistedAndHeSeesTheSecondOne()
    {
        $connection = new ApplicationConnection();
        (new Bot($connection))
            ->insert([
                ['id' => $this->botId()->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'vasya_bot']
            ]);
        (new BotUser($this->botId(), $connection))
            ->insert(
                ['id' => $this->userId()->value(), 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $this->telegramUserId()->value(), 'telegram_handle' => 'dremuchee_bydlo']
            );
        (new RegistrationQuestion($connection))
            ->insert([
                ['id' => $this->firstRegistrationQuestionId()->value()->pure()->raw(), 'profile_record_type' => (new Position())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 1, 'text' => 'Какая у вас должность?'],
                ['id' => $this->secondRegistrationQuestionId()->value()->pure()->raw(), 'profile_record_type' => (new Experience())->value(), 'bot_id' => $this->botId()->value(), 'ordinal_number' => 2, 'text' => 'А опыт?'],
            ]);
        $transport = new Indifferent();

        $response =
            (new SendsArbitraryMessage(
                (new UserMessage($this->telegramUserId(), (string) (new ProductManager())->value()))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserRegistrationProgressUpdated($this->userId(), $this->firstRegistrationQuestionId(), $connection);
        $this->assertPositionIs($this->telegramUserId(), $this->botId(), new ProductManager(), $connection);
        $this->assertEquals(
            'А опыт?',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenExistingButNotYetRegisteredUserWhoHasAnsweredAllButOneQuestionAnswersTheLastOneThenHeBecomesRegisteredAndSeesStatusInfo()
    {
    }

    public function testWhenRegisteredUserSendsArbitraryMessageThenHeSeesStatusInfo()
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

    private function userId(): UserId
    {
        return new UserIdFromUuid(new FromString('103729d6-330c-4123-b856-d5196812d509'));
    }

    private function firstRegistrationQuestionId(): RegistrationQuestionId
    {
        return new RegistrationQuestionIdFromString('203729d6-330c-4123-b856-d5196812d509');
    }

    private function secondRegistrationQuestionId(): RegistrationQuestionId
    {
        return new RegistrationQuestionIdFromString('303729d6-330c-4123-b856-d5196812d509');
    }

    private function assertUserRegistrationProgressUpdated(UserId $userId, RegistrationQuestionId $registrationQuestionId, OpenConnection $connection)
    {
        $this->assertNotEmpty(
            (new Selecting(
                <<<q
select *
from user_registration_progress urp
where urp.user_id = ? and urp.registration_question_id = ?
q
                ,
                [$userId->value(), $registrationQuestionId->value()->pure()->raw()],
                $connection
            ))
                ->response()->pure()->raw()
        );
    }

    private function assertPositionIs(TelegramUserId $telegramUserId, BotId $botId, UserPosition $position, OpenConnection $connection)
    {
        $this->assertTrue(
            (new FromBotUser(
                new ByTelegramUserId($telegramUserId, $botId, $connection)
            ))
                ->equals(
                    new FromPure($position)
                )
        );
    }
}