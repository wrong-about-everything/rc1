<?php

declare(strict_types=1);

namespace RC\Activities\Cron\PromptsToFillAboutMeSection;

use Meringue\Timeline\Point\Now;
use RC\Domain\Bot\BotId\BotId;
use RC\Domain\Bot\BotToken\Impure\ByBotId;
use RC\Domain\BotUser\UserStatus\Pure\RegistrationIsInProgress;
use RC\Domain\BotUser\WriteModel\PromptedToFillAboutMeSection;
use RC\Domain\MeetingRound\MeetingRoundId\Impure\FromMeetingRound;
use RC\Domain\MeetingRound\ReadModel\LatestAlreadyStarted;
use RC\Domain\MeetingRound\ReadModel\MeetingRound;
use RC\Domain\Participant\ParticipantId\Pure\FromString;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\Logging\LogItem\ErrorMessage;
use RC\Infrastructure\Logging\LogItem\FromNonSuccessfulImpureValue;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Infrastructure\TelegramBot\UserId\Pure\FromInteger;
use RC\Infrastructure\UserStory\Body\Emptie;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\Successful;

// @todo: добавить в крон
class PromptsToFillAboutMeSection extends Existent
{
    private $botId;
    private $transport;
    private $connection;
    private $logs;

    public function __construct(BotId $botId, HttpTransport $transport, OpenConnection $connection, Logs $logs)
    {
        $this->botId = $botId;
        $this->transport = $transport;
        $this->connection = $connection;
        $this->logs = $logs;
    }

    public function response(): Response
    {
        $this->logs->receive(new InformationMessage('Cron prompts to fill about me section scenario started'));

        array_map(
            function (array $dropout) {
                $participantValue =
                    (new PromptedToFillAboutMeSection(
                        new FromInteger($dropout['telegram_id']),
                        $this->botId,
                        $this->transport,
                        $this->connection
                    ))
                        ->value();
                if (!$participantValue->isSuccessful()) {
                    $this->logs->receive(new FromNonSuccessfulImpureValue($participantValue));
                }
            },
            $this->usersWithEmptyAboutMeSection()
        );

        $this->logs->receive(new InformationMessage('Cron prompts to fill about me section scenario finished'));

        return new Successful(new Emptie());
    }

    private function usersWithEmptyAboutMeSection()
    {
        return
            (new Selecting(
                <<<q
select tu.telegram_id    
from telegram_user tu join bot_user bu on tu.id = bu.user_id
where bu.status = ? and bu.position is not null and bu.experience is not null and bu.about is null
order by tu.telegram_id
q
                ,
                [(new RegistrationIsInProgress())->value()],
                $this->connection
            ))
                ->response()->pure()->raw();
    }
}