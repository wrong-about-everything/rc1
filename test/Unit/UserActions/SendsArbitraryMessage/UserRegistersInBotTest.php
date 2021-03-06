<?php

declare(strict_types=1);

namespace RC\Tests\Unit\UserActions\SendsArbitraryMessage;

use Meringue\ISO8601DateTime;
use Meringue\ISO8601DateTime\FromISO8601;
use Meringue\ISO8601Interval\Floating\NDays;
use Meringue\ISO8601Interval\Floating\OneDay;
use Meringue\Timeline\Point\Now;
use Meringue\Timeline\Point\Past;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RC\Domain\About\Impure\FromBotUser as AboutBotUser;
use RC\Domain\BotUser\ReadModel\ByInternalTelegramUserIdAndBotId;
use RC\Domain\Experience\ExperienceId\Impure\FromBotUser as ExperienceFromBotUser;
use RC\Domain\Experience\ExperienceId\Impure\FromPure as ImpureExperienceFromPure;
use RC\Domain\Experience\ExperienceId\Pure\Experience as UserExperience;
use RC\Domain\Experience\ExperienceId\Pure\LessThanAYear;
use RC\Domain\Experience\ExperienceName\LessThanAYearName;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Domain\Position\PositionId\Impure\FromBotUser;
use RC\Domain\Position\PositionId\Impure\FromPure;
use RC\Domain\Position\PositionId\Pure\Position as UserPosition;
use RC\Domain\Position\PositionId\Pure\ProductDesigner;
use RC\Domain\Position\PositionId\Pure\ProductManager;
use RC\Domain\Position\PositionName\ProductDesignerName;
use RC\Domain\Position\PositionName\ProductManagerName;
use RC\Domain\RegistrationQuestion\RegistrationQuestionId\Impure\FromString as RegistrationQuestionIdFromString;
use RC\Domain\RegistrationQuestion\RegistrationQuestionId\Impure\RegistrationQuestionId;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\About;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\RegistrationQuestionType;
use RC\Domain\TelegramBot\UserMessage\Pure\Skipped;
use RC\Domain\TelegramUser\UserId\Pure\FromUuid as UserIdFromUuid;
use RC\Domain\TelegramUser\UserId\Pure\TelegramUserId;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Experience;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Position;
use RC\Domain\BotUser\UserStatus\Impure\FromReadModelBotUser as UserStatusFromBotUser;
use RC\Domain\BotUser\UserStatus\Impure\FromPure as ImpureUserStatusFromPure;
use RC\Domain\BotUser\UserStatus\Pure\Registered;
use RC\Domain\BotUser\UserStatus\Pure\RegistrationIsInProgress;
use RC\Domain\BotUser\UserStatus\Pure\UserStatus;
use RC\Infrastructure\Http\Request\Outbound\Request;
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
use RC\Tests\Infrastructure\Stub\Table\MeetingRound;
use RC\Tests\Infrastructure\Stub\Table\RegistrationQuestion;
use RC\Tests\Infrastructure\Stub\Table\TelegramUser;
use RC\Tests\Infrastructure\Stub\Table\UserRegistrationProgress;
use RC\Tests\Infrastructure\Stub\TelegramMessage\UserMessage;
use RC\UserActions\SendsArbitraryMessage\SendsArbitraryMessage;

class UserRegistersInBotTest extends TestCase
{
    public function testWhenNewUserAnswersTheFirstQuestionThenHisAnswerIsPersistedAndHeSeesTheSecondQuestion()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $this->availablePositionIds(), $connection);
        $this->createTelegramUser($this->userId(), $this->telegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->userId(), new RegistrationIsInProgress(), $connection);
        $this->createRegistrationQuestion($this->firstRegistrationQuestionIdInFirstBot(), new Position(), $this->botId(), 1, '?????????? ?? ?????? ???????????????????', $connection);
        $this->createRegistrationQuestion($this->secondRegistrationQuestionIdInFirstBot(), new Experience(), $this->botId(), 2, '?? ?????????', $connection);

        $this->createBot($this->secondBotId(), $this->availablePositionIds(), $connection);
        $this->createRegistrationQuestion($this->firstRegistrationQuestionIdInSecondBot(), new Position(), $this->secondBotId(), 1, '?????????? ?? ?????? ???????????????????', $connection);
        $this->createRegistrationQuestion($this->secondRegistrationQuestionIdInSecondBot(), new Experience(), $this->secondBotId(), 2, '?? ?????????', $connection);

        $transport = new Indifferent();

        $response = $this->userReply((new ProductManagerName())->value(), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserRegistrationProgressUpdated($this->userId(), $this->firstRegistrationQuestionIdInFirstBot(), $connection);
        $this->assertPositionIs($this->telegramUserId(), $this->botId(), new ProductManager(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            '?? ?????????',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenNewUserAnswersTheFirstQuestionWithTextInsteadOfPressingButtonThenHeSeesValidationErrorAndWhenHeAnswersWithAButtonThenHeSeesTheSecondQuestion()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $this->availablePositionIds(), $connection);
        $this->createTelegramUser($this->userId(), $this->telegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->userId(), new RegistrationIsInProgress(), $connection);
        $this->createRegistrationQuestion($this->firstRegistrationQuestionIdInFirstBot(), new Position(), $this->botId(), 1, '?????????? ?? ?????? ???????????????????', $connection);
        $this->createRegistrationQuestion($this->secondRegistrationQuestionIdInFirstBot(), new Experience(), $this->botId(), 2, '?? ?????????', $connection);

        $this->createBot($this->secondBotId(), $this->availablePositionIds(), $connection);
        $this->createRegistrationQuestion($this->firstRegistrationQuestionIdInSecondBot(), new Position(), $this->secondBotId(), 1, '?????????? ?? ?????? ???????????????????', $connection);
        $this->createRegistrationQuestion($this->secondRegistrationQuestionIdInSecondBot(), new Experience(), $this->secondBotId(), 2, '?? ?????????', $connection);

        $transport = new Indifferent();

        $response = $this->userReply('???????', $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            '?? ??????????????????, ???? ???????? ???? ?????????? ?????????????? ?????????? ?? ???????? ????????????. ?????????????? ????????????????, ????????????????????, ???????? ???? ?????????????????? ????????????. ???????? ???? ???????? ???? ???????????????? ??? ???????????????? ?? @gorgonzola_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
        $this->assertReplyButtons($transport->sentRequests()[0]);

        $secondResponse = $this->userReply((new ProductManagerName())->value(), $transport, $connection)->response();

        $this->assertTrue($secondResponse->isSuccessful());
        $this->assertUserRegistrationProgressUpdated($this->userId(), $this->firstRegistrationQuestionIdInFirstBot(), $connection);
        $this->assertPositionIs($this->telegramUserId(), $this->botId(), new ProductManager(), $connection);
        $this->assertCount(2, $transport->sentRequests());
        $this->assertEquals(
            '?? ?????????',
            (new FromQuery(new FromUrl($transport->sentRequests()[1]->url())))->value()['text']
        );
    }

    public function testWhenNewUserAnswersTheFirstQuestionWithTextInsteadOfPressingButtonThenHeSeesValidationError()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $this->availablePositionIds(), $connection);
        $this->createTelegramUser($this->userId(), $this->telegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->userId(), new RegistrationIsInProgress(), $connection);
        $this->createRegistrationQuestion($this->firstRegistrationQuestionIdInFirstBot(), new Position(), $this->botId(), 1, '?????????? ?? ?????? ???????????????????', $connection);
        $this->createRegistrationQuestion($this->secondRegistrationQuestionIdInFirstBot(), new Experience(), $this->botId(), 2, '?? ?????????', $connection);

        $this->createBot($this->secondBotId(), $this->availablePositionIds(), $connection);
        $this->createRegistrationQuestion($this->firstRegistrationQuestionIdInSecondBot(), new Position(), $this->secondBotId(), 1, '?????????? ?? ?????? ???????????????????', $connection);
        $this->createRegistrationQuestion($this->secondRegistrationQuestionIdInSecondBot(), new Experience(), $this->secondBotId(), 2, '?? ?????????', $connection);

        $transport = new Indifferent();

        $response = $this->userReply('???????', $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            '?? ??????????????????, ???? ???????? ???? ?????????? ?????????????? ?????????? ?? ???????? ????????????. ?????????????? ????????????????, ????????????????????, ???????? ???? ?????????????????? ????????????. ???????? ???? ???????? ???? ???????????????? ??? ???????????????? ?? @gorgonzola_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenExistingButNotYetRegisteredUserAnswersTheLastQuestionThenHeBecomesRegisteredAndSeesCongratulations()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $this->availablePositionIds(), $connection);
        $this->createTelegramUser($this->userId(), $this->telegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->userId(), new RegistrationIsInProgress(), $connection);
        $this->createRegistrationQuestion($this->firstRegistrationQuestionIdInFirstBot(), new Position(), $this->botId(), 1, '?????????? ?? ?????? ???????????????????', $connection);
        $this->createRegistrationQuestion($this->secondRegistrationQuestionIdInFirstBot(), new Experience(), $this->botId(), 2, '?? ?????????', $connection);
        $this->createRegistrationProgress($this->firstRegistrationQuestionIdInFirstBot(), $this->userId(), $connection);

        $this->createBot($this->secondBotId(), $this->availablePositionIds(), $connection);
        $this->createRegistrationQuestion($this->firstRegistrationQuestionIdInSecondBot(), new Position(), $this->secondBotId(), 1, '?????????? ?? ?????? ???????????????????', $connection);
        $this->createRegistrationQuestion($this->secondRegistrationQuestionIdInSecondBot(), new Experience(), $this->secondBotId(), 2, '?? ?????????', $connection);

        $transport = new Indifferent();

        $response = $this->userReply((new LessThanAYearName())->value(), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserRegistrationProgressUpdated($this->userId(), $this->secondRegistrationQuestionIdInFirstBot(), $connection);
        $this->assertExperienceIs($this->telegramUserId(), $this->botId(), new LessThanAYear(), $connection);
        $this->assertUserIs($this->telegramUserId(), $this->botId(), new Registered(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            '????????????????????, ???? ????????????????????????????????????! ???????? ???????????? ??????-???? ???????????????? ?????? ????????????????, ?????????? ???????????? ???? @gorgonzola_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenExistingButNotYetRegisteredUserSkipsTheLastAboutMeQuestionThenHeBecomesRegisteredAndSeesCongratulations()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $this->availablePositionIds(), $connection);
        $this->createTelegramUser($this->userId(), $this->telegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->userId(), new RegistrationIsInProgress(), $connection);
        $this->createRegistrationQuestion($this->firstRegistrationQuestionIdInFirstBot(), new Position(), $this->botId(), 1, '?????????? ?? ?????? ???????????????????', $connection);
        $this->createRegistrationQuestion($this->secondRegistrationQuestionIdInFirstBot(), new About(), $this->botId(), 2, '???????????????????? ?????? ?? ????????, ???????? ????????????', $connection);
        $this->createRegistrationProgress($this->firstRegistrationQuestionIdInFirstBot(), $this->userId(), $connection);

        $this->createBot($this->secondBotId(), $this->availablePositionIds(), $connection);
        $this->createRegistrationQuestion($this->firstRegistrationQuestionIdInSecondBot(), new Position(), $this->secondBotId(), 1, '?????????? ?? ?????? ???????????????????', $connection);
        $this->createRegistrationQuestion($this->secondRegistrationQuestionIdInSecondBot(), new Experience(), $this->secondBotId(), 2, '?? ?????????', $connection);

        $transport = new Indifferent();

        $response = $this->userReply((new Skipped())->value(), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserRegistrationProgressUpdated($this->userId(), $this->secondRegistrationQuestionIdInFirstBot(), $connection);
        $this->assertAboutMeIsEmpty($this->telegramUserId(), $this->botId(), $connection);
        $this->assertUserIs($this->telegramUserId(), $this->botId(), new Registered(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            '????????????????????, ???? ????????????????????????????????????! ???????? ???????????? ??????-???? ???????????????? ?????? ????????????????, ?????????? ???????????? ???? @gorgonzola_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenExistingButNotYetRegisteredUserAnswersTheLastAboutMeQuestionThenHeBecomesRegisteredAndSeesCongratulations()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $this->availablePositionIds(), $connection);
        $this->createTelegramUser($this->userId(), $this->telegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->userId(), new RegistrationIsInProgress(), $connection);
        $this->createRegistrationQuestion($this->firstRegistrationQuestionIdInFirstBot(), new Position(), $this->botId(), 1, '?????????? ?? ?????? ???????????????????', $connection);
        $this->createRegistrationQuestion($this->secondRegistrationQuestionIdInFirstBot(), new About(), $this->botId(), 2, '???????????????????? ?????? ?? ????????, ???????? ????????????', $connection);
        $this->createRegistrationProgress($this->firstRegistrationQuestionIdInFirstBot(), $this->userId(), $connection);

        $this->createBot($this->secondBotId(), $this->availablePositionIds(), $connection);
        $this->createRegistrationQuestion($this->firstRegistrationQuestionIdInSecondBot(), new Position(), $this->secondBotId(), 1, '?????????? ?? ?????? ???????????????????', $connection);
        $this->createRegistrationQuestion($this->secondRegistrationQuestionIdInSecondBot(), new Experience(), $this->secondBotId(), 2, '?? ?????????', $connection);

        $transport = new Indifferent();

        $response = $this->userReply('??????????????, ?????? ???? ?????????? ?? ???????? ?? ???????? ????????????????????!', $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserRegistrationProgressUpdated($this->userId(), $this->secondRegistrationQuestionIdInFirstBot(), $connection);
        $this->assertAboutMeIs($this->telegramUserId(), $this->botId(), '??????????????, ?????? ???? ?????????? ?? ???????? ?? ???????? ????????????????????!', $connection);
        $this->assertUserIs($this->telegramUserId(), $this->botId(), new Registered(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            '????????????????????, ???? ????????????????????????????????????! ???????? ???????????? ??????-???? ???????????????? ?????? ????????????????, ?????????? ???????????? ???? @gorgonzola_support_bot',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testWhenRegisteredUserSendsArbitraryMessageThenHeSeesStatusInfo()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $this->availablePositionIds(), $connection);
        $this->createTelegramUser($this->userId(), $this->telegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->userId(), new Registered(), $connection);

        $this->createBot($this->secondBotId(), $this->availablePositionIds(), $connection);
        $this->createRegistrationQuestion($this->firstRegistrationQuestionIdInSecondBot(), new Position(), $this->secondBotId(), 1, '?????????? ?? ?????? ???????????????????', $connection);
        $this->createRegistrationQuestion($this->secondRegistrationQuestionIdInSecondBot(), new Experience(), $this->secondBotId(), 2, '?? ?????????', $connection);

        $transport = new Indifferent();

        $response = $this->userReply((new LessThanAYearName())->value(), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            '???????????? ??????-???? ????????????????? ?????????? ???????????? ???? @gorgonzola_support_bot!',
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    public function testGivenMeetingRoundAheadWhenUserAnswersTheLastQuestionThenHeSeesAnInvitationToAMeetingRound()
    {
        $connection = new ApplicationConnection();
        $this->createBot($this->botId(), $this->availablePositionIds(), $connection);
        $this->createTelegramUser($this->userId(), $this->telegramUserId(), $connection);
        $this->createBotUser($this->botId(), $this->userId(), new RegistrationIsInProgress(), $connection);
        $this->createRegistrationQuestion($this->firstRegistrationQuestionIdInFirstBot(), new Position(), $this->botId(), 1, '?????????? ?? ?????? ???????????????????', $connection);
        $this->createRegistrationQuestion($this->secondRegistrationQuestionIdInFirstBot(), new Experience(), $this->botId(), 2, '?? ?????????', $connection);
        $this->createRegistrationProgress($this->firstRegistrationQuestionIdInFirstBot(), $this->userId(), $connection);
        $this->createMeetingRound(Uuid::uuid4()->toString(), $this->botId(), new Past(new Now(), new OneDay()), new Past(new Now(), new NDays(2)), $connection);
        $this->createMeetingRound($this->futureMeetingRoundId(), $this->botId(), new FromISO8601('2025-08-08T09:00:00+03'), new Now(), $connection);

        $this->createBot($this->secondBotId(), $this->availablePositionIds(), $connection);
        $this->createRegistrationQuestion($this->firstRegistrationQuestionIdInSecondBot(), new Position(), $this->secondBotId(), 1, '?????????? ?? ?????? ???????????????????', $connection);
        $this->createRegistrationQuestion($this->secondRegistrationQuestionIdInSecondBot(), new Experience(), $this->secondBotId(), 2, '?? ?????????', $connection);

        $transport = new Indifferent();

        $response = $this->userReply((new LessThanAYearName())->value(), $transport, $connection)->response();

        $this->assertTrue($response->isSuccessful());
        $this->assertUserRegistrationProgressUpdated($this->userId(), $this->firstRegistrationQuestionIdInFirstBot(), $connection);
        $this->assertExperienceIs($this->telegramUserId(), $this->botId(), new LessThanAYear(), $connection);
        $this->assertUserIs($this->telegramUserId(), $this->botId(), new Registered(), $connection);
        $this->assertCount(1, $transport->sentRequests());
        $this->assertEquals(
            <<<q
?????????????? ???? ????????????!

?? ?????? ?????? ???????????????????? ??????????????, ???????????? ??????????????????????????? ???????????? ?????? ???????? 8 ?????????????? (?????? ??????????????), ?? ?????????? ?? ?????? ??????????????????????, ???????????? ?????? ??????????????, ???????????????????????? ?????????? ??????????.
q
            ,
            (new FromQuery(new FromUrl($transport->sentRequests()[0]->url())))->value()['text']
        );
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function availablePositionIds()
    {
        return [(new ProductManager())->value(), (new ProductDesigner())->value()];
    }

    private function telegramUserId(): InternalTelegramUserId
    {
        return new FromInteger(654987);
    }

    private function botId(): BotId
    {
        return new FromUuid(new Fixed());
    }

    private function secondBotId(): BotId
    {
        return new FromUuid(new FromString('b19a1fbe-4480-4c65-8d9b-0ca0e20173f2'));
    }

    private function userId(): TelegramUserId
    {
        return new UserIdFromUuid(new FromString('103729d6-330c-4123-b856-d5196812d509'));
    }

    private function firstRegistrationQuestionIdInFirstBot(): RegistrationQuestionId
    {
        return new RegistrationQuestionIdFromString('203729d6-330c-4123-b856-d5196812d509');
    }

    private function firstRegistrationQuestionIdInSecondBot(): RegistrationQuestionId
    {
        return new RegistrationQuestionIdFromString('f016f0b9-34e7-4103-96f4-235b8920c5d8');
    }

    private function futureMeetingRoundId(): string
    {
        return Uuid::uuid4()->toString();
    }

    private function secondRegistrationQuestionIdInFirstBot(): RegistrationQuestionId
    {
        return new RegistrationQuestionIdFromString('303729d6-330c-4123-b856-d5196812d509');
    }

    private function secondRegistrationQuestionIdInSecondBot(): RegistrationQuestionId
    {
        return new RegistrationQuestionIdFromString('c6282bc5-868f-4e86-aef6-c05de3c3edc7');
    }

    private function createBot(BotId $botId, array $availablePositionIds, OpenConnection $connection)
    {
        (new Bot($connection))
            ->insert([
                ['id' => $botId->value(), 'token' => Uuid::uuid4()->toString(), 'name' => 'vasya_bot', 'available_positions' => $availablePositionIds]
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
                [
                    'bot_id' => $botId->value(),
                    'user_id' => $userId->value(),
                    'status' => $status->value(),
                    'position' => null,
                    'experience' => null,
                    'about' => null,
                ]
            ]);
    }

    private function createRegistrationQuestion(RegistrationQuestionId $registrationQuestionId, RegistrationQuestionType $questionType, BotId $botId, int $ordinalNumber, string $text, OpenConnection $connection)
    {
        (new RegistrationQuestion($connection))
            ->insert([
                ['id' => $registrationQuestionId->value()->pure()->raw(), 'profile_record_type' => $questionType->value(), 'bot_id' => $botId->value(), 'ordinal_number' => $ordinalNumber, 'text' => $text],
            ]);
    }

    private function createRegistrationProgress(RegistrationQuestionId $registrationQuestionId, TelegramUserId $userId, OpenConnection $connection)
    {
        (new UserRegistrationProgress($connection))
            ->insert([
                ['registration_question_id' => $registrationQuestionId->value()->pure()->raw(), 'user_id' => $userId->value()]
            ]);
    }

    private function createMeetingRound(string $meetingRoundId, BotId $botId, ISO8601DateTime $startDateTime, ISO8601DateTime $invitationDateTime, $connection)
    {
        (new MeetingRound($connection))
            ->insert([
                ['id' => $meetingRoundId, 'bot_id' => $botId->value(), 'start_date' => $startDateTime->value(), 'invitation_date' => $invitationDateTime->value()]
            ]);
    }

    private function userReply(string $text, HttpTransport $transport, OpenConnection $connection)
    {
        return
            new SendsArbitraryMessage(
                new Now(),
                (new UserMessage($this->telegramUserId(), $text))->value(),
                $this->botId()->value(),
                $transport,
                $connection,
                new DevNull()
            );
    }

    private function assertUserRegistrationProgressUpdated(TelegramUserId $userId, RegistrationQuestionId $registrationQuestionId, OpenConnection $connection)
    {
        $this->assertNotEmpty(
            (new Selecting(
                <<<q
select *
from user_registration_progress urp
where urp.user_id = ? and urp.registration_question_id = ?
q
                ,
                [$userId->value(), $registrationQuestionId->value()->pure()->raw()],
                $connection
            ))
                ->response()->pure()->raw()
        );
    }

    private function assertPositionIs(InternalTelegramUserId $telegramUserId, BotId $botId, UserPosition $position, OpenConnection $connection)
    {
        $this->assertTrue(
            (new FromBotUser(
                new ByInternalTelegramUserIdAndBotId($telegramUserId, $botId, $connection)
            ))
                ->equals(
                    new FromPure($position)
                )
        );
    }

    private function assertExperienceIs(InternalTelegramUserId $telegramUserId, BotId $botId, UserExperience $experience, OpenConnection $connection)
    {
        $this->assertTrue(
            (new ExperienceFromBotUser(
                new ByInternalTelegramUserIdAndBotId($telegramUserId, $botId, $connection)
            ))
                ->equals(
                    new ImpureExperienceFromPure($experience)
                )
        );
    }

    private function assertAboutMeIsEmpty(InternalTelegramUserId $telegramUserId, BotId $botId, OpenConnection $connection)
    {
        $this->assertTrue(
            (new AboutBotUser(
                new ByInternalTelegramUserIdAndBotId($telegramUserId, $botId, $connection)
            ))
                ->empty()->pure()->raw()
        );
    }

    private function assertAboutMeIs(InternalTelegramUserId $telegramUserId, BotId $botId, string $about, OpenConnection $connection)
    {
        $this->assertEquals(
            $about,
            (new AboutBotUser(
                new ByInternalTelegramUserIdAndBotId($telegramUserId, $botId, $connection)
            ))
                ->value()->pure()->raw()
        );
    }

    private function assertUserIs(InternalTelegramUserId $telegramUserId, BotId $botId, UserStatus $userStatus, OpenConnection $connection)
    {
        $this->assertTrue(
            (new UserStatusFromBotUser(
                new ByInternalTelegramUserIdAndBotId($telegramUserId, $botId, $connection)
            ))
                ->equals(
                    new ImpureUserStatusFromPure($userStatus)
                )
        );
    }

    private function assertReplyButtons(Request $request)
    {
        $this->assertEquals(
            [
                [['text' => (new ProductManagerName())->value()], ['text' => (new ProductDesignerName())->value()]]
            ],
            json_decode((new FromQuery(new FromUrl($request->url())))->value()['reply_markup'], true)['keyboard']
        );
    }
}