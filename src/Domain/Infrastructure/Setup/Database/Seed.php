<?php

declare(strict_types=1);

namespace RC\Domain\Infrastructure\Setup\Database;

use Ramsey\Uuid\Uuid;
use RC\Domain\Experience\ExperienceId\Pure\BetweenAYearAndThree;
use RC\Domain\Experience\ExperienceId\Pure\BetweenThreeYearsAndSix;
use RC\Domain\Experience\ExperienceId\Pure\GreaterThanSix;
use RC\Domain\Experience\ExperienceId\Pure\LessThanAYear;
use RC\Domain\Position\PositionId\Pure\Analyst;
use RC\Domain\Position\PositionId\Pure\ProductDesigner;
use RC\Domain\Position\PositionId\Pure\ProductManager;
use RC\Domain\UserProfileRecordType\Pure\About;
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
                        [
                            Uuid::uuid4()->toString(),
                            (new Position())->value(),
                            '1f6d0fd5-3179-47fb-b92d-f6bec4e8f016',
                            1,
                            'Привет, это рэндом кофе. На самом деле он не рэндом, потому что мы не втупую пару подбираем, а по определённым параметрам, так чтоб вам было интересно и полезно. Полное описание я ещё не придумал. Ответьте на три вопроса. Итак, кем вы работаете?'
                        ],
                        $this->connection
                    ),
                    new SingleMutating(
                        'insert into registration_question (id, profile_record_type, bot_id, ordinal_number, text) values (?, ?, ?, ?, ?)',
                        [Uuid::uuid4()->toString(), (new Experience())->value(), '1f6d0fd5-3179-47fb-b92d-f6bec4e8f016', 2, 'Какой у вас опыт работы на этой должности?'],
                        $this->connection
                    ),
                    new SingleMutating(
                        'insert into registration_question (id, profile_record_type, bot_id, ordinal_number, text) values (?, ?, ?, ?, ?)',
                        [Uuid::uuid4()->toString(), (new About())->value(), '1f6d0fd5-3179-47fb-b92d-f6bec4e8f016', 3, 'Можете написать пару слов о себе для вашего собеседника. Где вы работаете, чем увлекаетесь, в каких областях вы можете поделиться своим опытом, а в каких хотели бы прокачаться лучше.'],
                        $this->connection
                    ),
                ],
                $this->connection
            ))
                ->response();
    }
}