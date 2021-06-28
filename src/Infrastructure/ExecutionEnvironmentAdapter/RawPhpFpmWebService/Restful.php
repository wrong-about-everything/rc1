<?php

declare(strict_types=1);

namespace RC\Infrastructure\ExecutionEnvironmentAdapter\RawPhpFpmWebService;

use RC\Infrastructure\ExecutionEnvironmentAdapter\RawPhpFpmWebService;
use RC\Infrastructure\Http\Response\Header;
use RC\Infrastructure\UserStory\Response\RestfulHttp\FromUserStoryResponse;
use RC\Infrastructure\UserStory\UserStory;

class Restful implements RawPhpFpmWebService
{
    private $userStory;

    public function __construct(UserStory $userStory)
    {
        $this->userStory = $userStory;
    }

    public function response(): void
    {
        $restfulHttpResponse = new FromUserStoryResponse($this->userStory->response());

        http_response_code(
            $restfulHttpResponse->code()->value()
        );

        array_map(
            function (Header $httpHeader) {
                if ($httpHeader->exists()) {
                    header($httpHeader->value());
                }
            },
            $restfulHttpResponse->headers()
        );

        echo json_encode($restfulHttpResponse->body());

        die();
    }
}