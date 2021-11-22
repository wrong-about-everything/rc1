<?php

declare(strict_types=1);

namespace RC\Tests\Unit\UserActions\SendsArbitraryMessage;

use Meringue\ISO8601DateTime;
use Meringue\ISO8601Interval\Floating\OneHour;
use Meringue\Timeline\Point\Future;
use Meringue\Timeline\Point\Now;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RC\Domain\FeedbackInvitation\FeedbackInvitationId\Pure\FeedbackInvitationId;
use RC\Domain\FeedbackInvitation\FeedbackInvitationId\Pure\FromString as FeedbackInvitationIdFromString;
use RC\Domain\FeedbackInvitation\Status\Pure\Sent;
use RC\Domain\FeedbackInvitation\Status\Pure\Status;
use RC\Domain\FeedbackQuestion\FeedbackQuestionId\Pure\FeedbackQuestionId;
use RC\Domain\FeedbackQuestion\FeedbackQuestionId\Pure\FromString as FeedbackQuestionIdFromString;
use RC\Domain\MeetingRound\MeetingRoundId\Pure\FromString as MeetingRoundIdFromString;
use RC\Domain\MeetingRound\MeetingRoundId\Pure\MeetingRoundId;
use RC\Domain\Participant\ParticipantId\Pure\FromString as ParticipantIdFromString;
use RC\Domain\Participant\ParticipantId\Pure\ParticipantId;
use RC\Domain\BotUser\UserStatus\Pure\UserStatus;
use RC\Domain\UserInterest\InterestId\Pure\Single\Networking;
use RC\Domain\UserInterest\InterestId\Pure\Single\SpecificArea;
use RC\Domain\BooleanAnswer\BooleanAnswerName\Sure;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Domain\TelegramUser\UserId\Pure\FromUuid as UserIdFromUuid;
use RC\Domain\TelegramUser\UserId\Pure\TelegramUserId;
use RC\Domain\BotUser\UserStatus\Pure\Registered;
use RC\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery;
use RC\Infrastructure\Http\Request\Url\Query\FromUrl;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\Http\Transport\Indifferent;
use RC\Infrastructure\Logging\Logs\DevNull;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotId\FromUuid;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromInteger;
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId;
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

class UserAnswersFeedbackQuestionsTest extends TestCase
{
    public function testGivenUserAcceptsInvitationWhenHeAnswersTheFirstAndTheOnlyQuestionThenHisAnswerIsSavedAndHeSeesThankYouMessage()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->firstBotId(), $connection);
        $this->createTelegramUser($this->userId(), $this->telegramUserId(), $connection);
        $this->createBotUser($this->firstBotId(), $this->userId(), new Registered(), $connection);
        $this->createMeetingRound($this->firstMeetingRoundId(), $this->firstBotId(), new Future(new Now(), new OneHour()), new Now(), $connection);
        $this->createParticipant($this->firstMeetingRoundId(), $this->firstParticipantId(), $this->userId(), $connection);
        $this->createFeedbackInvitation($this->firstFeedbackInvitationId(), $this->firstParticipantId(), new Sent(), $connection);
        $this->createFeedbackQuestion($this->firstMeetingRoundFirstFeedbackQuestionId(), $this->firstMeetingRoundId(), 'как дела?', 1, $connection);
        $transport = new Indifferent();

        $firstResponse = $this->userReplies($this->telegramUserId(), (new Sure())->value(), $transport, $connection);

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
            'Спасибо за ответы! Если хотите что-то спросить или уточнить, смело пишите на @gorgonzola_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[1]->url())))->value()['text']
        );
        $this->assertParticipantAnswerIs($this->firstMeetingRoundFirstFeedbackQuestionId(), $this->firstParticipantId(), 'Шикардос!', $connection);

        $thirdResponse = $this->userReplies($this->telegramUserId(), 'Кашердос!', $transport, $connection);

        $this->assertTrue($thirdResponse->isSuccessful());
        $this->assertCount(3, $transport->sentRequests());
        $this->assertEquals(
            'Хотите что-то уточнить? Смело пишите на @gorgonzola_support_bot!',
            (new FromQuery(new FromUrl($transport->sentRequests()[2]->url())))->value()['text']
        );
        $this->assertParticipantAnswerIs($this->firstMeetingRoundFirstFeedbackQuestionId(), $this->firstParticipantId(), 'Шикардос!', $connection);

        $fourthResponse = $this->userReplies($this->telegramUserId(), 'привет!', $transport, $connection);

        $this->assertTrue($fourthResponse->isSuccessful());
        $this->assertCount(4, $transport->sentRequests());
        $this->assertEquals(
            'Хотите что-то уточнить? Смело пишите на @gorgonzola_support_bot!',
            (new FromQuery(new FromUrl($transport->sentRequests()[3]->url())))->value()['text']
        );
        $this->assertParticipantAnswerIs($this->firstMeetingRoundFirstFeedbackQuestionId(), $this->firstParticipantId(), 'Шикардос!', $connection);
    }

    public function testGivenUserAcceptsInvitationWhenHeAnswersTheFirstQuestionThenHisAnswerIsSavedAndHeSeesTheSecondOne()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->firstBotId(), $connection);
        $this->createTelegramUser($this->userId(), $this->telegramUserId(), $connection);
        $this->createBotUser($this->firstBotId(), $this->userId(), new Registered(), $connection);
        $this->createMeetingRound($this->firstMeetingRoundId(), $this->firstBotId(), new Future(new Now(), new OneHour()), new Now(), $connection);
        $this->createParticipant($this->firstMeetingRoundId(), $this->firstParticipantId(), $this->userId(), $connection);
        $this->createFeedbackInvitation($this->firstFeedbackInvitationId(), $this->firstParticipantId(), new Sent(), $connection);
        $this->createFeedbackQuestion($this->firstMeetingRoundFirstFeedbackQuestionId(), $this->firstMeetingRoundId(), 'привет, как дела?', 1, $connection);
        $this->createFeedbackQuestion($this->firstMeetingRoundSecondFeedbackQuestionId(), $this->firstMeetingRoundId(), 'как здоровье, азаза?', 2, $connection);

        $this->createBot($this->secondBotId(), $connection);
        $this->createBotUser($this->secondBotId(), $this->userId(), new Registered(), $connection);
        $this->createMeetingRound($this->secondMeetingRoundId(), $this->secondBotId(), new Future(new Now(), new OneHour()), new Now(), $connection);
        $this->createParticipant($this->secondMeetingRoundId(), $this->secondParticipantId(), $this->userId(), $connection);
        $this->createFeedbackInvitation($this->secondFeedbackInvitationId(), $this->secondParticipantId(), new Sent(), $connection);
        $this->createFeedbackQuestion($this->secondMeetingRoundFirstFeedbackQuestionId(), $this->secondMeetingRoundId(), 'hey, how are you?', 1, $connection);
        $this->createFeedbackQuestion($this->secondMeetingRoundSecondFeedbackQuestionId(), $this->secondMeetingRoundId(), 'whats up bro', 2, $connection);

        $transport = new Indifferent();

        $this->userReplies($this->telegramUserId(), (new Sure())->value(), $transport, $connection);

        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'привет, как дела?',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );

        $this->userReplies($this->telegramUserId(), 'кометы не падают', $transport, $connection);

        $this->assertParticipantAnswerIs($this->firstMeetingRoundFirstFeedbackQuestionId(), $this->firstParticipantId(), 'кометы не падают', $connection);
        $this->assertCount(2, $transport->sentRequests());
        $this->assertEquals(
            'как здоровье, азаза?',
            (new FromQuery(new FromUrl($transport->sentRequests()[1]->url())))->value()['text']
        );

        $this->userReplies($this->telegramUserId(), 'и всё нормально', $transport, $connection);

        $this->assertParticipantAnswerIs($this->firstMeetingRoundSecondFeedbackQuestionId(), $this->firstParticipantId(), 'и всё нормально', $connection);
        $this->assertParticipantAnswerIs($this->firstMeetingRoundFirstFeedbackQuestionId(), $this->firstParticipantId(), 'кометы не падают', $connection);
        $this->assertCount(3, $transport->sentRequests());
        $this->assertEquals(
            'Спасибо за ответы! Если хотите что-то спросить или уточнить, смело пишите на @gorgonzola_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[2]->url())))->value()['text']
        );

        $this->userReplies($this->telegramUserId(), 'бубубу', $transport, $connection);

        $this->assertCount(4, $transport->sentRequests());
        $this->assertEquals(
            'Хотите что-то уточнить? Смело пишите на @gorgonzola_support_bot!',
            (new FromQuery(new FromUrl($transport->sentRequests()[3]->url())))->value()['text']
        );
        $this->assertParticipantAnswerIs($this->firstMeetingRoundSecondFeedbackQuestionId(), $this->firstParticipantId(), 'и всё нормально', $connection);
        $this->assertParticipantAnswerIs($this->firstMeetingRoundFirstFeedbackQuestionId(), $this->firstParticipantId(), 'кометы не падают', $connection);

        $this->userReplies($this->telegramUserId(), 'привет!', $transport, $connection);

        $this->assertCount(5, $transport->sentRequests());
        $this->assertEquals(
            'Хотите что-то уточнить? Смело пишите на @gorgonzola_support_bot!',
            (new FromQuery(new FromUrl($transport->sentRequests()[4]->url())))->value()['text']
        );
        $this->assertParticipantAnswerIs($this->firstMeetingRoundSecondFeedbackQuestionId(), $this->firstParticipantId(), 'и всё нормально', $connection);
        $this->assertParticipantAnswerIs($this->firstMeetingRoundFirstFeedbackQuestionId(), $this->firstParticipantId(), 'кометы не падают', $connection);
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function telegramUserId(): InternalTelegramUserId
    {
        return new FromInteger(654987);
    }

    private function firstBotId(): BotId
    {
        return new FromUuid(new Fixed());
    }

    private function secondBotId(): BotId
    {
        return new FromUuid(new FromString('f0f5948b-90e4-4cb4-911a-8b5b0d97113c'));
    }

    private function firstMeetingRoundId(): MeetingRoundId
    {
        return new MeetingRoundIdFromString('e00729d6-330c-4123-b856-d5196812d111');
    }

    private function secondMeetingRoundId(): MeetingRoundId
    {
        return new MeetingRoundIdFromString('2969f443-0a45-4874-a529-d5ca45a2f093');
    }

    private function firstMeetingRoundFirstFeedbackQuestionId(): FeedbackQuestionId
    {
        return new FeedbackQuestionIdFromString('333729d6-330c-4123-b856-d5196812dddd');
    }

    private function firstMeetingRoundSecondFeedbackQuestionId(): FeedbackQuestionId
    {
        return new FeedbackQuestionIdFromString('444729d6-330c-4123-b856-d5196812dccc');
    }

    private function secondMeetingRoundFirstFeedbackQuestionId(): FeedbackQuestionId
    {
        return new FeedbackQuestionIdFromString('555729d6-330c-4123-b856-d5196812d111');
    }

    private function secondMeetingRoundSecondFeedbackQuestionId(): FeedbackQuestionId
    {
        return new FeedbackQuestionIdFromString('666729d6-330c-4123-b856-d5196812d222');
    }

    private function firstFeedbackInvitationId(): FeedbackInvitationId
    {
        return new FeedbackInvitationIdFromString('111729d6-330c-4123-b856-d5196812dfff');
    }

    private function secondFeedbackInvitationId(): FeedbackInvitationId
    {
        return new FeedbackInvitationIdFromString('480f8801-4b03-4f34-a142-19a4d145b75b');
    }

    private function firstParticipantId(): ParticipantId
    {
        return new ParticipantIdFromString('222729d6-330c-4123-b856-d5196812deee');
    }

    private function secondParticipantId(): ParticipantId
    {
        return new ParticipantIdFromString('333729d6-330c-4123-b856-d5196812dfff');
    }

    private function interestIds()
    {
        return [(new Networking())->value(), (new SpecificArea())->value()];
    }

    private function userId(): TelegramUserId
    {
        return new UserIdFromUuid(new FromString('103729d6-330c-4123-b856-d5196812d509'));
    }

    private function userReplies(InternalTelegramUserId $telegramUserId, string $answer, HttpTransport $transport, OpenConnection $connection)
    {
        return
            (new SendsArbitraryMessage(
                new Now(),
                (new UserMessage($telegramUserId, $answer))->value(),
                $this->firstBotId()->value(),
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

    private function createTelegramUser(TelegramUserId $userId, InternalTelegramUserId $telegramUserId, $connection)
    {
        (new TelegramUser($connection))
            ->insert([
                ['id' => $userId->value(), 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $telegramUserId->value(), 'telegram_handle' => 'dremuchee_bydlo'],
            ]);
    }

    private function createBotUser(BotId $botId, TelegramUserId $userId, UserStatus $status, $connection)
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

    private function createParticipant(MeetingRoundId $meetingRoundId, ParticipantId $participantId, TelegramUserId $userId, OpenConnection $connection)
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

    private function createFeedbackQuestion(FeedbackQuestionId $feedbackQuestionId, MeetingRoundId $meetingRoundId, string $text, int $ordinalNumber, OpenConnection $connection)
    {
        (new FeedbackQuestion($connection))
            ->insert([
                ['id' => $feedbackQuestionId->value(), 'meeting_round_id' => $meetingRoundId->value(), 'text' => $text, 'ordinal_number' => $ordinalNumber]
            ]);
    }

    private function assertParticipantAnswerIs(FeedbackQuestionId $feedbackQuestionId, ParticipantId $participantId, string $userAnswer, OpenConnection $connection)
    {
        $answer = $this->participantAnswer($feedbackQuestionId, $participantId, $connection);
        $this->assertEquals($userAnswer, $answer['text']);
    }

    private function participantAnswer(FeedbackQuestionId $feedbackQuestionId, ParticipantId $participantId, OpenConnection $connection): array
    {
        return
            (new Selecting(
                'select * from feedback_answer where feedback_question_id = ? and participant_id = ?',
                [$feedbackQuestionId->value(), $participantId->value()],
                $connection
            ))
                ->response()->pure()->raw()[0];
    }
}
