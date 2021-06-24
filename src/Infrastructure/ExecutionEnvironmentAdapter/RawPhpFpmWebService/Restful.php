<?php

declare(strict_types=1);

namespace RC\Infrastructure\ExecutionEnvironmentAdapter\RawPhpFpmWebService;

use RC\Infrastructure\ExecutionEnvironmentAdapter\RawPhpFpmWebService;
use RC\Infrastructure\UserStory\Header;
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
        http_response_code(
            (new HttpCodeFromUserStoryCode(
                $this->userStory->response()->code()
            ))
                ->value()
        );

        array_map(
            function (Header $userStoryHeader) {
                $httpHeader = new HttpHeaderFromUserStoryHeader($userStoryHeader);
                if ($httpHeader->exists()) {
                    header($httpHeader->value());
                }
            },
            $this->userStory->response()->headers()
        );

        echo json_encode($this->userStory->response()->body());

        die();
    }
}