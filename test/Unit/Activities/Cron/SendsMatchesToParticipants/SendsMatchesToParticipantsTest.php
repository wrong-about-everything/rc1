<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Activities\Cron\SendsMatchesToParticipants;

use PHPUnit\Framework\TestCase;
use RC\Activities\Cron\SendsMatchesToParticipants\SendsMatchesToParticipants;

class SendsMatchesToParticipantsTest extends TestCase
{
    public function test()
    {
        new SendsMatchesToParticipants(
            $botId, $transport, $connection, $logs
        );
    }
}