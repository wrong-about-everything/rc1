<?php

declare(strict_types=1);

namespace RC\Infrastructure\UserStory;

use RC\Infrastructure\Logging\LogItem\FromThrowable;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\UserStory\Response\NonRetryableServerError;
use Throwable;

class LazySafetyNet implements UserStory
{
    private $userStory;
    private $fallbackResponseBody;
    private $logs;
    private $response;

    public function __construct(UserStory $userStory, Body $fallbackResponseBody, Logs $logs)
    {
        $this->userStory = $userStory;
        $this->fallbackResponseBody = $fallbackResponseBody;
        $this->logs = $logs;
        $this->response = null;
    }

    public function response(): Response
    {
        if (is_null($this->response)) {
            $this->response = $this->doResponse();
        }

        return $this->response;
    }

    public function exists(): bool
    {
        return $this->userStory->exists();
    }

    private function doResponse(): Response
    {
        try {
            return $this->userStory->response();
        } catch (Throwable $t) {
            $this->logs->add(new FromThrowable($t));
            return new NonRetryableServerError($this->fallbackResponseBody);
        }
    }
}