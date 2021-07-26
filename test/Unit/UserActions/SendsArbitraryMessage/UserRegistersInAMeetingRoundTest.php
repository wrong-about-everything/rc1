<?php

declare(strict_types=1);

namespace RC\Tests\Unit\UserActions\SendsArbitraryMessage;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RC\Domain\MeetingRound\MeetingRoundId\FromString as MeetingRoundIdFromString;
use RC\Domain\Participant\ReadModel\ByMeetingRoundAndUser;
use RC\Domain\Participant\Status\Impure\FromParticipant as StatusFromParticipant;
use RC\Domain\Participant\Status\Impure\FromPure as ImpureStatusFromPure;
use RC\Domain\Participant\Status\Pure\Registered as ParticipantRegistered;
use RC\Domain\UserInterest\InterestId\Impure\Multiple\FromParticipant;
use RC\Domain\UserInterest\InterestId\Impure\Single\FromPure as ImpureInterestFromPure;
use RC\Domain\UserInterest\InterestName\Pure\Networking as NetworkingName;
use RC\Domain\UserInterest\InterestId\Pure\Single\Networking;
use RC\Domain\UserInterest\InterestName\Pure\SpecificArea;
use RC\Domain\BooleanAnswer\BooleanAnswerName\Yes;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Domain\RoundInvitation\ReadModel\LatestByTelegramUserIdAndBotId;
use RC\Domain\RoundInvitation\Status\Impure\FromInvitation;
use RC\Domain\RoundInvitation\Status\Impure\FromPure;
use RC\Domain\RoundInvitation\Status\Pure\Sent;
use RC\Domain\RoundInvitation\Status\Pure\UserRegistered;
use RC\Domain\UserInterest\InterestId\Pure\Single\SpecificArea as SpecificAreaId;
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
use RC\Tests\Infrastructure\Stub\TelegramMessage\UserMessage;
use RC\UserActions\SendsArbitraryMessage\SendsArbitraryMessage;

class UserRegistersInAMeetingRoundTest extends TestCase
{
    public function testUserRegistersWithNetworkingAim()
    {
        $connection = new ApplicationConnection();
        (new Bot($connection))
            ->insert([
                ['id' => $this->botId()->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'vasya_bot']
            ]);
        (new BotUser($this->botId(), $connection))
            ->insert(
                ['id' => $this->userId()->value(), 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $this->telegramUserId()->value(), 'telegram_handle' => 'dremuchee_bydlo'],
                ['status' => (new Registered())->value()]
            );
        (new MeetingRound($connection))
            ->insert([
                ['id' => $this->meetingRoundId(), 'bot_id' => $this->botId()->value()]
            ]);
        (new MeetingRoundInvitation($connection))
            ->insert([
                ['id' => $this->meetingRoundInvitationId(), 'meeting_round_id' => $this->meetingRoundId(), 'user_id' => $this->userId()->value(), 'status' => (new Sent())->value()]
            ]);
        (new RoundRegistrationQuestion($connection))
            ->insert([
                ['id' => Uuid::uuid4()->toString(), 'meeting_round_id' => $this->meetingRoundId(), 'user_interest' => (new Networking())->value(), 'text' => 'Вопрос про цель общения'],
                ['id' => Uuid::uuid4()->toString(), 'meeting_round_id' => $this->meetingRoundId(), 'user_interest' => (new SpecificAreaId())->value(), 'text' => 'Вопрос про интересы'],
            ]);
        $transport = new Indifferent();

        $firstResponse =
            (new SendsArbitraryMessage(
                (new UserMessage($this->telegramUserId(), (new Yes())->value()))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($firstResponse->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Вопрос про цель общения',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );

        $secondResponse =
            (new SendsArbitraryMessage(
                (new UserMessage($this->telegramUserId(), (new NetworkingName())->value()))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($secondResponse->isSuccessful());
        $this->assertCount(2, $transport->sentRequests());
        $this->assertEquals(
            'Поздравляю, вы зарегистрировались! В понедельник в 11 утра пришлю вам пару для разговора. Если хотите что-то спросить или уточнить, смело пишите на @gorgonzola_support',
            (new FromQuery(new FromUrl($transport->sentRequests()[1]->url())))->value()['text']
        );
        $this->assertParticipantWithNetworkingInterestExists($this->meetingRoundId(), $this->userId(), $connection);
    }

    public function testUserRegistersWithSpecificAreaAim()
    {
        $connection = new ApplicationConnection();
        (new Bot($connection))
            ->insert([
                ['id' => $this->botId()->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'vasya_bot']
            ]);
        (new BotUser($this->botId(), $connection))
            ->insert(
                ['id' => $this->userId()->value(), 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $this->telegramUserId()->value(), 'telegram_handle' => 'dremuchee_bydlo'],
                ['status' => (new Registered())->value()]
            );
        (new MeetingRound($connection))
            ->insert([
                ['id' => $this->meetingRoundId(), 'bot_id' => $this->botId()->value()]
            ]);
        (new MeetingRoundInvitation($connection))
            ->insert([
                ['id' => $this->meetingRoundInvitationId(), 'meeting_round_id' => $this->meetingRoundId(), 'user_id' => $this->userId()->value(), 'status' => (new Sent())->value()]
            ]);
        (new RoundRegistrationQuestion($connection))
            ->insert([
                ['id' => Uuid::uuid4()->toString(), 'meeting_round_id' => $this->meetingRoundId(), 'user_interest' => (new Networking())->value(), 'text' => 'Вопрос про цель общения'],
                ['id' => Uuid::uuid4()->toString(), 'meeting_round_id' => $this->meetingRoundId(), 'user_interest' => (new SpecificAreaId())->value(), 'text' => 'Вопрос про интересы'],
            ]);
        $transport = new Indifferent();

        $firstResponse =
            (new SendsArbitraryMessage(
                (new UserMessage($this->telegramUserId(), (new Yes())->value()))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($firstResponse->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Вопрос про цель общения',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );

        $secondResponse =
            (new SendsArbitraryMessage(
                (new UserMessage($this->telegramUserId(), (new SpecificArea())->value()))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($secondResponse->isSuccessful());
        $this->assertCount(2, $transport->sentRequests());
        $this->assertEquals(
            'Вопрос про интересы',
            (new FromQuery(new FromUrl($transport->sentRequests()[1]->url())))->value()['text']
        );

        $thirdResponse =
            (new SendsArbitraryMessage(
                (new UserMessage($this->telegramUserId(), 'Вот такие вот у меня интересы'))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($thirdResponse->isSuccessful());
        $this->assertCount(3, $transport->sentRequests());
        $this->assertEquals(
            'Поздравляю, вы зарегистрировались! В понедельник в 11 утра пришлю вам пару для разговора. Если хотите что-то спросить или уточнить, смело пишите на @gorgonzola_support',
            (new FromQuery(new FromUrl($transport->sentRequests()[2]->url())))->value()['text']
        );
        $this->assertTrue(
            (new FromInvitation(
                new LatestByTelegramUserIdAndBotId($this->telegramUserId(), $this->botId(), $connection)
            ))
                ->equals(
                    new FromPure(new UserRegistered())
                )
        );
        $this->assertParticipantWithSpecificInterestExists($this->meetingRoundId(), $this->userId(), $connection);
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

    private function meetingRoundId(): string
    {
        return 'e00729d6-330c-4123-b856-d5196812d111';
    }

    private function meetingRoundInvitationId(): string
    {
        return '333729d6-330c-4123-b856-d5196812d444';
    }

    private function userId(): UserId
    {
        return new UserIdFromUuid(new FromString('103729d6-330c-4123-b856-d5196812d509'));
    }

    private function assertParticipantWithNetworkingInterestExists(string $meetingRoundId, UserId $userId, OpenConnection $connection)
    {
        $participant =
            new ByMeetingRoundAndUser(
                new MeetingRoundIdFromString($meetingRoundId),
                $userId,
                $connection
            );
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

    private function assertParticipantWithSpecificInterestExists(string $meetingRoundId, UserId $userId, OpenConnection $connection)
    {
        $participant =
            new ByMeetingRoundAndUser(
                new MeetingRoundIdFromString($meetingRoundId),
                $userId,
                $connection
            );
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
}