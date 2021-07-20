<?php

declare(strict_types=1);

namespace RC\Domain\Infrastructure\Setup\Database;

use Ramsey\Uuid\Uuid;
use RC\Domain\Experience\Pure\BetweenAYearAndThree;
use RC\Domain\Experience\Pure\BetweenThreeYearsAndSix;
use RC\Domain\Experience\Pure\GreaterThanSix;
use RC\Domain\Experience\Pure\LessThanAYear;
use RC\Domain\Position\Pure\Analyst;
use RC\Domain\Position\Pure\ProductDesigner;
use RC\Domain\Position\Pure\ProductManager;
use RC\Domain\UserProfileRecordType\Pure\Experience;
use RC\Domain\UserProfileRecordType\Pure\Position;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\TransactionalQueryFromMultipleQueries;
use RC\Infrastructure\Uuid\RandomUUID;

class Seed
{
    private $connection;

    public function __construct(OpenConnection $connection)
    {
        $this->connection = $connection;
    }

    public function value(): ImpureValue
    {
        $addGorgonzolaBot = $this->addGorgonzolaBot();
        if (!$addGorgonzolaBot->isSuccessful()) {
            return $addGorgonzolaBot;
        }

        $addAnalysisParadysisGroup = $this->addAnalysisParadysisGroup();
        if (!$addAnalysisParadysisGroup->isSuccessful()) {
            return $addAnalysisParadysisGroup;
        }

        $addQuestions = $this->addQuestions();
        if (!$addQuestions->isSuccessful()) {
            return $addQuestions;
        }

        return new Successful(new Emptie());
    }

    private function addGorgonzolaBot()
    {
        return
            (new SingleMutating(
                'insert into bot values (?, ?, \'false\', ?, ?, ?)',
                [
                    '1f6d0fd5-3179-47fb-b92d-f6bec4e8f016',
                    '1884532101:AAGlJklZYP5j72nC2UcvB0IbD05i70kQqWc',
                    '@gorgonzola_sandwich_bot',
                    json_encode([(new ProductManager())->value(), (new ProductDesigner())->value(), (new Analyst())->value(), ]),
                    json_encode([(new LessThanAYear())->value(), (new BetweenAYearAndThree())->value(), (new BetweenThreeYearsAndSix())->value(), (new GreaterThanSix())->value(), ])
                ],
                $this->connection
            ))
                ->response();
    }

    private function addAnalysisParadysisGroup()
    {
        return
            (new SingleMutating(
                'insert into "group" values (?, ?, ?)',
                [Uuid::uuid4()->toString(), '1f6d0fd5-3179-47fb-b92d-f6bec4e8f016', 'Analysis Paradysis'],
                $this->connection
            ))
                ->response();
    }

    private function addQuestions()
    {
        return
            (new TransactionalQueryFromMultipleQueries(
                [
                    new SingleMutating(
                        'insert into registration_question (id, profile_record_type, bot_id, ordinal_number, text) values (?, ?, ?, ?, ?)',
                        [Uuid::uuid4()->toString(), (new Position())->value(), '1f6d0fd5-3179-47fb-b92d-f6bec4e8f016', 1, 'Кем вы работаете?'],
                        $this->connection
                    ),
                    new SingleMutating(
                        'insert into registration_question (id, profile_record_type, bot_id, ordinal_number, text) values (?, ?, ?, ?, ?)',
                        [Uuid::uuid4()->toString(), (new Experience())->value(), '1f6d0fd5-3179-47fb-b92d-f6bec4e8f016', 1, 'Какой у вас опыт?'],
                        $this->connection
                    ),
                ],
                $this->connection
            ))
                ->response();
    }
}