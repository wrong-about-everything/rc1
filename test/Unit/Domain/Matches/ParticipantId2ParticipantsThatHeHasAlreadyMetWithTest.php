<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Domain\Matches;

use Meringue\ISO8601DateTime;
use Meringue\ISO8601Interval\Floating\NDays;
use Meringue\Timeline\Point\Future;
use Meringue\Timeline\Point\Now;
use Meringue\Timeline\Point\Past;
use PHPUnit\Framework\TestCase;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotId\FromUuid;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Domain\Matches\ParticipantId2ParticipantsThatHeHasAlreadyMetWith;
use RC\Domain\MeetingRound\MeetingRoundId\Impure\FromPure;
use RC\Domain\MeetingRound\MeetingRoundId\Pure\FromString as RoundId;
use RC\Domain\MeetingRound\MeetingRoundId\Pure\MeetingRoundId;
use RC\Domain\MeetingRound\ReadModel\ById;
use RC\Domain\Participant\ParticipantId\Pure\FromString as ParticipantIdFromString;
use RC\Domain\Participant\ParticipantId\Pure\ParticipantId;
use RC\Domain\Participant\Status\Pure\Registered;
use RC\Domain\TelegramUser\UserId\Pure\FromUuid as TelegramUserFromUuid;
use RC\Domain\TelegramUser\UserId\Pure\TelegramUserId;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromInteger;
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId;
use RC\Infrastructure\Uuid\FromString;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Stub\Table\Bot;
use RC\Tests\Infrastructure\Stub\Table\MeetingRound;
use RC\Tests\Infrastructure\Stub\Table\MeetingRoundPair;
use RC\Tests\Infrastructure\Stub\Table\MeetingRoundParticipant;
use RC\Tests\Infrastructure\Stub\Table\TelegramUser;

class ParticipantId2ParticipantsThatHeHasAlreadyMetWithTest extends TestCase
{
    public function test()
    {
        $connection = new ApplicationConnection();
        $this->seedBot($this->botId(), $connection);

        $this->seedMeetingRound($this->firstMeetingRoundId(), $this->botId(), new Past(new Now(), new NDays(5)), $connection);

        $this->seedTelegramUser($this->firstTelegramUserId(), $this->firstUserInternalTelegramId(), $connection);
        $this->seedTelegramUser($this->secondTelegramUserId(), $this->secondUserInternalTelegramId(), $connection);
        $this->seedTelegramUser($this->thirdTelegramUserId(), $this->thirdUserInternalTelegramId(), $connection);
        $this->seedTelegramUser($this->fourthTelegramUserId(), $this->fourthUserInternalTelegramId(), $connection);

        $this->seedParticipant($this->firstTelegramUserId(), $this->firstMeetingRoundId(), $this->firstParticipantIdInFirstRound(), $connection);
        $this->seedParticipant($this->secondTelegramUserId(), $this->firstMeetingRoundId(), $this->secondParticipantIdInFirstRound(), $connection);
        $this->seedParticipant($this->thirdTelegramUserId(), $this->firstMeetingRoundId(), $this->thirdParticipantIdInFirstRound(), $connection);
        $this->seedParticipant($this->fourthTelegramUserId(), $this->firstMeetingRoundId(), $this->fourthParticipantIdInFirstRound(), $connection);

        $this->seedPairFor($this->firstParticipantIdInFirstRound(), $this->secondParticipantIdInFirstRound(), $connection);
        $this->seedPairFor($this->secondParticipantIdInFirstRound(), $this->firstParticipantIdInFirstRound(), $connection);
        $this->seedPairFor($this->thirdParticipantIdInFirstRound(), $this->fourthParticipantIdInFirstRound(), $connection);
        $this->seedPairFor($this->fourthParticipantIdInFirstRound(), $this->thirdParticipantIdInFirstRound(), $connection);


        $this->seedMeetingRound($this->secondMeetingRoundId(), $this->botId(), new Past(new Now(), new NDays(4)), $connection);

        $this->seedParticipant($this->firstTelegramUserId(), $this->secondMeetingRoundId(), $this->firstParticipantIdInSecondRound(), $connection);
        $this->seedParticipant($this->secondTelegramUserId(), $this->secondMeetingRoundId(), $this->secondParticipantIdInSecondRound(), $connection);
        $this->seedParticipant($this->thirdTelegramUserId(), $this->secondMeetingRoundId(), $this->thirdParticipantIdInSecondRound(), $connection);
        $this->seedParticipant($this->fourthTelegramUserId(), $this->secondMeetingRoundId(), $this->fourthParticipantIdInSecondRound(), $connection);

        $this->seedPairFor($this->firstParticipantIdInSecondRound(), $this->thirdParticipantIdInSecondRound(), $connection);
        $this->seedPairFor($this->thirdParticipantIdInSecondRound(), $this->firstParticipantIdInSecondRound(), $connection);
        $this->seedPairFor($this->secondParticipantIdInSecondRound(), $this->fourthParticipantIdInSecondRound(), $connection);
        $this->seedPairFor($this->fourthParticipantIdInSecondRound(), $this->secondParticipantIdInSecondRound(), $connection);


        $this->seedMeetingRound($this->thirdMeetingRoundId(), $this->botId(), new Future(new Now(), new NDays(1)), $connection);

        $this->seedParticipant($this->firstTelegramUserId(), $this->thirdMeetingRoundId(), $this->firstParticipantIdInThirdRound(), $connection);
        $this->seedParticipant($this->secondTelegramUserId(), $this->thirdMeetingRoundId(), $this->secondParticipantIdInThirdRound(), $connection);
        $this->seedParticipant($this->thirdTelegramUserId(), $this->thirdMeetingRoundId(), $this->thirdParticipantIdInThirdRound(), $connection);
        $this->seedParticipant($this->fourthTelegramUserId(), $this->thirdMeetingRoundId(), $this->fourthParticipantIdInThirdRound(), $connection);

        $this->assertEquals(
            [
                $this->firstParticipantIdInThirdRound()->value() => [$this->secondParticipantIdInThirdRound()->value(), $this->thirdParticipantIdInThirdRound()->value()],
                $this->secondParticipantIdInThirdRound()->value() => [$this->firstParticipantIdInThirdRound()->value(), $this->fourthParticipantIdInThirdRound()->value()],
                $this->thirdParticipantIdInThirdRound()->value() => [$this->firstParticipantIdInThirdRound()->value(), $this->fourthParticipantIdInThirdRound()->value()],
                $this->fourthParticipantIdInThirdRound()->value() => [$this->secondParticipantIdInThirdRound()->value(), $this->thirdParticipantIdInThirdRound()->value()],
            ],
                (new ParticipantId2ParticipantsThatHeHasAlreadyMetWith(
                    new ById(new FromPure($this->thirdMeetingRoundId()), $connection),
                    $connection
                ))
                    ->value()->pure()->raw()
        );
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function botId(): BotId
    {
        return new FromUuid(new FromString('5bf56c96-859d-4f34-ae18-8c33ba8226f7'));
    }

    private function firstMeetingRoundId(): MeetingRoundId
    {
        return new RoundId('a49926cc-6956-457e-a44d-bae206426a8c');
    }

    private function secondMeetingRoundId(): MeetingRoundId
    {
        return new RoundId('222926cc-6956-457e-a44d-bae206426222');
    }

    private function thirdMeetingRoundId(): MeetingRoundId
    {
        return new RoundId('333926cc-6956-457e-a44d-bae206426333');
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

    private function fourthUserInternalTelegramId(): InternalTelegramUserId
    {
        return new FromInteger(4444444);
    }

    private function firstTelegramUserId(): TelegramUserId
    {
        return new TelegramUserFromUuid(new FromString('06eaff25-3124-4354-8e45-3b4d1f290242'));
    }

    private function secondTelegramUserId(): TelegramUserId
    {
        return new TelegramUserFromUuid(new FromString('5fa9092c-2a09-480d-9bb8-27ad09be1a21'));
    }

    private function thirdTelegramUserId(): TelegramUserId
    {
        return new TelegramUserFromUuid(new FromString('dc398a7a-3766-41b0-96a7-8bd81c5ada6e'));
    }

    private function fourthTelegramUserId(): TelegramUserId
    {
        return new TelegramUserFromUuid(new FromString('cba0785a-cb1a-47d2-975c-f436ce668690'));
    }

    private function firstParticipantIdInFirstRound(): ParticipantId
    {
        return new ParticipantIdFromString('111926cc-6956-457e-a44d-bae206426fff');
    }

    private function secondParticipantIdInFirstRound(): ParticipantId
    {
        return new ParticipantIdFromString('222926cc-6956-457e-a44d-bae206426eee');
    }

    private function thirdParticipantIdInFirstRound(): ParticipantId
    {
        return new ParticipantIdFromString('333926cc-6956-457e-a44d-bae206426ddd');
    }

    private function fourthParticipantIdInFirstRound(): ParticipantId
    {
        return new ParticipantIdFromString('444926cc-6956-457e-a44d-bae206426444');
    }

    private function firstParticipantIdInSecondRound(): ParticipantId
    {
        return new ParticipantIdFromString('e37f8de5-597d-49c2-9b13-f75fd03d2cdf');
    }

    private function secondParticipantIdInSecondRound(): ParticipantId
    {
        return new ParticipantIdFromString('222222cc-6956-457e-a44d-bae206222eee');
    }

    private function thirdParticipantIdInSecondRound(): ParticipantId
    {
        return new ParticipantIdFromString('333333cc-6956-457e-a44d-bae206333ddd');
    }

    private function fourthParticipantIdInSecondRound(): ParticipantId
    {
        return new ParticipantIdFromString('444444cc-6956-457e-a44d-bae206444444');
    }

    private function firstParticipantIdInThirdRound(): ParticipantId
    {
        return new ParticipantIdFromString('836246a4-41ca-4e1a-8fc8-6efbf7673365');
    }

    private function secondParticipantIdInThirdRound(): ParticipantId
    {
        return new ParticipantIdFromString('6cc38ad0-2909-405b-8ddd-c582efe2c863');
    }

    private function thirdParticipantIdInThirdRound(): ParticipantId
    {
        return new ParticipantIdFromString('cbc9374e-3fe1-4fae-8758-6fa18a199e53');
    }

    private function fourthParticipantIdInThirdRound(): ParticipantId
    {
        return new ParticipantIdFromString('728024c8-6be8-42fa-ab7e-c0616ff9647f');
    }

    private function seedBot(BotId $botId, OpenConnection $connection)
    {
        (new Bot($connection))
            ->insert([
                ['id' => $botId->value(),]
            ]);
    }

    private function seedMeetingRound(MeetingRoundId $meetingRoundId, BotId $botId, ISO8601DateTime $startDate, OpenConnection $connection)
    {
        (new MeetingRound($connection))
            ->insert([
                ['id' => $meetingRoundId->value(), 'bot_id' => $botId->value(), 'start_date' => $startDate->value()]
            ]);
    }

    private function seedTelegramUser(TelegramUserId $telegramUserId, InternalTelegramUserId $internalTelegramUserId, OpenConnection $connection)
    {
        (new TelegramUser($connection))
            ->insert([
                ['id' => $telegramUserId->value(), 'telegram_id' => $internalTelegramUserId->value()]
            ]);
    }

    private function seedParticipant(TelegramUserId $telegramUserId, MeetingRoundId $meetingRoundId, ParticipantId $participantId, OpenConnection $connection)
    {
        (new MeetingRoundParticipant($connection))
            ->insert([
                ['id' => $participantId->value(), 'user_id' => $telegramUserId->value(), 'meeting_round_id' => $meetingRoundId->value(), 'status' => (new Registered())->value()]
            ]);
    }

    private function seedPairFor(ParticipantId $firstParticipantId, ParticipantId $secondParticipantId, OpenConnection $connection)
    {
        (new MeetingRoundPair($connection))
            ->insert([
                ['participant_id' => $firstParticipantId->value(), 'match_participant_id' => $secondParticipantId->value()]
            ]);
    }

}