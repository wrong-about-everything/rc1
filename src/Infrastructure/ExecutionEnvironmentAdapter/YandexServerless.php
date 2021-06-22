<?php

declare(strict_types=1);

namespace RC\Infrastructure\ExecutionEnvironmentAdapter;

use RC\Infrastructure\UserStory\UserStory;

class YandexServerless
{
    private $userStory;

    public function __construct(UserStory $userStory)
    {
        $this->userStory = $userStory;
    }

    public function response(): array
    {
        return [
            'statusCode' => $this->userStory->response()->code()->value(),
            'body' => $this->userStory->response()->body(),
        ];
    }
}