<?php

declare(strict_types=1);

require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';

set_error_handler(
    function ($errno, $errstr, $errfile, $errline, array $errcontex) {
        throw new Exception($errstr, 0);
    },
    E_ALL
);

use Dotenv\Dotenv as OneAndOnly;
use Ramsey\Uuid\Uuid;
use RC\Domain\Infrastructure\Setup\Database\Seed;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\ApplicationConnection;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\Credentials\ApplicationCredentials;
use RC\Domain\Infrastructure\SqlDatabase\Agnostic\Connection\Credentials\RootCredentials;
use RC\Domain\RoundInvitation\Status\Pure\_New;
use RC\Domain\RoundRegistrationQuestion\Type\Pure\NetworkingOrSomeSpecificArea;
use RC\Domain\RoundRegistrationQuestion\Type\Pure\SpecificAreaChoosing;
use RC\Domain\UserProfileRecordType\Pure\About;
use RC\Domain\UserProfileRecordType\Pure\Experience;
use RC\Domain\UserProfileRecordType\Pure\Position;
use RC\Infrastructure\Filesystem\DirPath\ExistentFromAbsolutePathString as DirPath;
use RC\Infrastructure\Filesystem\FilePath\ExistentFromAbsolutePathString as FilePath;
use RC\Infrastructure\Setup\Database\Recreate;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\Port\FromString;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\DatabaseName\SpecifiedDatabaseName;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\Host\FromString as Host;
use RC\Infrastructure\SqlDatabase\Agnostic\Connection\Credentials\DefaultCredentials;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\Selecting;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutating;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\SingleMutatingQueryWithMultipleValueSets;
use RC\Infrastructure\SqlDatabase\Agnostic\Query\TransactionalQueryFromMultipleQueries;

OneAndOnly::createUnsafeImmutable(dirname(dirname(__FILE__)), './deploy/.env.prod')->load();

if (PHP_SAPI !== 'cli') {
    exit;
}

$x = rand(10, 50);
$y = rand(10, 50);
echo "Do you want to create new round? $x + $y = ";
$handle = fopen('php://stdin','r');
$answer = fgets($handle);
if(trim($answer) != ($x + $y)){
    echo "Wrong!\n";
    exit;
}

$options = getopt('', ['bot_id:', 'start_date_time:']);
$connection = new ApplicationConnection();


$botUsers =
    (new Selecting(
        'select user_id from bot_user where bot_id = ?',
        [$options['bot_id']],
        $connection
    ))
        ->response();
if (!$botUsers->isSuccessful()) {
    var_dump($botUsers->error()->logMessage());
    var_dump($botUsers->error()->context());
    die();
}

$meetingRoundId = Uuid::uuid4()->toString();
$response =
    (new TransactionalQueryFromMultipleQueries(
        [
            new SingleMutating(
                <<<q
            insert into meeting_round (id, bot_id, name, start_date, timezone, available_interests)
            values (?, ?, 'Новый раунд', ?, 'Europe/Moscow', '[0, 1]')
        q
                ,
                [$meetingRoundId, $options['bot_id'], $options['start_date_time']],
                $connection
            ),
            new SingleMutatingQueryWithMultipleValueSets(
                <<<q
    insert into meeting_round_invitation (id, meeting_round_id, user_id, status)
    values (?, ?, ?, ?)
    q
                ,
                array_map(
                    function (array $botUserRow) use ($meetingRoundId) {
                        return [Uuid::uuid4()->toString(), $meetingRoundId, $botUserRow['user_id'], (new _New())->value()];
                    },
                    $botUsers->pure()->raw()
                ),
                $connection
            ),
            new SingleMutating(
                'insert into meeting_round_registration_question (id, meeting_round_id, type, ordinal_number, text) values (?, ?, ?, ?, ?)',
                [
                    Uuid::uuid4()->toString(),
                    $meetingRoundId,
                    (new NetworkingOrSomeSpecificArea())->value(),
                    1,
                    'Чего вы ожидаете от встречи?'
                ],
                $connection
            ),
            new SingleMutating(
                'insert into meeting_round_registration_question (id, meeting_round_id, type, ordinal_number, text) values (?, ?, ?, ?, ?)',
                [
                    Uuid::uuid4()->toString(),
                    $meetingRoundId,
                    (new SpecificAreaChoosing())->value(),
                    2,
                    'Напишите тему, которую хотели бы обсудить. Например, "внедрение продуктовой культуры" или "мотивация сотрудников". Постарайтесь не задавать слишком узкую тему — так больше вероятность, что мы подберем вам классного собеседника.'
                ],
                $connection
            )
        ],
        $connection
    ))
        ->response();
if (!$response->isSuccessful()) {
    var_dump($response->error()->logMessage());
    var_dump($response->error()->context());
    die();
}

die('Success!');