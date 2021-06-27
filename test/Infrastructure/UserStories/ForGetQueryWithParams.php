<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\UserStories;

use RC\Infrastructure\Http\Request\Url\Query;
use RC\Infrastructure\UserStory\Body\Json;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\Successful;

class ForGetQueryWithParams extends Existent
{
    private $id;
    private $name;
    private $query;

    public function __construct(string $id, string $name, Query $query)
    {
        $this->id = $id;
        $this->name = $name;
        $this->query = $query;
    }

    public function response(): Response
    {
        return new Successful(new Json(['id' => $this->id, 'name' => $this->name]));
    }
}