<?php

declare(strict_types=1);

namespace RC\Domain\UserStory;

use RC\Domain\BotId\FromQuery;
use RC\Infrastructure\Http\Request\Inbound\Request;
use RC\Infrastructure\Http\Request\Url\Query\FromUrl;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\Unauthorized;
use RC\Infrastructure\UserStory\UserStory;

class Authorized extends Existent
{
    private $original;
    private $request;

    public function __construct(UserStory $original, Request $request)
    {
        $this->original = $original;
        $this->request = $request;
    }

    public function response(): Response
    {
        if (!(new FromQuery(new FromUrl($this->request->url())))->exists()) {
            return new Unauthorized();
        }

        return $this->original->response();
    }
}