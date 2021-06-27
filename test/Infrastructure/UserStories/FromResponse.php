<?php

declare(strict_types=1);

namespace RC\Tests\Infrastructure\UserStories;

use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;

class FromResponse extends Existent
{
    private $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function response(): Response
    {
        return $this->response;
    }
}