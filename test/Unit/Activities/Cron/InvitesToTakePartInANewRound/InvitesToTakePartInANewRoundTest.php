<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Activities\Cron\InvitesToTakePartInANewRound;

use Meringue\ISO8601DateTime;
use Meringue\ISO8601Interval\Floating\NHours;
use Meringue\Timeline\Point\Future;
use Meringue\Timeline\Point\Now;
use Meringue\Timeline\Point\Past;
use PHPUnit\Framework\TestCase;
use RC\Domain\BooleanAnswer\BooleanAnswerName\Sure;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotId\FromUuid;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Domain\MeetingRound\MeetingRoundId\Pure\FromString as RoundId;
use RC\Domain\MeetingRound\MeetingRoundId\Pure\MeetingRoundId;
use RC\Domain\Participant\ReadModel\ByMeetingRoundAndUser;
use RC\Domain\Participant\Status\Impure\FromPure;
use RC\Domain\Participant\Status\Impure\FromReadModelParticipant;
use RC\Domain\Participant\Status\Pure\Registered;
use RC\Domain\RoundInvitation\ReadModel\ByMeetingRoundIdAndUserId;
use RC\Domain\RoundInvitation\Status\Impure\FromInvitation;
use RC\Domain\RoundInvitation\Status\Impure\FromPure as ImpureInvitationStatusFromPure;
use RC\Domain\RoundInvitation\Status\Pure\_New;
use RC\Domain\RoundInvitation\Status\Pure\Accepted;
use RC\Domain\RoundInvitation\Status\Pure\Declined;
use RC\Domain\RoundInvitation\Status\Pure\ErrorDuringSending;
use RC\Domain\RoundInvitation\Status\Pure\FromInteger;
use RC\Domain\RoundInvitation\Status\Pure\Sent;
use RC\Domain\RoundInvitation\Status\Pure\Status;
use RC\Domain\TelegramUser\ByTelegramId;
use RC\Domain\TelegramUser\UserId\Impure\FromTelegramUser;
use RC\Domain\TelegramUser\UserId\Impure\TelegramUserId as ImpureTelegramUserId;
use RC\Domain\TelegramUser\UserId\Pure\FromImpure;
use RC\Domain\TelegramUser\UserId\Pure\FromUuid as UserIdFromUuid;
use RC\Domain\TelegramUser\UserId\Pure\TelegramUserId;
use RC\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery;
use RC\Infrastructure\Http\Request\Url\Query\FromUrl;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\Http\Transport\Indifferent;
use RC\Infrastructure\Logging\Logs\DevNull;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Infrastructure\TelegramBot\UserCommand\Start;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromInteger as TelegramUserIdFromInteger;
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId;
use RC\Infrastructure\Uuid\Fixed;
use RC\Infrastructure\Uuid\FromString;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Stub\Table\Bot;
use RC\Tests\Infrastructure\Stub\Table\MeetingRound;
use RC\Tests\Infrastructure\Stub\Table\MeetingRoundInvitation;
use RC\Tests\Infrastructure\Stub\Table\MeetingRoundParticipant;
use RC\Tests\Infrastructure\Stub\Table\TelegramUser;
use RC\Activities\Cron\InvitesToTakePartInANewRound\InvitesToTakePartInANewRound;
use RC\Tests\Infrastructure\Stub\TelegramMessage\UserMessage;
use RC\UserActions\PressesStart\PressesStart;
use RC\UserActions\SendsArbitraryMessage\SendsArbitraryMessage;

class InvitesToTakePartInANewRoundTest extends TestCase
{
    public function testWhenAllMeetingInvitationsAreSentThenNoInvitationIsSent()
    {
        $connection = new ApplicationConnection();
        $this->seedUser($this->firstUserId(), $connection);
        $this->seedUser($this->secondUserId(), $connection);
        // first meeting
        $this->seedBot($this->botId(), $connection);
        $this->seedMeetingRound($this->meetingRoundId(), $this->botId(), new Now(), $connection);
        $this->seedSentMeetingRoundInvitations($this->meetingRoundId(), $connection);
        // second meeting
        $this->seedBot($this->someOtherBotId(), $connection);
        $this->seedMeetingRound($this->someOtherMeetingRoundId(), $this->someOtherBotId(), new Now(), $connection);
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
        $this->assertAllInvitationsAreSent($this->meetingRoundId(), $connection);
        $this->assertAllInvitationsAreNew($this->someOtherMeetingRoundId(), $connection);
    }

    public function testWhenNoneOfMeetingInvitationsAreSentThenTheFirst100InvitationsAreSent()
    {
        $connection = new ApplicationConnection();
        $this->seedUser($this->firstUserId(), $connection);
        $this->seedUser($this->secondUserId(), $connection);
        // first meeting
        $this->seedBot($this->botId(), $connection);
        $this->seedMeetingRound($this->meetingRoundId(), $this->botId(), new Now(), $connection);
        $this->seedNewMeetingRoundInvitations($this->meetingRoundId(), $connection);
        // second meeting
        $this->seedBot($this->someOtherBotId(), $connection);
        $this->seedMeetingRound($this->someOtherMeetingRoundId(), $this->someOtherBotId(), new Now(), $connection);
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
        $this->assertAllInvitationsAreSent($this->meetingRoundId(), $connection);
        $this->assertAllInvitationsAreNew($this->someOtherMeetingRoundId(), $connection);
    }

    public function testGivenTwoRoundsWhenNoneOfInvitationsAreSentThenTheFirst100InvitationsAreSentForTheMeetingWithMatchingInvitationDate()
    {
        $connection = new ApplicationConnection();
        $this->seedUser($this->firstUserId(), $connection);
        $this->seedUser($this->secondUserId(), $connection);
        // first meeting
        $this->seedBot($this->botId(), $connection);
        $this->seedMeetingRound($this->meetingRoundId(), $this->botId(), new Now(), $connection);
        $this->seedNewMeetingRoundInvitations($this->meetingRoundId(), $connection);
        // second meeting
        $this->seedMeetingRound($this->someOtherMeetingRoundId(), $this->botId(), new Future(new Now(), new NHours(1)), $connection);
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
        $this->assertAllInvitationsAreSent($this->meetingRoundId(), $connection);
        $this->assertAllInvitationsAreNew($this->someOtherMeetingRoundId(), $connection);
    }

    public function testGivenOneRoundHasPassedAndTheOtherIsNewWhenNoneOfInvitationsAreSentThenTheFirst100InvitationsAreSentForTheMeetingWithMatchingInvitationDate()
    {
        $connection = new ApplicationConnection();
        $this->seedUser($this->firstUserId(), $connection);
        $this->seedUser($this->secondUserId(), $connection);
        // first meeting
        $this->seedBot($this->botId(), $connection);
        $this->seedMeetingRound($this->meetingRoundId(), $this->botId(), new Now(), $connection);
        $this->seedNewMeetingRoundInvitations($this->meetingRoundId(), $connection);
        // second meeting
        $this->seedMeetingRound($this->someOtherMeetingRoundId(), $this->botId(), new Past(new Now(), new NHours(1)), $connection);
        $this->seedAcceptedMeetingRoundInvitations($this->someOtherMeetingRoundId(), $connection);
        $this->seedMeetingRoundParticipants($this->someOtherMeetingRoundId(), $connection);

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
        $this->assertAllInvitationsAreSent($this->meetingRoundId(), $connection);
        $this->assertAllInvitationsAreAccepted($this->someOtherMeetingRoundId(), $connection);
    }

    public function testOnlyNewAndErroneousInvitationsAreSent()
    {
        $connection = new ApplicationConnection();
        $this->seedUser($this->firstUserId(), $connection);
        $this->seedUser($this->secondUserId(), $connection);
        $this->seedUser($this->thirdUserId(), $connection);
        $this->seedUser($this->fourthUserId(), $connection);
        $this->seedUser($this->fifthUserId(), $connection);
        // first meeting
        $this->seedBot($this->botId(), $connection);
        $this->seedMeetingRound($this->meetingRoundId(), $this->botId(), new Now(), $connection);
        $this->seedInvitation($this->meetingRoundId(), $this->firstUserId(), new Sent(), $connection);
        $this->seedInvitation($this->meetingRoundId(), $this->secondUserId(), new _New(), $connection);
        $this->seedInvitation($this->meetingRoundId(), $this->thirdUserId(), new ErrorDuringSending(), $connection);
        $this->seedInvitation($this->meetingRoundId(), $this->fourthUserId(), new Accepted(), $connection);
        $this->seedInvitation($this->meetingRoundId(), $this->fifthUserId(), new Declined(), $connection);
        // second meeting
        $this->seedBot($this->someOtherBotId(), $connection);
        $this->seedMeetingRound($this->someOtherMeetingRoundId(), $this->someOtherBotId(), new Now(), $connection);
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
        $this->assertAllInvitationsAreSent($this->meetingRoundId(), $connection);
        $this->assertAllInvitationsAreNew($this->someOtherMeetingRoundId(), $connection);
    }

    public function testWhenSomeParticipantsAreRegisteredDuringRegistrationInBotThenInvitationsAreSentOnlyToNonParticipants()
    {
        $connection = new ApplicationConnection();
        $this->seedUser($this->firstUserId(), $connection);
        $this->seedBot($this->botId(), $connection);
        $this->seedMeetingRound($this->meetingRoundId(), $this->botId(), new Now(), $connection);
        $this->seedInvitation($this->meetingRoundId(), $this->firstUserId(), new _New(), $connection);
        $this->newUserRegistersInBotAndForANewRound($this->secondTelegramUserId(), $connection);
        $this->assertUserIsARoundParticipantWithAcceptedInvitation($this->meetingRoundId(), new FromTelegramUser(new ByTelegramId($this->secondTelegramUserId(), $connection)), $connection);

        $transport = new Indifferent();
        (new InvitesToTakePartInANewRound($this->botId(), $transport, $connection, new DevNull()))->response();
        $this->assertCount(1, $transport->sentRequests());
        $this->assertInvitationIsSent($this->meetingRoundId(), $this->firstUserId(), $connection);
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function newUserRegistersInBotAndForANewRound(InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        $transport = new Indifferent();
        $this->newUserRegistersInBot($telegramUserId, $transport, $connection);
        $this->assertEquals(
            <<<q
Спасибо за ответы!

У нас уже намечаются встречи, готовы поучаствовать? Пришлю вам пару сегодня, а когда и как встретиться, онлайн или оффлайн, договоритесь между собой.
q
            ,
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );

        $this->newUserAcceptsAnInvitationForARoundWithoutRegistrationQuestionAndGetsRegisteredRightAway($telegramUserId, $transport, $connection);
        $this->assertEquals(
            'Поздравляю, вы зарегистрировались! Сегодня пришлю вам пару для разговора. Если хотите что-то спросить или уточнить, смело пишите на @gorgonzola_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[1]->url())))->value()['text']
        );
    }

    private function newUserRegistersInBot(InternalTelegramUserId $telegramUserId, HttpTransport $transport, OpenConnection $connection)
    {
        (new PressesStart(
            (new UserMessage($telegramUserId, (new Start())->value()))->value(),
            $this->botId()->value(),
            $transport,
            $connection,
            new DevNull()
        ))
            ->response();
    }

    private function newUserAcceptsAnInvitationForARoundWithoutRegistrationQuestionAndGetsRegisteredRightAway(InternalTelegramUserId $telegramUserId, HttpTransport $transport, OpenConnection $connection)
    {
        (new SendsArbitraryMessage(
            new Now(),
            (new UserMessage($telegramUserId, (new Sure())->value()))->value(),
            $this->botId()->value(),
            $transport,
            $connection,
            new DevNull()
        ))
            ->response();
    }

    private function secondTelegramUserId(): InternalTelegramUserId
    {
        return new TelegramUserIdFromInteger(231654987);
    }

    private function seedUser(TelegramUserId $userId, OpenConnection $connection)
    {
        (new TelegramUser($connection))
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

    private function seedMeetingRound(MeetingRoundId $meetingRoundId, BotId $botId, ISO8601DateTime $invitationDate, OpenConnection $connection)
    {
        (new MeetingRound($connection))
            ->insert([
                ['id' => $meetingRoundId->value(), 'bot_id' => $botId->value(), 'invitation_date' => $invitationDate->value(), 'start_date' => (new Future(new Now(), new NHours(2)))->value()]
            ]);
    }

    private function seedSentMeetingRoundInvitations(MeetingRoundId $meetingRoundId, OpenConnection $connection)
    {
        (new MeetingRoundInvitation($connection))
            ->insert([
                ['meeting_round_id' => $meetingRoundId->value(), 'user_id' => $this->firstUserId()->value(), 'status' => (new Sent())->value()],
                ['meeting_round_id' => $meetingRoundId->value(), 'user_id' => $this->secondUserId()->value(), 'status' => (new Sent())->value()],
            ]);
    }

    private function seedNewMeetingRoundInvitations(MeetingRoundId $meetingRoundId, OpenConnection $connection)
    {
        (new MeetingRoundInvitation($connection))
            ->insert([
                ['meeting_round_id' => $meetingRoundId->value(), 'user_id' => $this->firstUserId()->value(), 'status' => (new _New())->value()],
                ['meeting_round_id' => $meetingRoundId->value(), 'user_id' => $this->secondUserId()->value(), 'status' => (new _New())->value()],
            ]);
    }

    private function seedAcceptedMeetingRoundInvitations(MeetingRoundId $meetingRoundId, OpenConnection $connection)
    {
        (new MeetingRoundInvitation($connection))
            ->insert([
                ['meeting_round_id' => $meetingRoundId->value(), 'user_id' => $this->firstUserId()->value(), 'status' => (new Accepted())->value()],
                ['meeting_round_id' => $meetingRoundId->value(), 'user_id' => $this->secondUserId()->value(), 'status' => (new Accepted())->value()],
            ]);
    }

    private function seedMeetingRoundParticipants(MeetingRoundId $meetingRoundId, OpenConnection $connection)
    {
        (new MeetingRoundParticipant($connection))
            ->insert([
                ['meeting_round_id' => $meetingRoundId->value(), 'user_id' => $this->firstUserId()->value(), 'status' => (new Registered())->value()],
                ['meeting_round_id' => $meetingRoundId->value(), 'user_id' => $this->secondUserId()->value(), 'status' => (new Registered())->value()],
            ]);
    }

    private function seedInvitation(MeetingRoundId $meetingRoundId, TelegramUserId $userId, Status $status, OpenConnection $connection)
    {
        (new MeetingRoundInvitation($connection))
            ->insert([
                ['meeting_round_id' => $meetingRoundId->value(), 'user_id' => $userId->value(), 'status' => $status->value()],
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

    private function meetingRoundId(): MeetingRoundId
    {
        return new RoundId('a49926cc-6956-457e-a44d-bae206426a8c');
    }

    private function someOtherMeetingRoundId(): MeetingRoundId
    {
        return new RoundId('b5d926cc-6956-457e-a44d-bae206426d98');
    }

    private function firstUserId(): TelegramUserId
    {
        return new UserIdFromUuid(new FromString('5fe926cc-6956-457e-a44d-bae206426d1f'));
    }

    private function secondUserId(): TelegramUserId
    {
        return new UserIdFromUuid(new FromString('bfd294ba-18f6-4dc0-ab35-8dc90ac4475b'));
    }

    private function thirdUserId(): TelegramUserId
    {
        return new UserIdFromUuid(new FromString('111294ba-18f6-4dc0-ab35-8dc90ac44111'));
    }

    private function fourthUserId(): TelegramUserId
    {
        return new UserIdFromUuid(new FromString('222294ba-18f6-4dc0-ab35-8dc90ac44222'));
    }

    private function fifthUserId(): TelegramUserId
    {
        return new UserIdFromUuid(new FromString('333294ba-18f6-4dc0-ab35-8dc90ac44333'));
    }

    private function assertAllInvitationsAreSent(MeetingRoundId $meetingRoundId, OpenConnection $connection)
    {
        array_map(
            function (array $record) {
                $this->assertTrue((new FromInteger($record['status']))->equals(new Sent()));
            },
            array_filter(
                (new Selecting(
                    <<<q
select mri.status
from meeting_round_invitation mri
    join meeting_round mr on mri.meeting_round_id = mr.id
where mr.id = ?
q
                    ,
                    [$meetingRoundId->value()],
                    $connection
                ))
                    ->response()->pure()->raw(),
                function (array $record) {
                    return !(new FromInteger($record['status']))->isFinal();
                }
            )
        );
    }

    private function assertAllInvitationsAreAccepted(MeetingRoundId $meetingRoundId, OpenConnection $connection)
    {
        array_map(
            function (array $record) {
                $this->assertTrue((new FromInteger($record['status']))->equals(new Accepted()));
            },
            array_filter(
                (new Selecting(
                    <<<q
select mri.status
from meeting_round_invitation mri
    join meeting_round mr on mri.meeting_round_id = mr.id
where mr.id = ?
q
                    ,
                    [$meetingRoundId->value()],
                    $connection
                ))
                    ->response()->pure()->raw(),
                function (array $record) {
                    return !(new FromInteger($record['status']))->isFinal();
                }
            )
        );
    }

    private function assertAllInvitationsAreNew(MeetingRoundId $meetingRoundId, OpenConnection $connection)
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
where mr.id = ?
q
                ,
                [$meetingRoundId->value()],
                $connection
            ))
                ->response()->pure()->raw()
        );
    }

    private function assertUserIsARoundParticipantWithAcceptedInvitation(MeetingRoundId $meetingRoundId, ImpureTelegramUserId $userId, OpenConnection $connection)
    {
        $participant =
            new ByMeetingRoundAndUser(
                $meetingRoundId,
                new FromImpure($userId),
                $connection
            );
        $this->assertTrue($participant->exists()->pure()->raw());
        $this->assertTrue(
            (new FromReadModelParticipant($participant))
                ->equals(
                    new FromPure(new Registered())
                )
        );
        $this->assertTrue(
            (new FromInvitation(
                new ByMeetingRoundIdAndUserId($meetingRoundId, new FromImpure($userId), $connection)
            ))
                ->equals(new ImpureInvitationStatusFromPure(new Accepted()))
        );
    }

    private function assertInvitationIsSent(MeetingRoundId $meetingRoundId, TelegramUserId $userId, OpenConnection $connection)
    {
        $this->assertTrue(
            (new FromInvitation(
                new ByMeetingRoundIdAndUserId($meetingRoundId, $userId, $connection)
            ))
                ->equals(new ImpureInvitationStatusFromPure(new Sent()))
        );
    }
}