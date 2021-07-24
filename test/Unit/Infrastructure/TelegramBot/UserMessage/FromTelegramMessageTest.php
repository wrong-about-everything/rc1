<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\TelegramBot\UserMessage;

use PHPUnit\Framework\TestCase;
use RC\Infrastructure\TelegramBot\UserMessage\Pure\FromTelegramMessage;

class FromTelegramMessageTest extends TestCase
{
    public function testWithEmptyBody()
    {
        $this->assertFalse((new FromTelegramMessage(''))->exists());
    }
}