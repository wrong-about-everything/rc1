<?php

declare(strict_types=1);

namespace RC\UserStories;

use RC\Infrastructure\Http\Request\Url\Query;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\UserStory\Body\Arrray;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\Successful;

class Sample extends Existent
{
    private $id;
    private $name;
    private $query;
    private $logs;

    public function __construct(string $id, string $name, Query $query, Logs $logs)
    {
        $this->id = $id;
        $this->name = $name;
        $this->query = $query;
        $this->logs = $logs;
    }

    public function response(): Response
    {
        $this->logs->receive(new InformationMessage('Sample scenario started'));

        $this->logs->receive(new InformationMessage('Sample scenario finished'));
        return
            new Successful(
                new Arrray([
                    'id' => $this->id,
                    'name' => $this->name,
                ])
            );
    }
}