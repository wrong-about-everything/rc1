<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Activities\Cron\SendsMatchesToParticipants;

use Meringue\ISO8601DateTime;
use Meringue\Timeline\Point\Now;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RC\Activities\Cron\SendsMatchesToParticipants\SendsMatchesToParticipants;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotId\FromUuid;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Domain\MeetingRound\MeetingRoundId\Pure\FromString as MeetingRoundIdFromString;
use RC\Domain\MeetingRound\MeetingRoundId\Pure\MeetingRoundId;
use RC\Domain\Participant\Status\Pure\Registered;
use RC\Domain\UserInterest\InterestId\Pure\Single\Networking;
use RC\Domain\UserInterest\InterestName\Pure\DayDreaming;
use RC\Infrastructure\Http\Transport\Indifferent;
use RC\Infrastructure\Logging\Logs\DevNull;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\Uuid\FromString;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Stub\Table\Bot;
use RC\Tests\Infrastructure\Stub\Table\BotUser;
use RC\Tests\Infrastructure\Stub\Table\MeetingRound;
use RC\Tests\Infrastructure\Stub\Table\MeetingRoundParticipant;
use RC\Tests\Infrastructure\Stub\Table\TelegramUser;

class SendsMatchesToParticipantsTest extends TestCase
{
    public function testWhenThereAreTwoPairsWithCommonInterestsThenFourParticipantsReceiveTheirMatch()
    {
        $transport = new Indifferent();
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $connection);
        $this->createRound($this->meetingRoundId(), $this->botId(), new Now(), $connection);
        $this->createParticipantVasya($this->botId(), $this->meetingRoundId(), [(new Networking())->value()], $connection);
        $this->createParticipantPolina($this->botId(), $this->meetingRoundId(), [(new Networking())->value()], $connection);
        $this->createParticipantFedya($this->botId(), $this->meetingRoundId(), [(new DayDreaming())->value()], $connection);
        $this->createParticipantTolya($this->botId(), $this->meetingRoundId(), [(new DayDreaming())->value()], $connection);

        (new SendsMatchesToParticipants(
            $this->botId(),
            $transport,
            $connection,
            new DevNull()
        ))
            ->response();
        $this->assertCount(4, $transport->sentRequests());

        (new SendsMatchesToParticipants(
            $this->botId(),
            $transport,
            $connection,
            new DevNull()
        ))
            ->response();
        $this->assertCount(4, $transport->sentRequests());
    }

    public function testWhenThereAreFourDropoutsInLastRoundAndThereIsOneEarlierRoundWithDropoutsEitherThenStillFourLastRoundDropoutsReceiveSorry()
    {
        $transport = new Indifferent();
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $connection);
        $this->createRound($this->meetingRoundId(), $this->botId(), new Now(), $connection);
        $this->createParticipantVasya($this->botId(), $this->meetingRoundId(), [(new Networking())->value()], $connection);
        $this->createParticipantPolina($this->botId(), $this->meetingRoundId(), [(new Networking())->value()], $connection);
        $this->createParticipantFedya($this->botId(), $this->meetingRoundId(), [(new DayDreaming())->value()], $connection);
        $this->createParticipantTolya($this->botId(), $this->meetingRoundId(), [(new DayDreaming())->value()], $connection);

        $this->createRound($this->anotherMeetingRoundId(), $this->botId(), new Now(), $connection);
        $this->createParticipantVasya($this->botId(), $this->anotherMeetingRoundId(), [(new Networking())->value()], $connection);
        $this->createParticipantPolina($this->botId(), $this->anotherMeetingRoundId(), [(new Networking())->value()], $connection);
        $this->createParticipantFedya($this->botId(), $this->anotherMeetingRoundId(), [(new DayDreaming())->value()], $connection);
        $this->createParticipantTolya($this->botId(), $this->anotherMeetingRoundId(), [(new DayDreaming())->value()], $connection);

        $this->createRound($this->yetAnotherMeetingRoundId(), $this->anotherBotId(), new Now(), $connection);
        $this->createParticipantVasya($this->anotherBotId(), $this->yetAnotherMeetingRoundId(), [(new Networking())->value()], $connection);
        $this->createParticipantPolina($this->anotherBotId(), $this->yetAnotherMeetingRoundId(), [(new Networking())->value()], $connection);
        $this->createParticipantFedya($this->anotherBotId(), $this->yetAnotherMeetingRoundId(), [(new DayDreaming())->value()], $connection);
        $this->createParticipantTolya($this->anotherBotId(), $this->yetAnotherMeetingRoundId(), [(new DayDreaming())->value()], $connection);

        (new SendsMatchesToParticipants(
            $this->botId(),
            $transport,
            $connection,
            new DevNull()
        ))
            ->response();
        $this->assertCount(4, $transport->sentRequests());

        (new SendsMatchesToParticipants(
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

    private function anotherMeetingRoundId(): MeetingRoundId
    {
        return new MeetingRoundIdFromString('a7c26e15-5ed3-4d5c-bbfa-ef58477f212e');
    }

    private function yetAnotherMeetingRoundId(): MeetingRoundId
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

    private function createParticipantVasya(BotId $botId, MeetingRoundId $meetingRoundId, array $interestedIn, OpenConnection $connection)
    {
        $userId = Uuid::uuid4()->toString();
        (new TelegramUser($connection))
            ->insert([
                ['id' => $userId, 'first_name' => 'Vasya', 'last_name' => 'Belov', 'telegram_id' => mt_rand(1, 999999), 'telegram_handle' => '@vasya',]
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

    private function createParticipantFedya(BotId $botId, MeetingRoundId $meetingRoundId, array $interestedIn, OpenConnection $connection)
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
                ['user_id' => $userId, 'meeting_round_id' => $meetingRoundId->value(), 'status' => (new Registered())->value(), 'interested_in' => $interestedIn]
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