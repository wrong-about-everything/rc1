<?php

declare(strict_types=1);

namespace RC\Domain\UserStory;

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
        parse_str(
            (new FromUrl($this->request->url()))->isSpecified() ? (new FromUrl($this->request->url()))->value() : '',
            $parsedQuery
        );
        // @todo: test! Why 200 when Unauthorized??
        if (!isset($parsedQuery['secret_smile']) || $parsedQuery['secret_smile'] != getenv('SECRET_SMILE')) {
            return new Unauthorized();
        }

        return $this->original->response();
    }
}