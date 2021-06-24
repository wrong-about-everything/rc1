<?php

declare(strict_types=1);

namespace RC\Infrastructure\ExecutionEnvironmentAdapter\RawPhpFpmWebService;

use Exception;
use RC\Infrastructure\Http\Response\Code;
use RC\Infrastructure\Http\Response\Code\BadRequest;
use RC\Infrastructure\Http\Response\Code\Ok;
use RC\Infrastructure\Http\Response\Code\ServerError as HttpServerError;
use RC\Infrastructure\UserStory\Code as UserStoryCode;
use RC\Infrastructure\UserStory\Code\ClientRequestError;
use RC\Infrastructure\UserStory\Code\RetryableServerError;
use RC\Infrastructure\UserStory\Code\Successful;

class HttpCodeFromUserStoryCode extends Code
{
    private $userStoryCode;

    public function __construct(UserStoryCode $userStoryCode)
    {
        $this->userStoryCode = $userStoryCode;
    }

    public function value(): int
    {
        if ($this->userStoryCode->equals(new Successful())) {
            return (new Ok())->value();
        } elseif ($this->userStoryCode->equals(new RetryableServerError())) {
            return (new HttpServerError())->value();
        } elseif ($this->userStoryCode->equals(new ClientRequestError())) {
            return (new BadRequest())->value();
        }

        throw new Exception(sprintf('Unknown user story code given: %s', $this->userStoryCode->value()));
    }
}