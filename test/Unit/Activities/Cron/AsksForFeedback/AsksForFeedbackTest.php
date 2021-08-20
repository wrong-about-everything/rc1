<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Activities\Cron\AsksForFeedback;

use Meringue\ISO8601DateTime;
use Meringue\Timeline\Point\Now;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RC\Activities\Cron\AsksForFeedback\AsksForFeedback;
use RC\Activities\User\RepliesToFeedbackInvitation\UserStories\AcceptsOrDeclinesInvitation\AcceptsOrDeclinesFeedbackInvitation;
use RC\Domain\BooleanAnswer\BooleanAnswerName\No;
use RC\Domain\BooleanAnswer\BooleanAnswerName\Sure;
use RC\Domain\BooleanAnswer\BooleanAnswerName\Yes;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotId\FromUuid;
use RC\Domain\FeedbackInvitation\Status\Pure\Accepted;
use RC\Domain\FeedbackInvitation\Status\Pure\Declined;
use RC\Domain\FeedbackInvitation\Status\Pure\Generated;
use RC\Domain\FeedbackInvitation\Status\Pure\Sent;
use RC\Domain\FeedbackInvitation\Status\Pure\Status;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Domain\MeetingRound\MeetingRoundId\Pure\FromString as RoundId;
use RC\Domain\MeetingRound\MeetingRoundId\Pure\MeetingRoundId;
use RC\Domain\Participant\ParticipantId\Pure\FromString as ParticipantIdFromString;
use RC\Domain\Participant\ParticipantId\Pure\ParticipantId;
use RC\Domain\Participant\Status\Pure\Registered;
use RC\Domain\TelegramUser\UserId\TelegramUserId;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\Http\Transport\Indifferent;
use RC\Infrastructure\Logging\Logs\DevNull;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromInteger;
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId;
use RC\Infrastructure\Uuid\FromString;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Stub\Table\Bot;
use RC\Tests\Infrastructure\Stub\Table\MeetingRound;
use RC\Tests\Infrastructure\Stub\Table\MeetingRoundPair;
use RC\Tests\Infrastructure\Stub\Table\MeetingRoundParticipant;
use RC\Tests\Infrastructure\Stub\Table\TelegramUser;
use RC\Tests\Infrastructure\Stub\TelegramMessage\UserMessage;

class AsksForFeedbackTest extends TestCase
{
    public function testWhenInvitationsAreNotGeneratedThenTheyAreGeneratedForParticipantsThatHadPairsInPastRoundAndThenAreSent()
    {
        $connection = new ApplicationConnection();
        $transport = new Indifferent();
        $this->seedBot($this->botId(), $connection);
        $this->seedMeetingRound($this->meetingRoundId(), $this->botId(), new Now(), $connection);
        $this->seedParticipant($this->meetingRoundId(), $this->firstUserInternalTelegramId(), $this->firstParticipantId(), $connection);
        $this->seedPairFor($this->firstParticipantId(), $this->secondParticipantId(), $connection);
        $this->seedParticipant($this->meetingRoundId(), $this->secondUserInternalTelegramId(), $this->secondParticipantId(), $connection);
        $this->seedPairFor($this->secondParticipantId(), $this->firstParticipantId(), $connection);
        $this->seedParticipant($this->meetingRoundId(), $this->thirdUserInternalTelegramId(), $this->thirdParticipantId(), $connection);

        $this->assertFeedbackInvitationsAre(new Generated(), 0, $connection);
        $this->assertFeedbackInvitationsAre(new Sent(), 0, $connection);

        $this->cronRequest($transport, $connection);

        $this->assertFeedbackInvitationsAre(new Sent(), 2, $connection);
        $this->assertCount(2, $transport->sentRequests());
    }

    public function testWhenInvitationsAreGeneratedAndSentDuringFirstCronRequestThenTheyAreNeitherGeneratedNorSentDuringSecondCronRequest()
    {
        $connection = new ApplicationConnection();
        $transport = new Indifferent();
        $this->seedBot($this->botId(), $connection);
        $this->seedMeetingRound($this->meetingRoundId(), $this->botId(), new Now(), $connection);
        $this->seedParticipant($this->meetingRoundId(), $this->firstUserInternalTelegramId(), $this->firstParticipantId(), $connection);
        $this->seedPairFor($this->firstParticipantId(), $this->secondParticipantId(), $connection);
        $this->seedParticipant($this->meetingRoundId(), $this->secondUserInternalTelegramId(), $this->secondParticipantId(), $connection);
        $this->seedPairFor($this->secondParticipantId(), $this->firstParticipantId(), $connection);
        $this->seedParticipant($this->meetingRoundId(), $this->thirdUserInternalTelegramId(), $this->thirdParticipantId(), $connection);

        $this->assertFeedbackInvitationsAre(new Generated(), 0, $connection);
        $this->assertFeedbackInvitationsAre(new Sent(), 0, $connection);

        $this->cronRequest($transport, $connection);

        $this->assertFeedbackInvitationsAre(new Sent(), 2, $connection);
        $this->assertFeedbackInvitationsAre(new Generated(), 0, $connection);
        $this->assertCount(2, $transport->sentRequests());

        $this->cronRequest($transport, $connection);

        $this->assertFeedbackInvitationsAre(new Sent(), 2, $connection);
        $this->assertCount(2, $transport->sentRequests());
    }

    public function testWhenInvitationsAreGeneratedAndSentDuringFirstCronRequestAndOneOfThemIsAcceptedAndTheOtherIsDeclinedThenTheyAreNeitherGeneratedNorSentDuringSecondCronRequest()
    {
        $connection = new ApplicationConnection();
        $transport = new Indifferent();
        $this->seedBot($this->botId(), $connection);
        $this->seedMeetingRound($this->meetingRoundId(), $this->botId(), new Now(), $connection);
        $this->seedParticipant($this->meetingRoundId(), $this->firstUserInternalTelegramId(), $this->firstParticipantId(), $connection);
        $this->seedPairFor($this->firstParticipantId(), $this->secondParticipantId(), $connection);
        $this->seedParticipant($this->meetingRoundId(), $this->secondUserInternalTelegramId(), $this->secondParticipantId(), $connection);
        $this->seedPairFor($this->secondParticipantId(), $this->firstParticipantId(), $connection);
        $this->seedParticipant($this->meetingRoundId(), $this->thirdUserInternalTelegramId(), $this->thirdParticipantId(), $connection);

        $this->assertFeedbackInvitationsAre(new Generated(), 0, $connection);
        $this->assertFeedbackInvitationsAre(new Sent(), 0, $connection);

        $this->cronRequest($transport, $connection);

        $this->assertFeedbackInvitationsAre(new Sent(), 2, $connection);
        $this->assertFeedbackInvitationsAre(new Generated(), 0, $connection);
        $this->assertCount(2, $transport->sentRequests());

        $this->userAcceptsFeedbackInvitation($this->firstUserInternalTelegramId(), $connection);
        $this->userDeclinesFeedbackInvitation($this->secondUserInternalTelegramId(), $connection);

        $this->cronRequest($transport, $connection);

        $this->assertFeedbackInvitationsAre(new Sent(), 0, $connection);
        $this->assertFeedbackInvitationsAre(new Accepted(), 1, $connection);
        $this->assertFeedbackInvitationsAre(new Declined(), 1, $connection);
        $this->assertCount(2, $transport->sentRequests()); // stays the same
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function botId(): BotId
    {
        return new FromUuid(new FromString('5bf56c96-859d-4f34-ae18-8c33ba8226f7'));
    }

    private function meetingRoundId(): MeetingRoundId
    {
        return new RoundId('a49926cc-6956-457e-a44d-bae206426a8c');
    }

    private function firstUserInternalTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(111111111111);
    }

    private function secondUserInternalTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(2222222222);
    }

    private function thirdUserInternalTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(3333333333);
    }

    private function firstParticipantId(): ParticipantId
    {
        return new ParticipantIdFromString('111926cc-6956-457e-a44d-bae206426fff');
    }

    private function secondParticipantId(): ParticipantId
    {
        return new ParticipantIdFromString('222926cc-6956-457e-a44d-bae206426eee');
    }

    private function thirdParticipantId(): ParticipantId
    {
        return new ParticipantIdFromString('333926cc-6956-457e-a44d-bae206426ddd');
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
                ['id' => $meetingRoundId->value(), 'bot_id' => $botId->value(), 'feedback_date' => $invitationDate->value()]
            ]);
    }

    private function seedParticipant(MeetingRoundId $meetingRoundId, InternalTelegramUserId $internalTelegramUserId, ParticipantId $participantId, OpenConnection $connection)
    {
        $userId = Uuid::uuid4()->toString();
        (new TelegramUser($connection))
            ->insert([
                ['id' => $userId, 'telegram_id' => $internalTelegramUserId->value()]
            ]);
        (new MeetingRoundParticipant($connection))
            ->insert([
                ['id' => $participantId->value(), 'user_id' => $userId, 'meeting_round_id' => $meetingRoundId->value(), 'status' => (new Registered())->value()]
            ]);
    }

    private function cronRequest(HttpTransport $transport, OpenConnection $connection)
    {
        (new AsksForFeedback(
            $this->botId(),
            $transport,
            $connection,
            new DevNull()
        ))
            ->response();
    }

    private function userAcceptsFeedbackInvitation(InternalTelegramUserId $internalTelegramUserId, OpenConnection $connection)
    {
        (new AcceptsOrDeclinesFeedbackInvitation(
            (new UserMessage($internalTelegramUserId, (new Yes())->value()))->value(),
            $this->botId()->value(),
            new Indifferent(),
            $connection,
            new DevNull()
        ))
            ->response();
    }

    private function userDeclinesFeedbackInvitation(InternalTelegramUserId $internalTelegramUserId, OpenConnection $connection)
    {
        (new AcceptsOrDeclinesFeedbackInvitation(
            (new UserMessage($internalTelegramUserId, (new No())->value()))->value(),
            $this->botId()->value(),
            new Indifferent(),
            $connection,
            new DevNull()
        ))
            ->response();
    }

    private function seedPairFor(ParticipantId $firstParticipantId, ParticipantId $secondParticipantId, OpenConnection $connection)
    {
        (new MeetingRoundPair($connection))
            ->insert([
                ['participant_id' => $firstParticipantId->value(), 'match_participant_id' => $secondParticipantId->value()]
            ]);
    }

    private function assertFeedbackInvitationsAre(Status $status, int $invitationQty, OpenConnection $connection)
    {
        $this->assertCount(
            $invitationQty,
            (new Selecting(
                'select * from feedback_invitation where status = ?',
                [$status->value()],
                $connection
            ))
                ->response()->pure()->raw()
        );
    }
}