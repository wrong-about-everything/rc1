<?php

declare(strict_types=1);

namespace RC\Infrastructure\ExecutionEnvironmentAdapter;

use Exception;
use RC\Infrastructure\Logging\LogItem\FromThrowable;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\UserStory\Body;
use RC\Infrastructure\UserStory\LazySafetyNet;
use RC\Infrastructure\UserStory\Response\RestfulHttp\FromUserStoryResponse;
use RC\Infrastructure\UserStory\UserStory;
use Throwable;

class YandexServerless
{
    private $userStory;

    public function __construct(UserStory $userStory, Body $fallbackResponseBody, Logs $logs)
    {
        set_error_handler(
            function ($errno, $errstr, $errfile, $errline, array $errcontex) {
                throw new Exception($errstr, 0);
            },
            E_ALL
        );
        set_exception_handler(
            function (Throwable $throwable) use ($logs) {
                $logs->add(new FromThrowable($throwable));
                throw $throwable;
            }
        );

        $this->userStory = new LazySafetyNet($userStory, $fallbackResponseBody, $logs);
    }

    public function response(): array
    {
        $httpResponse = new FromUserStoryResponse($this->userStory->response());
        return [
            'statusCode' => $httpResponse->code()->value(),
            'body' => $httpResponse->body(),
        ];
    }
}