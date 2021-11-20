<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Activities\Cron\SendsSorryToDropouts;

use Meringue\ISO8601DateTime;
use Meringue\ISO8601Interval\Floating\NDays;
use Meringue\Timeline\Point\Now;
use Meringue\Timeline\Point\Past;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RC\Activities\Cron\SendsSorryToDropouts\SendsSorryToDropouts;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotId\FromUuid;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Domain\MeetingRound\MeetingRoundId\Pure\FromString as MeetingRoundIdFromString;
use RC\Domain\MeetingRound\MeetingRoundId\Pure\MeetingRoundId;
use RC\Domain\Participant\Status\Pure\Registered;
use RC\Domain\TelegramUser\UserId\Pure\FromUuid as TelegramUserIdFromUuid;
use RC\Domain\TelegramUser\UserId\Pure\TelegramUserId;
use RC\Infrastructure\Http\Request\Outbound\Request;
use RC\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery;
use RC\Infrastructure\Http\Request\Url\Query\FromUrl;
use RC\Infrastructure\Http\Transport\Indifferent;
use RC\Infrastructure\Logging\Logs\DevNull;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromInteger;
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId;
use RC\Infrastructure\Uuid\FromString;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Stub\Table\Bot;
use RC\Tests\Infrastructure\Stub\Table\BotUser;
use RC\Tests\Infrastructure\Stub\Table\Dropout;
use RC\Tests\Infrastructure\Stub\Table\MeetingRound;
use RC\Tests\Infrastructure\Stub\Table\MeetingRoundParticipant;
use RC\Tests\Infrastructure\Stub\Table\TelegramUser;

class SendsSorryToDropoutsTest extends TestCase
{
    public function testWhenThereAreTwoPairsWithCommonInterestsThenFourParticipantsReceiveTheirMatch()
    {
        $transport = new Indifferent();
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $connection);

        $this->createRound($this->meetingRoundId(), $this->botId(), new Now(), $connection);

        $this->createUser($this->firstTelegramUserId(), $this->firstInternalTelegramUserId(), $this->botId(), $connection);
        $this->createUser($this->secondTelegramUserId(), $this->secondInternalTelegramUserId(), $this->botId(), $connection);
        $this->createUser($this->thirdTelegramUserId(), $this->thirdInternalTelegramUserId(), $this->botId(), $connection);
        $this->createUser($this->fourthTelegramUserId(), $this->fourthInternalTelegramUserId(), $this->botId(), $connection);

        $this->createDropout($this->firstTelegramUserId(), $this->meetingRoundId(), $connection);
        $this->createDropout($this->secondTelegramUserId(), $this->meetingRoundId(), $connection);
        $this->createDropout($this->thirdTelegramUserId(), $this->meetingRoundId(), $connection);
        $this->createDropout($this->fourthTelegramUserId(), $this->meetingRoundId(), $connection);

        (new SendsSorryToDropouts(
            $this->botId(),
            $transport,
            $connection,
            new DevNull()
        ))
            ->response();

        $this->assertCount(4, $transport->sentRequests());
        $this->assertMessageIsSentTo($this->firstInternalTelegramUserId(), $transport->sentRequests()[0]);
        $this->assertMessageIsSentTo($this->secondInternalTelegramUserId(), $transport->sentRequests()[1]);
        $this->assertMessageIsSentTo($this->thirdInternalTelegramUserId(), $transport->sentRequests()[2]);
        $this->assertMessageIsSentTo($this->fourthInternalTelegramUserId(), $transport->sentRequests()[3]);

        (new SendsSorryToDropouts(
            $this->botId(),
            $transport,
            $connection,
            new DevNull()
        ))
            ->response();
        $this->assertCount(4, $transport->sentRequests());
    }

    public function testWhenThereAreTwoPairsInOneRoundAndOtherRoundsAndParticipantsArePresentEitherThenStillFourFormerRoundParticipantsReceiveTheirMatch()
    {
        $transport = new Indifferent();
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $connection);
        $this->createRound($this->meetingRoundId(), $this->botId(), new Now(), $connection);

        $this->createUser($this->firstTelegramUserId(), $this->firstInternalTelegramUserId(), $this->botId(), $connection);
        $this->createUser($this->secondTelegramUserId(), $this->secondInternalTelegramUserId(), $this->botId(), $connection);
        $this->createUser($this->thirdTelegramUserId(), $this->thirdInternalTelegramUserId(), $this->botId(), $connection);
        $this->createUser($this->fourthTelegramUserId(), $this->fourthInternalTelegramUserId(), $this->botId(), $connection);

        $this->createDropout($this->firstTelegramUserId(), $this->meetingRoundId(), $connection);
        $this->createDropout($this->secondTelegramUserId(), $this->meetingRoundId(), $connection);
        $this->createDropout($this->thirdTelegramUserId(), $this->meetingRoundId(), $connection);
        $this->createDropout($this->fourthTelegramUserId(), $this->meetingRoundId(), $connection);

        $this->createRound($this->earlierMeetingRoundId(), $this->botId(), new Past(new Now(), new NDays(7)), $connection);

        $this->createDropout($this->firstTelegramUserId(), $this->earlierMeetingRoundId(), $connection);
        $this->createDropout($this->secondTelegramUserId(), $this->earlierMeetingRoundId(), $connection);
        $this->createDropout($this->thirdTelegramUserId(), $this->earlierMeetingRoundId(), $connection);
        $this->createDropout($this->fourthTelegramUserId(), $this->earlierMeetingRoundId(), $connection);

        $this->createRound($this->anotherBotsMeetingRoundId(), $this->anotherBotId(), new Now(), $connection);

        $this->createDropout($this->firstTelegramUserId(), $this->anotherBotsMeetingRoundId(), $connection);
        $this->createDropout($this->secondTelegramUserId(), $this->anotherBotsMeetingRoundId(), $connection);
        $this->createDropout($this->thirdTelegramUserId(), $this->anotherBotsMeetingRoundId(), $connection);
        $this->createDropout($this->fourthTelegramUserId(), $this->anotherBotsMeetingRoundId(), $connection);

        (new SendsSorryToDropouts(
            $this->botId(),
            $transport,
            $connection,
            new DevNull()
        ))
            ->response();
        $this->assertCount(4, $transport->sentRequests());
        $this->assertMessageIsSentTo($this->firstInternalTelegramUserId(), $transport->sentRequests()[0]);
        $this->assertMessageIsSentTo($this->secondInternalTelegramUserId(), $transport->sentRequests()[1]);
        $this->assertMessageIsSentTo($this->thirdInternalTelegramUserId(), $transport->sentRequests()[2]);
        $this->assertMessageIsSentTo($this->fourthInternalTelegramUserId(), $transport->sentRequests()[3]);

        (new SendsSorryToDropouts(
            $this->botId(),
            $transport,
            $connection,
            new DevNull()
        ))
            ->response();
        $this->assertCount(4, $transport->sentRequests());
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function botId(): BotId
    {
        return new FromUuid(new FromString('8a998d04-91aa-4aed-bf85-c757e35df4fc'));
    }

    private function anotherBotId(): BotId
    {
        return new FromUuid(new FromString('7e86322f-066b-423e-b754-d41c10d83001'));
    }

    private function meetingRoundId(): MeetingRoundId
    {
        return new MeetingRoundIdFromString('8a998d04-91aa-4aed-bf85-c757e35df4fc');
    }

    private function firstTelegramUserId(): TelegramUserId
    {
        return new TelegramUserIdFromUuid(new FromString('b352b467-bfe9-4604-9e5a-02a858371de4'));
    }

    private function secondTelegramUserId(): TelegramUserId
    {
        return new TelegramUserIdFromUuid(new FromString('cac59bc0-2a41-4695-8f4e-7a3bf2c121e0'));
    }

    private function thirdTelegramUserId(): TelegramUserId
    {
        return new TelegramUserIdFromUuid(new FromString('b2776088-969f-4c7f-9758-f0117a711d05'));
    }

    private function fourthTelegramUserId(): TelegramUserId
    {
        return new TelegramUserIdFromUuid(new FromString('ac6cee9b-b420-461e-b436-3de9013fceaf'));
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

    private function earlierMeetingRoundId(): MeetingRoundId
    {
        return new MeetingRoundIdFromString('a7c26e15-5ed3-4d5c-bbfa-ef58477f212e');
    }

    private function anotherBotsMeetingRoundId(): MeetingRoundId
    {
        return new MeetingRoundIdFromString('d2cd6c8f-904e-4774-a369-2cfbedaca9b7');
    }

    private function createBot(BotId $botId, OpenConnection $connection)
    {
        (new Bot($connection))
            ->insert([
                ['id' => $botId->value()]
            ]);
    }

    private function createRound(MeetingRoundId $meetingRoundId, BotId $botId, ISO8601DateTime $startDateTime, OpenConnection $connection)
    {
        (new MeetingRound($connection))
            ->insert([
                ['id' => $meetingRoundId->value(), 'bot_id' => $botId->value(), 'start_date' => $startDateTime->value()]
            ]);
    }

    private function createUser(TelegramUserId $telegramUserId, InternalTelegramUserId $internalTelegramUserId, BotId $botId, OpenConnection $connection)
    {
        (new TelegramUser($connection))
            ->insert([
                ['id' => $telegramUserId->value(), 'telegram_id' => $internalTelegramUserId->value()]
            ]);
        (new BotUser($connection))
            ->insert([
                ['user_id' => $telegramUserId->value(), 'bot_id' => $botId->value(), ]
            ]);
    }

    private function createDropout(TelegramUserId $telegramUserId, MeetingRoundId $meetingRoundId, OpenConnection $connection)
    {
        $meetingRoundParticipantId = Uuid::uuid4()->toString();
        (new MeetingRoundParticipant($connection))
            ->insert([
                ['id' => $meetingRoundParticipantId, 'user_id' => $telegramUserId->value(), 'meeting_round_id' => $meetingRoundId->value(), 'status' => (new Registered())->value()]
            ]);
        (new Dropout($connection))
            ->insert([
                ['user_id' => $telegramUserId->value(), 'dropout_participant_id' => $meetingRoundParticipantId, 'sorry_is_sent' => 0]
            ]);
    }

    private function assertMessageIsSentTo(InternalTelegramUserId $internalTelegramUserId, Request $sentRequest)
    {
        $this->assertEquals($internalTelegramUserId->value(), (new FromQuery(new FromUrl($sentRequest->url())))->value()['chat_id']);
        $this->assertEquals(
            <<<text
К сожалению, в этот раз у нас нечетное количество участников и вам не повезло получить собеседника. 

Бот пришлет вам приглашение на следующий раунд встреч — участвуйте дальше, и мы обязательно подберем вам пару для разговора.
text
            ,
            (new FromQuery(new FromUrl($sentRequest->url())))->value()['text']
        );
    }

    private function createParticipantFedya(BotId $botId, MeetingRoundId $meetingRoundId, OpenConnection $connection)
    {
        $userId = Uuid::uuid4()->toString();
        (new TelegramUser($connection))
            ->insert([
                ['id' => $userId, 'first_name' => 'Fedya', 'last_name' => 'Liubitel katatsya na velosipede', 'telegram_id' => mt_rand(1, 999999), 'telegram_handle' => '@fedya',]
            ]);
        (new BotUser($connection))
            ->insert([
                ['user_id' => $userId, 'bot_id' => $botId->value(), ]
            ]);
        (new MeetingRoundParticipant($connection))
            ->insert([
                ['user_id' => $userId, 'meeting_round_id' => $meetingRoundId->value(), 'status' => (new Registered())->value()]
            ]);
    }

    private function createParticipantTolya(BotId $botId, MeetingRoundId $meetingRoundId, array $interestedIn, OpenConnection $connection)
    {
        $userId = Uuid::uuid4()->toString();
        (new TelegramUser($connection))
            ->insert([
                ['id' => $userId, 'first_name' => 'Tolya', 'last_name' => 'Liubitel alkogolya', 'telegram_id' => mt_rand(1, 999999), 'telegram_handle' => '@tolya',]
            ]);
        (new BotUser($connection))
            ->insert([
                ['user_id' => $userId, 'bot_id' => $botId->value(), ]
            ]);
        (new MeetingRoundParticipant($connection))
            ->insert([
                ['user_id' => $userId, 'meeting_round_id' => $meetingRoundId->value(), 'status' => (new Registered())->value(), 'interested_in' => $interestedIn]
            ]);
    }

    private function createParticipantPolina(BotId $botId, MeetingRoundId $meetingRoundId, array $interestedIn, OpenConnection $connection)
    {
        $userId = Uuid::uuid4()->toString();
        (new TelegramUser($connection))
            ->insert([
                ['id' => $userId, 'first_name' => 'Polina', 'last_name' => 'P.', 'telegram_id' => mt_rand(1, 999999), 'telegram_handle' => '@polzzzza',]
            ]);
        (new BotUser($connection))
            ->insert([
                ['user_id' => $userId, 'bot_id' => $botId->value(), ]
            ]);
        (new MeetingRoundParticipant($connection))
            ->insert([
                ['user_id' => $userId, 'meeting_round_id' => $meetingRoundId->value(), 'status' => (new Registered())->value(), 'interested_in' => $interestedIn]
            ]);
    }
}