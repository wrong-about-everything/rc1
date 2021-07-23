<?php

declare(strict_types=1);

namespace RC\Activities\Cron\InvitesToTakePartInANewRound\Invitation;

use RC\Infrastructure\ImpureInteractions\ImpureValue;
use RC\Infrastructure\Logging\LogItem\ErrorMessage;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\TelegramBot\UserId\Pure\TelegramUserId;

class Logged implements Invitation
{
    private $botName;
    private $telegramUserId;
    private $meetingRoundInvitation;
    private $logs;
    private $cached;

    public function __construct(string $botName, TelegramUserId $telegramUserId, Invitation $meetingRoundInvitation, Logs $logs)
    {
        $this->botName = $botName;
        $this->telegramUserId = $telegramUserId;
        $this->meetingRoundInvitation = $meetingRoundInvitation;
        $this->logs = $logs;
        $this->cached = null;
    }

    public function value(): ImpureValue
    {
        if (is_null($this->cached)) {
            $this->cached = $this->doValue();
        }

        return $this->cached;
    }

    private function doValue()
    {
        $this->logStart();
        $r = $this->meetingRoundInvitation->value();
        $this->logFinish($r);
        return $r;
    }

    private function logStart()
    {
        $this->logs
            ->receive(
                new InformationMessage(
                    sprintf(
                        'Invitation to attend a new %s round is being sent to %d',
                        $this->botName,
                        $this->telegramUserId->value()
                    )
                )
            );
    }

    private function logFinish(ImpureValue $impureValue)
    {
        if (!$impureValue->isSuccessful()) {
            $this->logs->receive(new ErrorMessage('Error during invitation sending!'));
        } else {
            $this->logs->receive(new InformationMessage('Invitation was sent successfully'));
        }
    }
}