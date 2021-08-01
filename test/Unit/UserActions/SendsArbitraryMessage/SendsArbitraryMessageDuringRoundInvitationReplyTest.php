<?php

declare(strict_types=1);

namespace RC\Tests\Unit\UserActions\SendsArbitraryMessage;

use Meringue\ISO8601Interval\Floating\NMinutes;
use Meringue\ISO8601Interval\Floating\OneHour;
use Meringue\Timeline\Point\Future;
use Meringue\Timeline\Point\Now;
use Meringue\Timeline\Point\Past;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RC\Domain\BooleanAnswer\BooleanAnswerName\No;
use RC\Domain\BooleanAnswer\BooleanAnswerName\Yes;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Domain\MeetingRound\MeetingRoundId\Pure\FromString as MeetingRoundFromString;
use RC\Domain\Participant\ReadModel\ByMeetingRoundAndUser;
use RC\Domain\Participant\Status\Impure\FromReadModelParticipant;
use RC\Domain\Participant\Status\Impure\FromPure;
use RC\Domain\Participant\Status\Pure\Registered as ParticipantRegistered;
use RC\Domain\Participant\Status\Pure\RegistrationInProgress;
use RC\Domain\Participant\Status\Pure\Status;
use RC\Domain\RoundInvitation\ReadModel\LatestInvitation;
use RC\Domain\RoundInvitation\Status\Impure\FromInvitation;
use RC\Domain\RoundInvitation\Status\Impure\FromPure as ImpureStatusFromPure;
use RC\Domain\RoundInvitation\Status\Pure\Accepted;
use RC\Domain\RoundInvitation\Status\Pure\Declined;
use RC\Domain\RoundInvitation\Status\Pure\Sent;
use RC\Domain\RoundRegistrationQuestion\Type\Pure\NetworkingOrSomeSpecificArea;
use RC\Domain\RoundRegistrationQuestion\Type\Pure\SpecificAreaChoosing;
use RC\Domain\User\UserId\FromUuid as UserIdFromUuid;
use RC\Domain\User\UserId\UserId;
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
use RC\Infrastructure\Uuid\FromString;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Stub\Table\Bot;
use RC\Tests\Infrastructure\Stub\Table\BotUser;
use RC\Tests\Infrastructure\Stub\Table\MeetingRound;
use RC\Tests\Infrastructure\Stub\Table\MeetingRoundInvitation;
use RC\Tests\Infrastructure\Stub\Table\RoundRegistrationQuestion;
use RC\Tests\Infrastructure\Stub\Table\UserRegistrationProgress;
use RC\Tests\Infrastructure\Stub\TelegramMessage\UserMessage;
use RC\UserActions\SendsArbitraryMessage\SendsArbitraryMessage;

class SendsArbitraryMessageDuringRoundInvitationReplyTest extends TestCase
{
    public function testWhenUserDeclinesRoundInvitationThenInvitationBecomesDeclinedAndHeSeesSeeYouNextTimeMessage()
    {
        $connection = new ApplicationConnection();
        (new Bot($connection))
            ->insert([
                ['id' => $this->botId()->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'vasya_bot']
            ]);
        (new BotUser($this->botId(), $connection))
            ->insert(
                ['id' => $this->firstUserId()->value(), 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $this->firstTelegramUserId()->value(), 'telegram_handle' => 'dremuchee_bydlo'],
                ['status' => (new Registered())->value()]
            );
        (new MeetingRound($connection))
            ->insert([
                ['id' => $this->meetingRoundId(), 'bot_id' => $this->botId()->value(), 'start_date' => (new Future(new Now(), new OneHour()))->value()]
            ]);
        (new MeetingRoundInvitation($connection))
            ->insert([
                ['id' => $this->meetingRoundInvitationId(), 'meeting_round_id' => $this->meetingRoundId(), 'user_id' => $this->firstUserId()->value(), 'status' => (new Sent())->value()]
            ]);
        $transport = new Indifferent();

        $response =
            (new SendsArbitraryMessage(
                (new UserMessage($this->firstTelegramUserId(), (new No())->value()))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertInvitationIsDeclined($this->firstTelegramUserId(), $this->botId(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Хорошо, тогда до следующего раза! Если хотите что-то спросить или уточнить, смело пишите на @gorgonzola_support',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
        $this->participantDoesNotExist($this->meetingRoundId(), $this->firstUserId(), $connection);
    }

    public function testGivenMeetingRoundHasNoParticipantsWhenUserAcceptsRoundInvitationThenInvitationBecomesAcceptedAndHeBecomesAParticipantWithRegistrationInProgressStatusAndHeSeesTheFirstRoundRegistrationQuestion()
    {
        $connection = new ApplicationConnection();
        (new Bot($connection))
            ->insert([
                ['id' => $this->botId()->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'vasya_bot']
            ]);
        (new BotUser($this->botId(), $connection))
            ->insert(
                ['id' => $this->firstUserId()->value(), 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $this->firstTelegramUserId()->value(), 'telegram_handle' => 'dremuchee_bydlo'],
                ['status' => (new Registered())->value()]
            );
        (new MeetingRound($connection))
            ->insert([
                ['id' => $this->meetingRoundId(), 'bot_id' => $this->botId()->value(), 'start_date' => (new Future(new Now(), new OneHour()))->value()]
            ]);
        (new MeetingRoundInvitation($connection))
            ->insert([
                ['id' => $this->meetingRoundInvitationId(), 'meeting_round_id' => $this->meetingRoundId(), 'user_id' => $this->firstUserId()->value(), 'status' => (new Sent())->value()]
            ]);
        (new RoundRegistrationQuestion($connection))
            ->insert([
                ['id' => Uuid::uuid4()->toString(), 'meeting_round_id' => $this->meetingRoundId(), 'type' => (new NetworkingOrSomeSpecificArea())->value()]
            ]);

        $transport = new Indifferent();

        $response =
            (new SendsArbitraryMessage(
                (new UserMessage($this->firstTelegramUserId(), (new Yes())->value()))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Привет, как дела, как здоровье, азаза?',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
        $this->participantExists($this->meetingRoundId(), $this->firstUserId(), $connection, new RegistrationInProgress());
    }

    public function testGivenMeetingRoundHasSomeParticipantsWhenUserAcceptsRoundInvitationThenInvitationBecomesAcceptedAndHeBecomesAParticipantWithRegistrationInProgressStatusAndHeSeesTheFirstRoundRegistrationQuestion()
    {
        $connection = new ApplicationConnection();
        (new Bot($connection))
            ->insert([
                ['id' => $this->botId()->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'vasya_bot']
            ]);
        (new BotUser($this->botId(), $connection))
            ->insert(
                ['id' => $this->firstUserId()->value(), 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $this->firstTelegramUserId()->value(), 'telegram_handle' => 'dremuchee_bydlo'],
                ['status' => (new Registered())->value()]
            );
        (new BotUser($this->botId(), $connection))
            ->insert(
                ['id' => $this->secondUserId()->value(), 'first_name' => 'Vasil', 'last_name' => 'Belov', 'telegram_id' => $this->secondTelegramUserId()->value(), 'telegram_handle' => 'vonuchee_bydlo'],
                ['status' => (new Registered())->value()]
            );
        (new MeetingRound($connection))
            ->insert([
                ['id' => $this->meetingRoundId(), 'bot_id' => $this->botId()->value(), 'start_date' => (new Future(new Now(), new OneHour()))->value()]
            ]);
        (new MeetingRoundInvitation($connection))
            ->insert([
                ['id' => $this->meetingRoundInvitationId(), 'meeting_round_id' => $this->meetingRoundId(), 'user_id' => $this->firstUserId()->value(), 'status' => (new Sent())->value()]
            ]);
        (new MeetingRoundInvitation($connection))
            ->insert([
                ['id' => 'aaa729d6-330c-4123-b856-d5196812dbbb', 'meeting_round_id' => $this->meetingRoundId(), 'user_id' => $this->secondUserId()->value(), 'status' => (new Accepted())->value()]
            ]);
        $registrationQuestionId = Uuid::uuid4()->toString();
        (new RoundRegistrationQuestion($connection))
            ->insert([
                ['id' => $registrationQuestionId, 'meeting_round_id' => $this->meetingRoundId(), 'type' => (new NetworkingOrSomeSpecificArea())->value()],
                ['id' => Uuid::uuid4()->toString(), 'meeting_round_id' => $this->meetingRoundId(), 'type' => (new SpecificAreaChoosing())->value()],
            ]);
        (new UserRegistrationProgress($connection))
            ->insert([
                ['registration_question_id' => $registrationQuestionId, 'user_id' => $this->secondUserId()->value()]
            ]);

        $transport = new Indifferent();

        $response =
            (new SendsArbitraryMessage(
                (new UserMessage($this->firstTelegramUserId(), (new Yes())->value()))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Привет, как дела, как здоровье, азаза?',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
        $this->participantExists($this->meetingRoundId(), $this->firstUserId(), $connection, new RegistrationInProgress());
    }

    public function testGivenMeetingRoundHasNoRegistrationQuestionsWhenUserAcceptsRoundInvitationThenInvitationBecomesAcceptedAndHeSeesCongratulations()
    {
        $connection = new ApplicationConnection();
        (new Bot($connection))
            ->insert([
                ['id' => $this->botId()->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'vasya_bot']
            ]);
        (new BotUser($this->botId(), $connection))
            ->insert(
                ['id' => $this->firstUserId()->value(), 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $this->firstTelegramUserId()->value(), 'telegram_handle' => 'dremuchee_bydlo'],
                ['status' => (new Registered())->value()]
            );
        (new MeetingRound($connection))
            ->insert([
                ['id' => $this->meetingRoundId(), 'bot_id' => $this->botId()->value(), 'start_date' => (new Future(new Now(), new OneHour()))->value()]
            ]);
        (new MeetingRoundInvitation($connection))
            ->insert([
                ['id' => $this->meetingRoundInvitationId(), 'meeting_round_id' => $this->meetingRoundId(), 'user_id' => $this->firstUserId()->value(), 'status' => (new Sent())->value()]
            ]);
        $transport = new Indifferent();

        $response =
            (new SendsArbitraryMessage(
                (new UserMessage($this->firstTelegramUserId(), (new Yes())->value()))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Поздравляю, вы зарегистрировались! В понедельник днём пришлю вам пару для разговора. Если хотите что-то спросить или уточнить, смело пишите на @gorgonzola_support',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
        $this->participantExists($this->meetingRoundId(), $this->firstUserId(), $connection, new ParticipantRegistered());
    }

    public function testGivenNoMeetingRoundsAheadWhenUserAcceptsInvitationThenHeSeesSorryAndSeeYouNextTime()
    {
        $connection = new ApplicationConnection();
        (new Bot($connection))
            ->insert([
                ['id' => $this->botId()->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'vasya_bot']
            ]);
        (new BotUser($this->botId(), $connection))
            ->insert(
                ['id' => $this->firstUserId()->value(), 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $this->firstTelegramUserId()->value(), 'telegram_handle' => 'dremuchee_bydlo'],
                ['status' => (new Registered())->value()]
            );
        (new MeetingRound($connection))
            ->insert([
                ['id' => $this->meetingRoundId(), 'start_date' => (new Past(new Now(), new NMinutes(1)))->value(), 'bot_id' => $this->botId()->value()]
            ]);
        (new MeetingRoundInvitation($connection))
            ->insert([
                ['id' => $this->meetingRoundInvitationId(), 'meeting_round_id' => $this->meetingRoundId(), 'user_id' => $this->firstUserId()->value(), 'status' => (new Sent())->value()]
            ]);
        $transport = new Indifferent();

        $response =
            (new SendsArbitraryMessage(
                (new UserMessage($this->firstTelegramUserId(), (new Yes())->value()))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Раунд встреч уже идёт или уже прошёл. Мы пришлём вам приглашение на новый раунд, как только о нём станет известно. Если хотите что-то спросить или уточнить, смело пишите на @gorgonzola_support',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
        $this->participantDoesNotExist($this->meetingRoundId(), $this->firstUserId(), $connection);
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function firstTelegramUserId(): TelegramUserId
    {
        return new FromInteger(654987);
    }

    private function secondTelegramUserId(): TelegramUserId
    {
        return new FromInteger(123456);
    }

    private function botId(): BotId
    {
        return new FromUuid(new Fixed());
    }

    private function meetingRoundId(): string
    {
        return 'e00729d6-330c-4123-b856-d5196812d111';
    }

    private function meetingRoundInvitationId(): string
    {
        return '333729d6-330c-4123-b856-d5196812d444';
    }

    private function firstUserId(): UserId
    {
        return new UserIdFromUuid(new FromString('103729d6-330c-4123-b856-d5196812d509'));
    }

    private function secondUserId(): UserId
    {
        return new UserIdFromUuid(new FromString('abc729d6-330c-4123-b856-d5196812ddef'));
    }

    private function assertInvitationIsDeclined(TelegramUserId $telegramUserId, BotId $botId, OpenConnection $connection)
    {
        $this->assertTrue(
            (new FromInvitation(
                new LatestInvitation($telegramUserId, $botId, $connection)
            ))
                ->equals(
                    new ImpureStatusFromPure(new Declined())
                )
        );
    }

    private function participantExists(string $meetingRoundId, UserId $userId, OpenConnection $connection, Status $status)
    {
        $participant =
            new ByMeetingRoundAndUser(
                new MeetingRoundFromString($meetingRoundId),
                $userId,
                $connection
            );
        $this->assertTrue($participant->exists()->pure()->raw());
        $this->assertTrue(
            (new FromReadModelParticipant($participant))
                ->equals(
                    new FromPure($status)
                )
        );
    }

    private function participantDoesNotExist(string $meetingRoundId, UserId $userId, OpenConnection $connection)
    {
        $this->assertFalse(
            (new ByMeetingRoundAndUser(
                new MeetingRoundFromString($meetingRoundId),
                $userId,
                $connection
            ))
                ->exists()->pure()->raw()
        );
    }
}