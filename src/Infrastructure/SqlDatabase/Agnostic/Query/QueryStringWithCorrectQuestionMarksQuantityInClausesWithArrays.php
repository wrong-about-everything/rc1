<?php

declare(strict_types=1);

namespace RC\Infrastructure\SqlDatabase\Agnostic\Query;

class QueryStringWithCorrectQuestionMarksQuantityInClausesWithArrays
{
    private $query;
    private $values;

    public function __construct(string $query, array $values)
    {
        $this->query = $query;
        $this->values = $values;
    }

    public function value()
    {
        if (strpos($this->query, '?') === false) {
            return $this->query;
        }

        return
            $this->implode(
                array_map(
                    function ($value) {
                        return
                            is_array($value)
                                ? implode(', ', array_fill(0, count($value), '?'))
                                : '?'
                            ;
                    },
                    $this->values
                ),
                explode('?', $this->query)
            );

    }

    private function implode(array $glueSymbols, array $pieces)
    {
        if (empty($pieces)) {
            return '';
        }

        $currentGlue = array_shift($glueSymbols);
        $currentPiece = array_shift($pieces);

        return $currentPiece . $currentGlue . $this->implode($glueSymbols, $pieces);
    }
}