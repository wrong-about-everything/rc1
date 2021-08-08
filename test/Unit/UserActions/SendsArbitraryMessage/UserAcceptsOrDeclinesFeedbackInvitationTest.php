<?php

declare(strict_types=1);

namespace RC\Tests\Unit\UserActions\SendsArbitraryMessage;

use Meringue\ISO8601DateTime;
use Meringue\ISO8601Interval\Floating\OneHour;
use Meringue\Timeline\Point\Future;
use Meringue\Timeline\Point\Now;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RC\Domain\BooleanAnswer\BooleanAnswerName\No;
use RC\Domain\BooleanAnswer\BooleanAnswerName\Yes;
use RC\Domain\FeedbackInvitation\FeedbackInvitationId\Impure\FromPure as ImpureFeedbackInvitationIdFromPure;
use RC\Domain\FeedbackInvitation\FeedbackInvitationId\Pure\FeedbackInvitationId;
use RC\Domain\FeedbackInvitation\FeedbackInvitationId\Pure\FromString as FeedbackInvitationIdFromString;
use RC\Domain\FeedbackInvitation\ReadModel\ById;
use RC\Domain\FeedbackInvitation\Status\Impure\FromFeedbackInvitation;
use RC\Domain\FeedbackInvitation\Status\Impure\FromPure as ImpureFeedbackInvitationStatusFromPure;
use RC\Domain\FeedbackInvitation\Status\Pure\Declined;
use RC\Domain\FeedbackInvitation\Status\Pure\Sent;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Domain\MeetingRound\MeetingRoundId\Pure\FromString as MeetingRoundIdFromString;
use RC\Domain\MeetingRound\MeetingRoundId\Pure\MeetingRoundId;
use RC\Domain\Participant\ParticipantId\Pure\FromString as ParticipantIdFromString;
use RC\Domain\Participant\ParticipantId\Pure\ParticipantId;
use RC\Domain\FeedbackInvitation\Status\Pure\Status;
use RC\Domain\User\UserId\FromUuid as UserIdFromUuid;
use RC\Domain\User\UserId\UserId;
use RC\Domain\User\UserStatus\Pure\Registered;
use RC\Domain\User\UserStatus\Pure\UserStatus;
use RC\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery;
use RC\Infrastructure\Http\Request\Url\Query\FromUrl;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\Http\Transport\Indifferent;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotId\FromUuid;
use RC\Infrastructure\Logging\Logs\DevNull;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromInteger;
use RC\Infrastructure\TelegramBot\UserId\Pure\TelegramUserId;
use RC\Infrastructure\Uuid\Fixed;
use RC\Infrastructure\Uuid\FromString;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Stub\Table\Bot;
use RC\Tests\Infrastructure\Stub\Table\BotUser;
use RC\Tests\Infrastructure\Stub\Table\FeedbackInvitation;
use RC\Tests\Infrastructure\Stub\Table\FeedbackQuestion;
use RC\Tests\Infrastructure\Stub\Table\MeetingRound;
use RC\Tests\Infrastructure\Stub\Table\MeetingRoundParticipant;
use RC\Tests\Infrastructure\Stub\Table\TelegramUser;
use RC\Tests\Infrastructure\Stub\TelegramMessage\UserMessage;
use RC\UserActions\SendsArbitraryMessage\SendsArbitraryMessage;

class UserAcceptsOrDeclinesFeedbackInvitationTest extends TestCase
{
    public function testWhenUserDeclinesFeedbackInvitationThenInvitationBecomesDeclinedAndHeSeesSeeYouNextTimeMessage()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $connection);
        $this->createTelegramUser($this->firstUserId(), $this->firstTelegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->firstUserId(), new Registered(), $connection);
        $this->createMeetingRound($this->meetingRoundId(), $this->botId(), new Future(new Now(), new OneHour()), new Now(), $connection);
        $this->createParticipant($this->meetingRoundId(), $this->firstParticipantId(), $this->firstUserId(), $connection);
        $this->createFeedbackInvitation($this->feedbackInvitationId(), $this->firstParticipantId(), new Sent(), $connection);
        $transport = new Indifferent();

        $response = $this->userReplies($this->firstTelegramUserId(), (new No())->value(), new Now(), $transport, $connection);

        $this->assertTrue($response->isSuccessful());
        $this->assertFeedbackInvitationIsDeclined($this->feedbackInvitationId(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Тогда до следующего раза! Если хотите что-то спросить или уточнить, смело пишите на @gorgonzola_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenUserAcceptsFeedbackInvitationThenInvitationBecomesAcceptedAndHeSeesTheFirstFeedbackQuestion()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $connection);
        $this->createTelegramUser($this->firstUserId(), $this->firstTelegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->firstUserId(), new Registered(), $connection);
        $this->createMeetingRound($this->meetingRoundId(), $this->botId(), new Future(new Now(), new OneHour()), new Now(), $connection);
        $this->createParticipant($this->meetingRoundId(), $this->firstParticipantId(), $this->firstUserId(), $connection);
        $this->createFeedbackInvitation($this->feedbackInvitationId(), $this->firstParticipantId(), new Sent(), $connection);
        $this->createFeedbackQuestion($this->meetingRoundId(), $connection);
        $transport = new Indifferent();

        $response = $this->userReplies($this->firstTelegramUserId(), (new Yes())->value(), new Now(), $transport, $connection);

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'как дела?',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function userReplies(TelegramUserId $who, string $what, ISO8601DateTime $when, HttpTransport $transport, OpenConnection $connection)
    {
        return
            (new SendsArbitraryMessage(
                $when,
                (new UserMessage($who, $what))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();
    }

    private function createBot(BotId $botId, OpenConnection $connection)
    {
        (new Bot($connection))
            ->insert([
                ['id' => $botId->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'vasya_bot']
            ]);
    }

    private function createTelegramUser(UserId $userId, TelegramUserId $telegramUserId, $connection)
    {
        (new TelegramUser($connection))
            ->insert([
                ['id' => $userId->value(), 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $telegramUserId->value(), 'telegram_handle' => 'dremuchee_bydlo'],
            ]);
    }

    private function createBotUser(BotId $botId, UserId $userId, UserStatus $status, $connection)
    {
        (new BotUser($connection))
            ->insert([
                ['bot_id' => $botId->value(), 'user_id' => $userId->value(), 'status' => $status->value()]
            ]);
    }

    private function createMeetingRound(MeetingRoundId $meetingRoundId, BotId $botId, ISO8601DateTime $startDateTime, ISO8601DateTime $invitationDateTime, $connection)
    {
        (new MeetingRound($connection))
            ->insert([
                ['id' => $meetingRoundId->value(), 'bot_id' => $botId->value(), 'start_date' => $startDateTime->value(), 'invitation_date' => $invitationDateTime->value()]
            ]);
    }

    private function createParticipant(MeetingRoundId $meetingRoundId, ParticipantId $participantId, UserId $userId, OpenConnection $connection)
    {
        (new MeetingRoundParticipant($connection))
            ->insert([
                ['id' => $participantId->value(), 'user_id' => $userId->value(), 'meeting_round_id' => $meetingRoundId->value()]
            ]);
    }

    private function createFeedbackInvitation(FeedbackInvitationId $feedbackInvitationId, ParticipantId $participantId, Status $status, OpenConnection $connection)
    {
        (new FeedbackInvitation($connection))
            ->insert([
                ['id' => $feedbackInvitationId->value(), 'participant_id' => $participantId->value(), 'status' => $status->value()]
            ]);
    }

    private function createFeedbackQuestion(MeetingRoundId $meetingRoundId, OpenConnection $connection)
    {
        (new FeedbackQuestion($connection))
            ->insert([
                ['meeting_round_id' => $meetingRoundId->value()]
            ]);
    }

    private function firstTelegramUserId(): TelegramUserId
    {
        return new FromInteger(654987);
    }

    private function firstParticipantId(): ParticipantId
    {
        return new ParticipantIdFromString('222729d6-330c-4123-b856-d5196812deee');
    }

    private function botId(): BotId
    {
        return new FromUuid(new Fixed());
    }

    private function meetingRoundId(): MeetingRoundId
    {
        return new MeetingRoundIdFromString('e00729d6-330c-4123-b856-d5196812d111');
    }

    private function feedbackInvitationId(): FeedbackInvitationId
    {
        return new FeedbackInvitationIdFromString('111729d6-330c-4123-b856-d5196812dfff');
    }

    private function firstUserId(): UserId
    {
        return new UserIdFromUuid(new FromString('103729d6-330c-4123-b856-d5196812d509'));
    }

    private function assertFeedbackInvitationIsDeclined(FeedbackInvitationId $feedbackInvitationId, OpenConnection $connection)
    {
        $this->assertTrue(
            (new FromFeedbackInvitation(
                new ById(new ImpureFeedbackInvitationIdFromPure($feedbackInvitationId), $connection)
            ))
                ->equals(
                    new ImpureFeedbackInvitationStatusFromPure(new Declined())
                )
        );
    }
}