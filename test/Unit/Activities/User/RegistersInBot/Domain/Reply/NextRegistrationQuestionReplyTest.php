<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Activities\User\RegistersInBot\Domain\Reply;

use PHPUnit\Framework\TestCase;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotId\FromUuid;
use RC\Domain\Experience\ExperienceId\Pure\BetweenAYearAndThree;
use RC\Domain\Experience\ExperienceId\Pure\BetweenThreeYearsAndSix;
use RC\Domain\Experience\ExperienceId\Pure\GreaterThanSix;
use RC\Domain\Experience\ExperienceId\Pure\LessThanAYear;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Domain\Position\PositionId\Pure\SystemOrBusinessAnalyst;
use RC\Domain\Position\PositionId\Pure\ProductDesigner;
use RC\Domain\Position\PositionId\Pure\ProductManager;
use RC\Activities\User\RegistersInBot\Domain\Reply\NextRegistrationQuestionReplyToUser;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Experience;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Position;
use RC\Domain\TelegramUser\UserId\Pure\FromUuid as UserIdFromUuid;
use RC\Domain\TelegramUser\UserId\Pure\TelegramUserId;
use RC\Domain\BotUser\UserStatus\Pure\RegistrationIsInProgress;
use RC\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery;
use RC\Infrastructure\Http\Request\Url\Query\FromUrl;
use RC\Infrastructure\Http\Transport\Indifferent;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromInteger;
use RC\Infrastructure\TelegramBot\UserId\Pure\InternalTelegramUserId;
use RC\Infrastructure\Uuid\Fixed;
use RC\Infrastructure\Uuid\FromString;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Stub\Table\Bot;
use RC\Tests\Infrastructure\Stub\Table\BotUser;
use RC\Tests\Infrastructure\Stub\Table\RegistrationQuestion;
use RC\Tests\Infrastructure\Stub\Table\TelegramUser;

class NextRegistrationQuestionReplyTest extends TestCase
{
    public function testPositionQuestion()
    {
        $connection = new ApplicationConnection();
        $httpTransport = new Indifferent();
        $this->seedBot($this->botId(), $connection);
        $this->seedBotUser($this->botId(), $this->telegramUserId(), $connection);
        $this->seedPositionQuestion($this->botId(), $connection);

        (new NextRegistrationQuestionReplyToUser(
            $this->telegramUserId(),
            $this->botId(),
            $connection,
            $httpTransport
        ))
            ->value();

        $this->assertCount(1, $httpTransport->sentRequests());
        $this->assertEquals('Кем работаете?', (new FromQuery(new FromUrl($httpTransport->sentRequests()[0]->url())))->value()['text']);
        $this->assertEquals(
            [
                [['text' => 'Продакт-менеджер'], ['text' => 'Продуктовый дизайнер']],
                [['text' => 'Системный/бизнес-аналитик']],
            ],
            json_decode(
                (new FromQuery(
                    new FromUrl(
                        $httpTransport->sentRequests()[0]->url()
                    )
                ))
                    ->value()['reply_markup'],
                true
            )['keyboard']
        );
    }

    public function testExperienceQuestion()
    {
        $connection = new ApplicationConnection();
        $httpTransport = new Indifferent();
        $this->seedBot($this->botId(), $connection);
        $this->seedBotUser($this->botId(), $this->telegramUserId(), $connection);
        $this->seedExperienceQuestion($this->botId(), $connection);

        (new NextRegistrationQuestionReplyToUser(
            $this->telegramUserId(),
            $this->botId(),
            $connection,
            $httpTransport
        ))
            ->value();

        $this->assertCount(1, $httpTransport->sentRequests());
        $this->assertEquals('Сколько?', (new FromQuery(new FromUrl($httpTransport->sentRequests()[0]->url())))->value()['text']);
        $this->assertEquals(
            [
                [['text' => 'Меньше года'], ['text' => 'От года до трёх лет']],
                [['text' => 'От трёх лет до шести'], ['text' => 'Больше шести лет']],
            ],
            json_decode(
                (new FromQuery(
                    new FromUrl(
                        $httpTransport->sentRequests()[0]->url()
                    )
                ))
                    ->value()['reply_markup'],
                true
            )['keyboard']
        );
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function telegramUserId(): InternalTelegramUserId
    {
        return new FromInteger(987654);
    }

    private function botId(): BotId
    {
        return new FromUuid(new Fixed());
    }

    private function userId(): TelegramUserId
    {
        return new UserIdFromUuid(new FromString('103729d6-330c-4123-b856-d5196812d509'));
    }

    private function seedBotUser(BotId $botId, InternalTelegramUserId $telegramUserId, OpenConnection $connection)
    {
        (new TelegramUser($connection))
            ->insert([
                ['id' => $this->userId()->value(), 'first_name' => 'Vadim', 'last_name' => 'Samokhin', 'telegram_id' => $telegramUserId->value(), 'telegram_handle' => 'dremuchee_bydlo'],
            ]);
        (new BotUser($connection))
            ->insert([
                ['bot_id' => $botId->value(), 'user_id' => $this->userId()->value(), 'status' => (new RegistrationIsInProgress())->value()]
            ]);
    }

    private function seedBot(BotId $botId, OpenConnection $connection)
    {
        (new Bot($connection))
            ->insert([
                [
                    'id' => $botId->value(),
                    'available_positions' => [(new ProductManager())->value(), (new ProductDesigner())->value(), (new SystemOrBusinessAnalyst())->value()],
                    'available_experiences' => [(new LessThanAYear())->value(), (new BetweenAYearAndThree())->value(), (new BetweenThreeYearsAndSix())->value(), (new GreaterThanSix())->value()],
                ]
            ]);
    }

    private function seedPositionQuestion(BotId $botId, OpenConnection $connection)
    {
        (new RegistrationQuestion($connection))
            ->insert([
                ['profile_record_type' => (new Position())->value(), 'bot_id' => $botId->value(), 'text' => 'Кем работаете?']
            ]);
    }

    private function seedExperienceQuestion(BotId $botId, OpenConnection $connection)
    {
        (new RegistrationQuestion($connection))
            ->insert([
                ['profile_record_type' => (new Experience())->value(), 'bot_id' => $botId->value(), 'text' => 'Сколько?']
            ]);
    }
}