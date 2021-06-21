<?php

declare(strict_types=1);

namespace RC\Infrastructure\ExecutionEnvironmentAdapter;

class YandexServerless
{
    private $event;

    public function __construct(array $event, $context)
    {
        $this->event = $event;
    }

    public function response(): array
    {
        return [
            'statusCode' => 200,
            'body' => 'Hello, World!',
        ];
    }
}