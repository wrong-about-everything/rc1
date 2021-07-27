<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Activities\User\RegistersInBot\Domain\Reply;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotId\FromUuid;
use RC\Domain\Experience\ExperienceId\Pure\BetweenAYearAndThree;
use RC\Domain\Experience\ExperienceId\Pure\BetweenThreeYearsAndSix;
use RC\Domain\Experience\ExperienceId\Pure\GreaterThanSix;
use RC\Domain\Experience\ExperienceId\Pure\LessThanAYear;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Domain\Position\PositionId\Pure\Analyst;
use RC\Domain\Position\PositionId\Pure\ProductDesigner;
use RC\Domain\Position\PositionId\Pure\ProductManager;
use RC\Activities\User\RegistersInBot\Domain\Reply\NextRegistrationQuestionReply;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Experience;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Position;
use RC\Infrastructure\Http\Request\Url\ParsedQuery\FromQuery;
use RC\Infrastructure\Http\Request\Url\Query\FromUrl;
use RC\Infrastructure\Http\Transport\Indifferent;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromInteger;
use RC\Infrastructure\TelegramBot\UserId\Pure\TelegramUserId;
use RC\Infrastructure\Uuid\Fixed;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Stub\Table\Bot;
use RC\Tests\Infrastructure\Stub\Table\BotUser;
use RC\Tests\Infrastructure\Stub\Table\RegistrationQuestion;

class NextRegistrationQuestionReplyTest extends TestCase
{
    public function testPositionQuestion()
    {
        $connection = new ApplicationConnection();
        $httpTransport = new Indifferent();
        $this->seedBot($this->botId(), $connection);
        $this->seedBotUser($this->botId(), $this->telegramUserId(), $connection);
        $this->seedPositionQuestion($this->botId(), $connection);

        (new NextRegistrationQuestionReply(
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
                [['text' => 'Продакт-менеджер']],
                [['text' => 'Продуктовый дизайнер']],
                [['text' => 'Аналитик']],
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

        (new NextRegistrationQuestionReply(
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
                [['text' => 'Меньше года']],
                [['text' => 'От года до трёх лет']],
                [['text' => 'От трёх лет до шести']],
                [['text' => 'Больше шести лет']],
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

    private function telegramUserId(): TelegramUserId
    {
        return new FromInteger(987654);
    }

    private function botId(): BotId
    {
        return new FromUuid(new Fixed());
    }

    private function seedBotUser(BotId $botId, TelegramUserId $telegramUserId, OpenConnection $connection)
    {
        (new BotUser($botId, $connection))
            ->insert(
                ['id' => Uuid::uuid4()->toString(), 'telegram_id' => $telegramUserId->value()],
                []
            );
    }

    private function seedBot(BotId $botId, OpenConnection $connection)
    {
        (new Bot($connection))
            ->insert([
                [
                    'id' => $botId->value(),
                    'available_positions' => [(new ProductManager())->value(), (new ProductDesigner())->value(), (new Analyst())->value()],
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