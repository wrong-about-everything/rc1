<?php

declare(strict_types=1);

namespace RC\Domain\RegistrationQuestion;

use RC\Domain\RegistrationQuestion\RegistrationQuestionId\Pure\RegistrationQuestionId;
use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Emptie;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;

class ById implements RegistrationQuestion
{
    private $id;
    private $connection;

    private $cached;

    public function __construct(RegistrationQuestionId $id, OpenConnection $connection)
    {
        $this->id = $id;
        $this->connection = $connection;

        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    private function doValue(): ImpureValue
    {
        $registrationQuestion =
            (new Selecting(
                'select rq.* from registration_question rq where rq.id = ?',
                [$this->id->value()],
                $this->connection
            ))
                ->response();
        if (!$registrationQuestion->isSuccessful()) {
            return $registrationQuestion;
        }
        if (!isset($registrationQuestion->pure()->raw()[0])) {
            return new Successful(new Emptie());
        }

        return new Successful(new Present($registrationQuestion->pure()->raw()[0]));
    }
}