<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\ImpureInteractions\ImpureValue;

use PHPUnit\Framework\TestCase;
use RC\Infrastructure\ImpureInteractions\Error\SilentDeclineWithDefaultUserMessage;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Combined;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Failed;
use RC\Infrastructure\ImpureInteractions\ImpureValue\Successful;
use RC\Infrastructure\ImpureInteractions\PureValue\Present;

class CombinedTest extends TestCase
{
    public function testSomeImpureValuesAreNonSuccessful()
    {
        $this->assertEquals(
            'ooooops;\nI did it again',
            (new Combined(
                new Combined(
                    new Combined(
                        new Successful(new Present(2)),
                        new Failed(new SilentDeclineWithDefaultUserMessage('ooooops', []))
                    ),
                    new Successful(new Present('hello'))
                ),
                new Failed(new SilentDeclineWithDefaultUserMessage('I did it again', []))
            ))
                ->error()->logMessage()
        );
    }

    public function testAllImpureValuesAreSuccessful()
    {
        $this->assertTrue(
            (new Combined(
                new Combined(
                    new Combined(
                        new Successful(new Present(2)),
                        new Successful(new Present(3)),
                    ),
                    new Successful(new Present('hello'))
                ),
                new Successful(new Present('vasily'))
            ))
                ->isSuccessful()
        );
    }
}