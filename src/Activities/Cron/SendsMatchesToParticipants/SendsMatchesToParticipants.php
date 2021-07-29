<?php

declare(strict_types=1);

namespace RC\Activities\Cron\SendsMatchesToParticipants;

use RC\Domain\Bot\BotId\BotId;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\SqlDatabase\Agnostic\OpenConnection;
use RC\Infrastructure\UserStory\Body\Emptie;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\Successful;

class SendsMatchesToParticipants extends Existent
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
        $this->logs->receive(new InformationMessage('Cron sends matches to participants scenario started'));

        if (false) {
            // get participants and their interests from db
            $participants2Interests = [
                1 => [1, 3],
                2 => [1, 2, 6],
            ];
            // generate matches
            $matches = [
                ['pair' => [1, 2], 'common_interests' => [3, 5]],
                ['pair' => [3, 4], 'common_interests' => [1]],
            ];
            new Matches($participants2Interests);
            // save in db
        }

        // send matches to participants
        array_map(
            function (array $invitation) {
                $toTelegramId = $invitation['to'];
                $matchTelegramId = $invitation['match'];
                $matchedInterests = $invitation['common_interests'];
                $text = "Привет, %s!\nВаш собеседник -- %s, его ник в телеге -- %s. У вас совпали такие интересы: %s. Вот что %s написал о себе: «%s».\n\nПриятного общения!";
            },
            []
        );

        $this->logs->receive(new InformationMessage('Cron sends matches to participants scenario finished'));

        return new Successful(new Emptie());
    }
}