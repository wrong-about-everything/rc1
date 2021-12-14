<?php

declare(strict_types=1);

namespace RC\Tests\Unit\UserActions\SendsArbitraryMessage;

use Meringue\ISO8601DateTime;
use Meringue\ISO8601Interval\Floating\NMinutes;
use Meringue\ISO8601Interval\Floating\OneHour;
use Meringue\ISO8601Interval\Floating\OneMinute;
use Meringue\Timeline\Point\Future;
use Meringue\Timeline\Point\Now;
use Meringue\Timeline\Point\Past;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RC\Domain\MeetingRound\MeetingRoundId\Pure\FromString as MeetingRoundIdFromString;
use RC\Domain\Participant\ReadModel\ByMeetingRoundAndUser;
use RC\Domain\Participant\ReadModel\Participant;
use RC\Domain\Participant\Status\Impure\FromReadModelParticipant as StatusFromParticipant;
use RC\Domain\Participant\Status\Impure\FromPure as ImpureStatusFromPure;
use RC\Domain\Participant\Status\Pure\Registered as ParticipantRegistered;
use RC\Domain\RoundInvitation\Status\Pure\Status as InvitationStatus;
use RC\Domain\RoundRegistrationQuestion\Type\Pure\NetworkingOrSomeSpecificArea;
use RC\Domain\RoundRegistrationQuestion\Type\Pure\RoundRegistrationQuestionType;
use RC\Domain\RoundRegistrationQuestion\Type\Pure\SpecificAreaChoosing;
use RC\Domain\BotUser\UserStatus\Pure\UserStatus;
use RC\Domain\UserInterest\InterestId\Impure\Multiple\FromParticipant;
use RC\Domain\UserInterest\InterestId\Impure\Single\FromPure as ImpureInterestFromPure;
use RC\Domain\UserInterest\InterestId\Pure\Single\FromInteger as InterestIdFromInteger;
use RC\Domain\UserInterest\InterestName\Pure\FromInterestId;
use RC\Domain\UserInterest\InterestName\Pure\Networking as NetworkingName;
use RC\Domain\UserInterest\InterestId\Pure\Single\Networking;
use RC\Domain\UserInterest\InterestId\Pure\Single\SpecificArea;
use RC\Domain\BooleanAnswer\BooleanAnswerName\Sure;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Domain\RoundInvitation\Status\Pure\Sent;
use RC\Domain\TelegramUser\UserId\Pure\FromUuid as UserIdFromUuid;
use RC\Domain\TelegramUser\UserId\Pure\TelegramUserId;
use RC\Domain\BotUser\UserStatus\Pure\Registered;
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
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId;
use RC\Infrastructure\Uuid\Fixed;
use RC\Infrastructure\Uuid\FromString;
use RC\Infrastructure\Uuid\RandomUUID;
use RC\Infrastructure\Uuid\UUID as InfrastructureUUID;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Stub\Table\Bot;
use RC\Tests\Infrastructure\Stub\Table\BotUser;
use RC\Tests\Infrastructure\Stub\Table\MeetingRound;
use RC\Tests\Infrastructure\Stub\Table\MeetingRoundInvitation;
use RC\Tests\Infrastructure\Stub\Table\RoundRegistrationQuestion;
use RC\Tests\Infrastructure\Stub\Table\TelegramUser;
use RC\Tests\Infrastructure\Stub\TelegramMessage\UserMessage;
use RC\UserActions\SendsArbitraryMessage\SendsArbitraryMessage;

class UserRegistersForAMeetingRoundTest extends TestCase
{
    public function testWhenThereAreSeveralPastInvitationsAndOneActiveThenUserIsAbleToRegisterForIt()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $connection);
        $this->createTelegramUser($this->userId(), $this->telegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->userId(), new Registered(), $connection);
        $this->createMeetingRound($this->olderMeetingRoundId(), $this->botId(), new Past(new Now(), new OneMinute()), new Past(new Now(), new OneHour()), $connection);
        $this->createMeetingRoundInvitation($this->olderMeetingRoundId(), $this->userId(), new Sent(), $connection);
        $this->createMeetingRound($this->meetingRoundId(), $this->botId(), new Future(new Now(), new NMinutes(6)), new Past(new Now(), new OneMinute()), $connection);
        $this->createMeetingRoundInvitation($this->meetingRoundId(), $this->userId(), new Sent(), $connection);
        $this->createRoundRegistrationQuestion(new RandomUUID(), $this->meetingRoundId(), new NetworkingOrSomeSpecificArea(), 1, 'Вопрос про цель общения', $connection);
        $this->createRoundRegistrationQuestion(new RandomUUID(), $this->meetingRoundId(), new SpecificAreaChoosing(), 2, 'Вопрос про интересы', $connection);
        $transport = new Indifferent();

        $firstResponse = $this->userReplies($this->telegramUserId(), (new Sure())->value(), $transport, $connection);

        $this->assertTrue($firstResponse->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Вопрос про цель общения',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );

        $secondResponse = $this->userReplies($this->telegramUserId(), (new NetworkingName())->value(), $transport, $connection);

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

    public function testUserRegistersWithNetworkingInterest()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $connection);
        $this->createTelegramUser($this->userId(), $this->telegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->userId(), new Registered(), $connection);
        $this->createMeetingRound($this->meetingRoundId(), $this->botId(), new Future(new Now(), new NMinutes(6)), new Past(new Now(), new OneHour()), $connection);
        $this->createMeetingRoundInvitation($this->meetingRoundId(), $this->userId(), new Sent(), $connection);
        $this->createRoundRegistrationQuestion(new RandomUUID(), $this->meetingRoundId(), new NetworkingOrSomeSpecificArea(), 1, 'Вопрос про цель общения', $connection);
        $this->createRoundRegistrationQuestion(new RandomUUID(), $this->meetingRoundId(), new SpecificAreaChoosing(), 2, 'Вопрос про интересы', $connection);
        $transport = new Indifferent();

        $firstResponse = $this->userReplies($this->telegramUserId(), (new Sure())->value(), $transport, $connection);

        $this->assertTrue($firstResponse->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Вопрос про цель общения',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );

        $secondResponse = $this->userReplies($this->telegramUserId(), (new NetworkingName())->value(), $transport, $connection);

        $this->assertTrue($secondResponse->isSuccessful());
        $this->assertCount(2, $transport->sentRequests());
        $this->assertEquals(
            'Поздравляю, вы зарегистрировались! Сегодня пришлю вам пару для разговора. Если хотите что-то спросить или уточнить, смело пишите на @gorgonzola_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[1]->url())))->value()['text']
        );
        $this->assertParticipantWithNetworkingInterestExists($this->meetingRoundId(), $this->userId(), $connection);

        $thirdResponse = $this->userReplies($this->telegramUserId(), 'привет!', $transport, $connection);

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

    public function testUserRegistersWithSpecificInterest()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $connection);
        $this->createTelegramUser($this->userId(), $this->telegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->userId(), new Registered(), $connection);
        $this->createMeetingRound($this->meetingRoundId(), $this->botId(), new Future(new Now(), new NMinutes(6)), new Past(new Now(), new OneHour()), $connection);
        $this->createMeetingRoundInvitation($this->meetingRoundId(), $this->userId(), new Sent(), $connection);
        $this->createRoundRegistrationQuestion(new RandomUUID(), $this->meetingRoundId(), new NetworkingOrSomeSpecificArea(), 1, 'Вопрос про цель общения', $connection);
        $this->createRoundRegistrationQuestion(new RandomUUID(), $this->meetingRoundId(), new SpecificAreaChoosing(), 2, 'Вопрос про интересы', $connection);
        $transport = new Indifferent();

        $firstResponse = $this->userReplies($this->telegramUserId(), (new Sure())->value(), $transport, $connection);

        $this->assertTrue($firstResponse->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Вопрос про цель общения',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );

        $secondResponse = $this->userReplies($this->telegramUserId(), (new SpecificAreaInterestName())->value(), $transport, $connection);

        $this->assertTrue($secondResponse->isSuccessful());
        $this->assertCount(2, $transport->sentRequests());
        $this->assertEquals(
            'Вопрос про интересы',
            (new FromQuery(new FromUrl($transport->sentRequests()[1]->url())))->value()['text']
        );

        $thirdResponse = $this->userReplies($this->telegramUserId(), 'Вот такие вот у меня интересы', $transport, $connection);

        $this->assertTrue($thirdResponse->isSuccessful());
        $this->assertCount(3, $transport->sentRequests());
        $this->assertEquals(
            'Поздравляю, вы зарегистрировались! Сегодня пришлю вам пару для разговора. Если хотите что-то спросить или уточнить, смело пишите на @gorgonzola_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[2]->url())))->value()['text']
        );
        $this->assertParticipantIsRegisteredWithSpecificInterest($this->meetingRoundId(), $this->userId(), $connection);

        $fourthResponse = $this->userReplies($this->telegramUserId(), 'привет!', $transport, $connection);

        $this->assertTrue($fourthResponse->isSuccessful());
        $this->assertCount(4, $transport->sentRequests());
        $this->assertEquals(
            'Хотите что-то уточнить? Смело пишите на @gorgonzola_support_bot!',
            (new FromQuery(new FromUrl($transport->sentRequests()[3]->url())))->value()['text']
        );
        $this->assertParticipantIsRegisteredWithSpecificInterest($this->meetingRoundId(), $this->userId(), $connection);

        $fifthResponse = $this->userReplies($this->telegramUserId(), 'привет!', $transport, $connection);

        $this->assertTrue($fifthResponse->isSuccessful());
        $this->assertCount(5, $transport->sentRequests());
        $this->assertEquals(
            'Хотите что-то уточнить? Смело пишите на @gorgonzola_support_bot!',
            (new FromQuery(new FromUrl($transport->sentRequests()[4]->url())))->value()['text']
        );
        $this->assertParticipantIsRegisteredWithSpecificInterest($this->meetingRoundId(), $this->userId(), $connection);
    }

    public function testUserRegistersWithValidationErrorsAlongTheWay()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $connection);
        $this->createTelegramUser($this->userId(), $this->telegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->userId(), new Registered(), $connection);
        $this->createMeetingRound($this->meetingRoundId(), $this->botId(), new Future(new Now(), new NMinutes(6)), new Past(new Now(), new OneHour()), $connection);
        $this->createMeetingRoundInvitation($this->meetingRoundId(), $this->userId(), new Sent(), $connection);
        $this->createRoundRegistrationQuestion(new RandomUUID(), $this->meetingRoundId(), new NetworkingOrSomeSpecificArea(), 1, 'Вопрос про цель общения', $connection);
        $this->createRoundRegistrationQuestion(new RandomUUID(), $this->meetingRoundId(), new SpecificAreaChoosing(), 2, 'Вопрос про интересы', $connection);
        $transport = new Indifferent();

        $firstResponse = $this->userReplies($this->telegramUserId(), (new Sure())->value(), $transport, $connection);

        $this->assertTrue($firstResponse->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Вопрос про цель общения',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
        $this->assertButtonNamesInReply($this->interestIds(), $transport->sentRequests()[0]);

        $secondResponse = $this->userReplies($this->telegramUserId(), 'ой ну я даже прям не знаю', $transport, $connection);

        $this->assertTrue($secondResponse->isSuccessful());
        $this->assertCount(2, $transport->sentRequests());
        $this->assertEquals(
            'К сожалению, мы пока не можем принять ответ в виде текста. Поэтому выберите, пожалуйста, один из вариантов ответа. Если ни один не подходит — напишите в @gorgonzola_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[1]->url())))->value()['text']
        );
        $this->assertButtonNamesInReply($this->interestIds(), $transport->sentRequests()[0]);

        $thirdResponse = $this->userReplies($this->telegramUserId(), (new SpecificAreaInterestName())->value(), $transport, $connection);

        $this->assertTrue($thirdResponse->isSuccessful());
        $this->assertCount(3, $transport->sentRequests());
        $this->assertEquals(
            'Вопрос про интересы',
            (new FromQuery(new FromUrl($transport->sentRequests()[2]->url())))->value()['text']
        );

        $fourthResponse = $this->userReplies($this->telegramUserId(), 'Вот такие вот у меня интересы', $transport, $connection);

        $this->assertTrue($fourthResponse->isSuccessful());
        $this->assertCount(4, $transport->sentRequests());
        $this->assertEquals(
            'Поздравляю, вы зарегистрировались! Сегодня пришлю вам пару для разговора. Если хотите что-то спросить или уточнить, смело пишите на @gorgonzola_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[3]->url())))->value()['text']
        );
        $this->assertParticipantIsRegisteredWithSpecificInterest($this->meetingRoundId(), $this->userId(), $connection);

        $fifthResponse = $this->userReplies($this->telegramUserId(), 'привет!', $transport, $connection);

        $this->assertTrue($fifthResponse->isSuccessful());
        $this->assertCount(5, $transport->sentRequests());
        $this->assertEquals(
            'Хотите что-то уточнить? Смело пишите на @gorgonzola_support_bot!',
            (new FromQuery(new FromUrl($transport->sentRequests()[4]->url())))->value()['text']
        );
        $this->assertParticipantIsRegisteredWithSpecificInterest($this->meetingRoundId(), $this->userId(), $connection);

        $sixthResponse = $this->userReplies($this->telegramUserId(), 'привет!', $transport, $connection);

        $this->assertTrue($sixthResponse->isSuccessful());
        $this->assertCount(6, $transport->sentRequests());
        $this->assertEquals(
            'Хотите что-то уточнить? Смело пишите на @gorgonzola_support_bot!',
            (new FromQuery(new FromUrl($transport->sentRequests()[5]->url())))->value()['text']
        );
        $this->assertParticipantIsRegisteredWithSpecificInterest($this->meetingRoundId(), $this->userId(), $connection);
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function telegramUserId(): InternalTelegramUserId
    {
        return new FromInteger(654987);
    }

    private function botId(): BotId
    {
        return new FromUuid(new Fixed());
    }

    private function meetingRoundId(): string
    {
        return 'e00729d6-330c-4123-b856-d5196812d111';
    }

    private function olderMeetingRoundId(): string
    {
        return 'df03b9d4-08fd-4f91-a063-14e11166c340';
    }

    private function interestIds()
    {
        return [(new Networking())->value(), (new SpecificArea())->value()];
    }

    private function interestNamesFromIds(array $ids)
    {
        return
            array_map(
                function (int $id) {
                    return (new FromInterestId(new InterestIdFromInteger($id)))->value();
                },
                $ids
            );
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

    private function createMeetingRound(string $meetingRoundId, BotId $botId, ISO8601DateTime $startDateTime, ISO8601DateTime $invitationDateTime, $connection)
    {
        (new MeetingRound($connection))
            ->insert([
                [
                    'id' => $meetingRoundId,
                    'bot_id' => $botId->value(),
                    'start_date' => $startDateTime->value(),
                    'invitation_date' => $invitationDateTime->value(),
                    'available_interests' => $this->interestIds(),
                ]
            ]);
    }

    private function createMeetingRoundInvitation(string $meetingRoundId, TelegramUserId $userId, InvitationStatus $status, OpenConnection $connection)
    {
        (new MeetingRoundInvitation($connection))
            ->insert([
                ['meeting_round_id' => $meetingRoundId, 'user_id' => $userId->value(), 'status' => $status->value()]
            ]);
    }

    private function createRoundRegistrationQuestion(InfrastructureUUID $id, string $meetingRoundId, RoundRegistrationQuestionType $questionType, int $ordinalNumber, string $text, OpenConnection $connection)
    {
        (new RoundRegistrationQuestion($connection))
            ->insert([
                ['id' => $id->value(), 'meeting_round_id' => $meetingRoundId, 'type' => $questionType->value(), 'ordinal_number' => $ordinalNumber, 'text' => $text]
            ]);
    }

    private function assertParticipantWithNetworkingInterestExists(string $meetingRoundId, TelegramUserId $userId, OpenConnection $connection)
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

    private function assertParticipantIsRegisteredWithSpecificInterest(string $meetingRoundId, TelegramUserId $userId, OpenConnection $connection)
    {
        $participant = $this->participant($meetingRoundId, $userId, $connection);
        $this->assertTrue($participant->value()->pure()->isPresent());
        $this->assertEquals(
            'Вот такие вот у меня интересы',
            $participant->value()->pure()->raw()['interested_in_as_plain_text']
        );
        $this->assertTrue(
            (new StatusFromParticipant($participant))
                ->equals(
                    new ImpureStatusFromPure(new ParticipantRegistered())
                )
        );
    }

    private function participant(string $meetingRoundId, TelegramUserId $userId, OpenConnection $connection): Participant
    {
        return
            new ByMeetingRoundAndUser(
                new MeetingRoundIdFromString($meetingRoundId),
                $userId,
                $connection
            );
    }

    private function assertButtonNamesInReply(array $interestIds, Request $request)
    {
        $this->assertEquals(
            $this->interestNamesFromIds($interestIds),
            array_map(
                function (array $option) {
                    return $option[0]['text'];
                },
                json_decode((new FromQuery(new FromUrl($request->url())))->value()['reply_markup'], true)['keyboard']
            )
        );
    }
}
