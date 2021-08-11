<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\TelegramBot\MessageToUser;

use PHPUnit\Framework\TestCase;
use RC\Infrastructure\TelegramBot\MessageToUser\MarkdownV2;

class MarkdownV2Test extends TestCase
{
    public function testNecessarySymbolsAreEscaped()
    {
        $this->assertEquals(
            'ПРивет, ребята\. \\\\\. Hiow are you doning? Everything fine? \#%$^, it\'s amazing to be here\!',
            (new MarkdownV2('ПРивет, ребята. \. Hiow are you doning? Everything fine? #%$^, it\'s amazing to be here!'))->value()
        );
    }
}