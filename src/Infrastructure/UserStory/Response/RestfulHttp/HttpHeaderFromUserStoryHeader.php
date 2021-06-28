<?php

declare(strict_types=1);

namespace RC\Infrastructure\UserStory\Response\RestfulHttp;

use RC\Infrastructure\ExecutionEnvironmentAdapter\RawPhpFpmWebService\HttpResponseHeaderFromUserStoryResponseHeader;
use RC\Infrastructure\Http\Response\Header;
use RC\Infrastructure\UserStory\Header as UserStoryResponseHeader;

class HttpHeaderFromUserStoryHeader extends Header
{
    private $httpHeader;

    public function __construct(UserStoryResponseHeader $userStoryResponseHeader)
    {
        $this->httpHeader = new HttpResponseHeaderFromUserStoryResponseHeader($userStoryResponseHeader);
    }

    public function value(): string
    {
        return $this->httpHeader->value();
    }

    public function exists(): bool
    {
        return $this->httpHeader->exists();
    }
}