<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Activities\Cron\InvitesToTakePartInANewRound;

use PHPUnit\Framework\TestCase;
use RC\Domain\BotId\BotId;
use RC\Domain\BotId\FromUuid;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Domain\MeetingRoundInvitation\Status\Pure\_New;
use RC\Domain\MeetingRoundInvitation\Status\Pure\FromInteger;
use RC\Domain\MeetingRoundInvitation\Status\Pure\Sent;
use RC\Domain\User\UserId\FromUuid as UserIdFromUuid;
use RC\Domain\User\UserId\UserId;
use RC\Infrastructure\Http\Transport\Indifferent;
use RC\Infrastructure\Logging\Logs\DevNull;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Infrastructure\Uuid\Fixed;
use RC\Infrastructure\Uuid\FromString;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Stub\Table\Bot;
use RC\Tests\Infrastructure\Stub\Table\MeetingRound;
use RC\Tests\Infrastructure\Stub\Table\MeetingRoundInvitation;
use RC\Tests\Infrastructure\Stub\Table\User;
use RC\Activities\Cron\InvitesToTakePartInANewRound\InvitesToTakePartInANewRound;

class InvitesToTakePartInANewRoundTest extends TestCase
{
    public function testWhenAllMeetingInvitationsAreSentThenNoInvitationIsSent()
    {
        $connection = new ApplicationConnection();
        $this->seedUser($this->firstUserId(), $connection);
        $this->seedUser($this->secondUserId(), $connection);
        // first meeting
        $this->seedBot($this->botId(), $connection);
        $this->seedMeetingRound($this->meetingRoundId(), $this->botId(), $connection);
        $this->seedSentMeetingRoundInvitations($this->meetingRoundId(), $connection);
        // second meeting
        $this->seedBot($this->someOtherBotId(), $connection);
        $this->seedMeetingRound($this->someOtherMeetingRoundId(), $this->someOtherBotId(), $connection);
        $this->seedNewMeetingRoundInvitations($this->someOtherMeetingRoundId(), $connection);

        $transport = new Indifferent();

        $response =
            (new InvitesToTakePartInANewRound(
                $this->botId(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(0, $transport->sentRequests());
        $this->assertAllInvitationsAreSent($this->botId(), $connection);
        $this->assertAllInvitationsAreNew($this->someOtherBotId(), $connection);
    }

    public function testWhenNoneOfMeetingInvitationsAreSentThenTheFirst100InvitationsAreSent()
    {
        $connection = new ApplicationConnection();
        $this->seedUser($this->firstUserId(), $connection);
        $this->seedUser($this->secondUserId(), $connection);
        // first meeting
        $this->seedBot($this->botId(), $connection);
        $this->seedMeetingRound($this->meetingRoundId(), $this->botId(), $connection);
        $this->seedNewMeetingRoundInvitations($this->meetingRoundId(), $connection);
        // second meeting
        $this->seedBot($this->someOtherBotId(), $connection);
        $this->seedMeetingRound($this->someOtherMeetingRoundId(), $this->someOtherBotId(), $connection);
        $this->seedNewMeetingRoundInvitations($this->someOtherMeetingRoundId(), $connection);

        $transport = new Indifferent();

        $response =
            (new InvitesToTakePartInANewRound(
                $this->botId(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(2, $transport->sentRequests());
        $this->assertAllInvitationsAreSent($this->botId(), $connection);
        $this->assertAllInvitationsAreNew($this->someOtherBotId(), $connection);
    }

    public function testWhenSomeOfMeetingInvitationsAreSentThenTheRestOfInvitationsAreSent()
    {
        $connection = new ApplicationConnection();
        $this->seedUser($this->firstUserId(), $connection);
        $this->seedUser($this->secondUserId(), $connection);
        // first meeting
        $this->seedBot($this->botId(), $connection);
        $this->seedMeetingRound($this->meetingRoundId(), $this->botId(), $connection);
        $this->seedOneSentAndOneNewMeetingRoundInvitations($this->meetingRoundId(), $connection);
        // second meeting
        $this->seedBot($this->someOtherBotId(), $connection);
        $this->seedMeetingRound($this->someOtherMeetingRoundId(), $this->someOtherBotId(), $connection);
        $this->seedNewMeetingRoundInvitations($this->someOtherMeetingRoundId(), $connection);

        $transport = new Indifferent();

        $response =
            (new InvitesToTakePartInANewRound(
                $this->botId(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertAllInvitationsAreSent($this->botId(), $connection);
        $this->assertAllInvitationsAreNew($this->someOtherBotId(), $connection);
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function seedUser(UserId $userId, OpenConnection $connection)
    {
        (new User($connection))
            ->insert([
                ['id' => $userId->value(), 'telegram_id' => mt_rand(1, 999999)]
            ]);
    }

    private function seedBot(BotId $botId, OpenConnection $connection)
    {
        (new Bot($connection))
            ->insert([
                ['id' => $botId->value(),]
            ]);
    }

    private function seedMeetingRound(string $meetingRoundId, BotId $botId, OpenConnection $connection)
    {
        (new MeetingRound($connection))
            ->insert([
                ['id' => $meetingRoundId, 'bot_id' => $botId->value()]
            ]);
    }

    private function seedSentMeetingRoundInvitations(string $meetingRoundId, OpenConnection $connection)
    {
        (new MeetingRoundInvitation($connection))
            ->insert([
                ['meeting_round_id' => $meetingRoundId, 'user_id' => $this->firstUserId()->value(), 'status' => (new Sent())->value()],
                ['meeting_round_id' => $meetingRoundId, 'user_id' => $this->secondUserId()->value(), 'status' => (new Sent())->value()],
            ]);
    }

    private function seedNewMeetingRoundInvitations(string $meetingRoundId, OpenConnection $connection)
    {
        (new MeetingRoundInvitation($connection))
            ->insert([
                ['meeting_round_id' => $meetingRoundId, 'user_id' => $this->firstUserId()->value(), 'status' => (new _New())->value()],
                ['meeting_round_id' => $meetingRoundId, 'user_id' => $this->secondUserId()->value(), 'status' => (new _New())->value()],
            ]);
    }

    private function seedOneSentAndOneNewMeetingRoundInvitations(string $meetingRoundId, OpenConnection $connection)
    {
        (new MeetingRoundInvitation($connection))
            ->insert([
                ['meeting_round_id' => $meetingRoundId, 'user_id' => $this->firstUserId()->value(), 'status' => (new Sent())->value()],
                ['meeting_round_id' => $meetingRoundId, 'user_id' => $this->secondUserId()->value(), 'status' => (new _New())->value()],
            ]);
    }

    private function botId(): BotId
    {
        return new FromUuid(new Fixed());
    }

    private function someOtherBotId(): BotId
    {
        return new FromUuid(new FromString('6ad926cc-6956-457e-a44d-bae2064263e2'));
    }

    private function meetingRoundId(): string
    {
        return 'a49926cc-6956-457e-a44d-bae206426a8c';
    }

    private function someOtherMeetingRoundId(): string
    {
        return 'b5d926cc-6956-457e-a44d-bae206426d98';
    }

    private function firstUserId(): UserId
    {
        return new UserIdFromUuid(new FromString('5fe926cc-6956-457e-a44d-bae206426d1f'));
    }

    private function secondUserId(): UserId
    {
        return new UserIdFromUuid(new FromString('bfd294ba-18f6-4dc0-ab35-8dc90ac4475b'));
    }

    private function assertAllInvitationsAreSent(BotId $botId, OpenConnection $connection)
    {
        array_map(
            function (array $record) {
                $this->assertTrue((new FromInteger($record['status']))->equals(new Sent()));
            },
            (new Selecting(
                <<<q
select mri.status
from meeting_round_invitation mri
    join meeting_round mr on mri.meeting_round_id = mr.id
    join bot b on b.id = mr.bot_id
where mr.bot_id = ?
q
                ,
                [$botId->value()],
                $connection
            ))
                ->response()->pure()->raw()
        );
    }

    private function assertAllInvitationsAreNew(BotId $botId, OpenConnection $connection)
    {
        array_map(
            function (array $record) {
                $this->assertTrue((new FromInteger($record['status']))->equals(new _New()));
            },
            (new Selecting(
                <<<q
select mri.status
from meeting_round_invitation mri
    join meeting_round mr on mri.meeting_round_id = mr.id
    join bot b on b.id = mr.bot_id
where mr.bot_id = ?
q
                ,
                [$botId->value()],
                $connection
            ))
                ->response()->pure()->raw()
        );
    }
}