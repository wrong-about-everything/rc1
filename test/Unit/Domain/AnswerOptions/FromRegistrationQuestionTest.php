<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Domain\AnswerOptions;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\About;
use RC\Domain\SentReplyToUser\ReplyOptions\FromRegistrationQuestion;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotId\FromUuid;
use RC\Domain\Experience\ExperienceId\Pure\BetweenAYearAndThree;
use RC\Domain\Experience\ExperienceId\Pure\BetweenThreeYearsAndSix;
use RC\Domain\Experience\ExperienceId\Pure\GreaterThanSix;
use RC\Domain\Experience\ExperienceId\Pure\LessThanAYear;
use RC\Domain\Experience\ExperienceName\BetweenAYearAndThreeName;
use RC\Domain\Experience\ExperienceName\BetweenThreeYearsAndSixName;
use RC\Domain\Experience\ExperienceName\GreaterThanSixYearsName;
use RC\Domain\Experience\ExperienceName\LessThanAYearName;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\RootConnection;
use RC\Domain\Position\PositionId\Pure\FromInteger;
use RC\Domain\Position\PositionId\Pure\ProductAnalyst;
use RC\Domain\Position\PositionId\Pure\ProductDesigner;
use RC\Domain\Position\PositionId\Pure\ProductManager;
use RC\Domain\Position\PositionId\Pure\ProjectManager;
use RC\Domain\Position\PositionId\Pure\SystemOrBusinessAnalyst;
use RC\Domain\Position\PositionName\FromPosition;
use RC\Domain\Position\PositionName\ProductAnalystName;
use RC\Domain\Position\PositionName\ProductDesignerName;
use RC\Domain\Position\PositionName\ProductManagerName;
use RC\Domain\Position\PositionName\ProjectManagerName;
use RC\Domain\Position\PositionName\SystemOrBusinessAnalystName;
use RC\Domain\RegistrationQuestion\ById;
use RC\Domain\RegistrationQuestion\RegistrationQuestion as DomainRegistrationQuestion;
use RC\Domain\RegistrationQuestion\RegistrationQuestionId\Pure\FromString as RegistrationQuestionIdFromString;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Experience;
use RC\Domain\RegistrationQuestion\RegistrationQuestionType\Pure\Position;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\Uuid\FromString;
use RC\Tests\Infrastructure\Environment\Reset;
use RC\Tests\Infrastructure\Stub\Table\Bot;
use RC\Tests\Infrastructure\Stub\Table\RegistrationQuestion;

class FromRegistrationQuestionTest extends TestCase
{
    public function testMultiplePositions()
    {
        $connection = new ApplicationConnection();
        $this->seedBot($this->botId(), $this->availablePositionIds(), $connection);
        $registrationQuestion = $this->newPositionRegistrationQuestion($this->botId(), $connection);

        $this->assertEquals(
            [
                [['text' => (new ProductAnalystName())->value()], ['text' => (new ProductDesignerName())->value()]],
                [['text' => (new SystemOrBusinessAnalystName())->value()], ['text' => (new ProductManagerName())->value()]],
                [['text' => (new ProjectManagerName())->value()]],
            ],
            (new FromRegistrationQuestion($registrationQuestion, $this->botId(), $connection))->value()->pure()->raw()
        );
    }

    public function testMultipleExperiences()
    {
        $connection = new ApplicationConnection();
        $this->seedBot($this->botId(), $this->availablePositionIds(), $connection);
        $registrationQuestion = $this->newExperienceRegistrationQuestion($this->botId(), $connection);

        $this->assertEquals(
            [
                [['text' => (new LessThanAYearName())->value()], ['text' => (new BetweenAYearAndThreeName())->value()]],
                [['text' => (new BetweenThreeYearsAndSixName())->value()], ['text' => (new GreaterThanSixYearsName())->value()]],
            ],
            (new FromRegistrationQuestion($registrationQuestion, $this->botId(), $connection))->value()->pure()->raw()
        );
    }

    public function testAboutMeQuestionCanBeSkipped()
    {
        $connection = new ApplicationConnection();
        $this->seedBot($this->botId(), [], $connection);
        $registrationQuestion = $this->newAboutMeRegistrationQuestion($this->botId(), $connection);

        $this->assertEquals(
            [
                [['text' => 'Пропустить']],
            ],
            (new FromRegistrationQuestion($registrationQuestion, $this->botId(), $connection))->value()->pure()->raw()
        );
    }

    protected function setUp(): void
    {
        (new Reset(new RootConnection()))->run();
    }

    private function newPositionRegistrationQuestion(BotId $botId, OpenConnection $connection): DomainRegistrationQuestion
    {
        $id = Uuid::uuid4()->toString();
        (new RegistrationQuestion($connection))
            ->insert([
                ['id' => $id, 'bot_id' => $botId->value(), 'profile_record_type' => (new Position())->value()]
            ]);

        return new ById(new RegistrationQuestionIdFromString($id), $connection);
    }

    private function newExperienceRegistrationQuestion(BotId $botId, OpenConnection $connection): DomainRegistrationQuestion
    {
        $id = Uuid::uuid4()->toString();
        (new RegistrationQuestion($connection))
            ->insert([
                ['id' => $id, 'bot_id' => $botId->value(), 'profile_record_type' => (new Experience())->value()]
            ]);

        return new ById(new RegistrationQuestionIdFromString($id), $connection);
    }

    private function newAboutMeRegistrationQuestion(BotId $botId, OpenConnection $connection): DomainRegistrationQuestion
    {
        $id = Uuid::uuid4()->toString();
        (new RegistrationQuestion($connection))
            ->insert([
                ['id' => $id, 'bot_id' => $botId->value(), 'profile_record_type' => (new About())->value()]
            ]);

        return new ById(new RegistrationQuestionIdFromString($id), $connection);
    }

    private function seedBot(BotId $botId, array $availablePositions, OpenConnection $connection)
    {
        (new Bot($connection))
            ->insert([
                [
                    'id' => $botId->value(),
                    'available_positions' => $availablePositions,
                    'available_experiences' => [(new LessThanAYear())->value(), (new BetweenAYearAndThree())->value(), (new BetweenThreeYearsAndSix())->value(), (new GreaterThanSix())->value(), ]
                ]
            ]);
    }

    private function botId(): BotId
    {
        return new FromUuid(new FromString('5bf56c96-859d-4f34-ae18-8c33ba8226f7'));
    }

    private function availablePositionIds()
    {
        return [
            (new ProductAnalyst())->value(),
            (new ProductDesigner())->value(),
            (new SystemOrBusinessAnalyst())->value(),
            (new ProductManager())->value(),
            (new ProjectManager())->value()
        ];
    }
}