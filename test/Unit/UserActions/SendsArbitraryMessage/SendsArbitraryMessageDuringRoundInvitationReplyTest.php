<?php

declare(strict_types=1);

namespace RC\Tests\Unit\UserActions\SendsArbitraryMessage;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RC\Domain\BooleanAnswer\BooleanAnswerName\No;
use RC\Domain\BooleanAnswer\BooleanAnswerName\Yes;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Domain\RoundInvitation\ReadModel\LatestByTelegramUserIdAndBotId;
use RC\Domain\RoundInvitation\Status\Impure\FromInvitation;
use RC\Domain\RoundInvitation\Status\Impure\FromPure as ImpureStatusFromPure;
use RC\Domain\RoundInvitation\Status\Pure\Declined;
use RC\Domain\RoundInvitation\Status\Pure\Sent;
use RC\Domain\User\UserId\FromUuid as UserIdFromUuid;
use RC\Domain\User\UserId\UserId;
use RC\Domain\User\UserStatus\Pure\Registered;
use RC\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery;
use RC\Infrastructure\Http\Request\Url\Query\FromUrl;
use RC\Infrastructure\Http\Transport\Indifferent;
use RC\Infrastructure\Logging\LogId;
use RC\Infrastructure\Logging\Logs\DevNull;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotId\FromUuid;
use RC\Infrastructure\Logging\Logs\StdOut;
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
        $transport = new Indifferent();

        $response =
            (new SendsArbitraryMessage(
                (new UserMessage($this->telegramUserId(), (new No())->value()))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertInvitationIsDeclined($this->telegramUserId(), $this->botId(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'Хорошо, тогда до следующего раза! Если хотите что-то спросить или уточнить, смело пишите на @gorgonzola_support',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenUserAcceptsRoundInvitationThenInvitationBecomesAcceptedAndHeSeesTheFirstRoundRegistrationQuestion()
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
        $transport = new Indifferent();

        $response =
            (new SendsArbitraryMessage(
                (new UserMessage($this->telegramUserId(), (new Yes())->value()))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            ))
                ->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            'А опыт?',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
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

    private function assertInvitationIsDeclined(TelegramUserId $telegramUserId, BotId $botId, OpenConnection $connection)
    {
        $this->assertTrue(
            (new FromInvitation(
                new LatestByTelegramUserIdAndBotId($telegramUserId, $botId, $connection)
            ))
                ->equals(
                    new ImpureStatusFromPure(new Declined())
                )
        );
    }
}