<?php

declare(strict_types=1);

namespace RC\Tests\Unit\UserActions\SendsArbitraryMessage;

use Meringue\ISO8601DateTime;
use Meringue\ISO8601Interval\Floating\OneHour;
use Meringue\ISO8601Interval\Floating\OneMinute;
use Meringue\Timeline\Point\Future;
use Meringue\Timeline\Point\Now;
use Meringue\Timeline\Point\Past;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RC\Domain\BooleanAnswer\BooleanAnswerName\BooleanAnswerName;
use RC\Domain\FeedbackInvitation\FeedbackInvitationId\Pure\FeedbackInvitationId;
use RC\Domain\FeedbackInvitation\FeedbackInvitationId\Pure\FromString as FeedbackInvitationIdFromString;
use RC\Domain\FeedbackInvitation\Status\Pure\Status;
use RC\Domain\MeetingRound\MeetingRoundId\Pure\FromString as MeetingRoundIdFromString;
use RC\Domain\MeetingRound\MeetingRoundId\Pure\MeetingRoundId;
use RC\Domain\Participant\ParticipantId\Pure\FromString as ParticipantIdFromString;
use RC\Domain\Participant\ParticipantId\Pure\ParticipantId;
use RC\Domain\Participant\ReadModel\ByMeetingRoundAndUser;
use RC\Domain\Participant\ReadModel\Participant;
use RC\Domain\Participant\Status\Impure\FromReadModelParticipant as StatusFromParticipant;
use RC\Domain\Participant\Status\Impure\FromPure as ImpureStatusFromPure;
use RC\Domain\Participant\Status\Pure\Registered as ParticipantRegistered;
use RC\Domain\RoundInvitation\Status\Pure\Status as InvitationStatus;
use RC\Domain\RoundRegistrationQuestion\Type\Pure\NetworkingOrSomeSpecificArea;
use RC\Domain\RoundRegistrationQuestion\Type\Pure\RoundRegistrationQuestionType;
use RC\Domain\RoundRegistrationQuestion\Type\Pure\SpecificAreaChoosing;
use RC\Domain\User\UserStatus\Pure\UserStatus;
use RC\Domain\UserInterest\InterestId\Impure\Multiple\FromParticipant;
use RC\Domain\UserInterest\InterestId\Impure\Single\FromPure as ImpureInterestFromPure;
use RC\Domain\UserInterest\InterestId\Pure\Single\FromInteger as InterestIdFromInteger;
use RC\Domain\UserInterest\InterestName\Pure\FromInterestId;
use RC\Domain\UserInterest\InterestName\Pure\Networking as NetworkingName;
use RC\Domain\UserInterest\InterestId\Pure\Single\Networking;
use RC\Domain\UserInterest\InterestId\Pure\Single\SpecificArea;
use RC\Domain\BooleanAnswer\BooleanAnswerName\Yes;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Domain\RoundInvitation\Status\Pure\Sent;
use RC\Domain\User\UserId\FromUuid as UserIdFromUuid;
use RC\Domain\User\UserId\UserId;
use RC\Domain\User\UserStatus\Pure\Registered;
use RC\Domain\UserInterest\InterestName\Pure\SpecificArea as SpecificAreaInterestName;
use RC\Infrastructure\Http\Request\Outbound\Request;
use RC\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery;
use RC\Infrastructure\Http\Request\Url\Query\FromUrl;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\Http\Transport\Indifferent;
use RC\Infrastructure\Logging\Logs\DevNull;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotId\FromUuid;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromInteger;
use RC\Infrastructure\TelegramBot\UserId\Pure\TelegramUserId;
use RC\Infrastructure\Uuid\Fixed;
use RC\Infrastructure\Uuid\FromString;
use RC\Infrastructure\Uuid\RandomUUID;
use RC\Infrastructure\Uuid\UUID as InfrastructureUUID;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Stub\Table\Bot;
use RC\Tests\Infrastructure\Stub\Table\BotUser;
use RC\Tests\Infrastructure\Stub\Table\FeedbackInvitation;
use RC\Tests\Infrastructure\Stub\Table\FeedbackQuestion;
use RC\Tests\Infrastructure\Stub\Table\MeetingRound;
use RC\Tests\Infrastructure\Stub\Table\MeetingRoundInvitation;
use RC\Tests\Infrastructure\Stub\Table\MeetingRoundParticipant;
use RC\Tests\Infrastructure\Stub\Table\RoundRegistrationQuestion;
use RC\Tests\Infrastructure\Stub\Table\TelegramUser;
use RC\Tests\Infrastructure\Stub\TelegramMessage\UserMessage;
use RC\UserActions\SendsArbitraryMessage\SendsArbitraryMessage;

class UserAnswersFeedbackQuestionsTest extends TestCase
{
    public function testGivenUserAcceptsInvitationWhenHeAnswersTheFirstQuestionThenHirProgressIsSavedAndHeSeesTheSecondQuestion()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $connection);
        $this->createTelegramUser($this->userId(), $this->telegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->userId(), new Registered(), $connection);
        $this->createMeetingRound($this->meetingRoundId(), $this->botId(), new Future(new Now(), new OneHour()), new Now(), $connection);
        $this->createParticipant($this->meetingRoundId(), $this->participantId(), $this->userId(), $connection);
        $this->createFeedbackInvitation($this->feedbackInvitationId(), $this->participantId(), new \RC\Domain\FeedbackInvitation\Status\Pure\Sent(), $connection);
        $this->createFeedbackQuestion($this->meetingRoundId(), $connection);
        $transport = new Indifferent();

        $firstResponse = $this->userReplies($this->telegramUserId(), (new Yes())->value(), $transport, $connection);

        $this->assertTrue($firstResponse->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'как дела?',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );

        $secondResponse = $this->userReplies($this->telegramUserId(), 'Шикардос!', $transport, $connection);

        $this->assertTrue($secondResponse->isSuccessful());
        $this->assertCount(2, $transport->sentRequests());
        $this->assertEquals(
            'Поздравляю, вы зарегистрировались! Сегодня пришлю вам пару для разговора. Если хотите что-то спросить или уточнить, смело пишите на @gorgonzola_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[1]->url())))->value()['text']
        );
        $this->assertParticipantWithNetworkingInterestExists($this->meetingRoundId(), $this->userId(), $connection);

        $thirdResponse = $this->userReplies($this->telegramUserId(), 'привет', $transport, $connection);

        $this->assertTrue($thirdResponse->isSuccessful());
        $this->assertCount(3, $transport->sentRequests());
        $this->assertEquals(
            'Хотите что-то уточнить? Смело пишите на @gorgonzola_support_bot!',
            (new FromQuery(new FromUrl($transport->sentRequests()[2]->url())))->value()['text']
        );
        $this->assertParticipantWithNetworkingInterestExists($this->meetingRoundId(), $this->userId(), $connection);

        $fourthResponse = $this->userReplies($this->telegramUserId(), 'привет!', $transport, $connection);

        $this->assertTrue($fourthResponse->isSuccessful());
        $this->assertCount(4, $transport->sentRequests());
        $this->assertEquals(
            'Хотите что-то уточнить? Смело пишите на @gorgonzola_support_bot!',
            (new FromQuery(new FromUrl($transport->sentRequests()[3]->url())))->value()['text']
        );
        $this->assertParticipantWithNetworkingInterestExists($this->meetingRoundId(), $this->userId(), $connection);
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

    private function meetingRoundId(): MeetingRoundId
    {
        return new MeetingRoundIdFromString('e00729d6-330c-4123-b856-d5196812d111');
    }

    private function feedbackInvitationId(): FeedbackInvitationId
    {
        return new FeedbackInvitationIdFromString('111729d6-330c-4123-b856-d5196812dfff');
    }

    private function participantId(): ParticipantId
    {
        return new ParticipantIdFromString('222729d6-330c-4123-b856-d5196812deee');
    }

    private function interestIds()
    {
        return [(new Networking())->value(), (new SpecificArea())->value()];
    }

    private function userId(): UserId
    {
        return new UserIdFromUuid(new FromString('103729d6-330c-4123-b856-d5196812d509'));
    }

    private function userReplies(TelegramUserId $telegramUserId, string $answer, HttpTransport $transport, OpenConnection $connection)
    {
        return
            (new SendsArbitraryMessage(
                new Now(),
                (new UserMessage($telegramUserId, $answer))->value(),
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
                [
                    'id' => $meetingRoundId->value(),
                    'bot_id' => $botId->value(),
                    'start_date' => $startDateTime->value(),
                    'invitation_date' => $invitationDateTime->value(),
                    'available_interests' => $this->interestIds(),
                ]
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

    private function assertParticipantWithNetworkingInterestExists(string $meetingRoundId, UserId $userId, OpenConnection $connection)
    {
        $participant = $this->participant($meetingRoundId, $userId, $connection);
        $this->assertTrue($participant->value()->pure()->isPresent());
        $this->assertTrue(
            (new FromParticipant($participant))
                ->contain(
                    new ImpureInterestFromPure(new Networking())
                )
        );
        $this->assertTrue(
            (new StatusFromParticipant($participant))
                ->equals(
                    new ImpureStatusFromPure(new ParticipantRegistered())
                )
        );
    }

    private function participant(string $meetingRoundId, UserId $userId, OpenConnection $connection): Participant
    {
        return
            new ByMeetingRoundAndUser(
                new MeetingRoundIdFromString($meetingRoundId),
                $userId,
                $connection
            );
    }
}
