<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use RC\Domain\UserStory\Body\FallbackResponseBody;
use RC\Infrastructure\Dotenv\EnvironmentDependentEnvFile;
use RC\Infrastructure\ExecutionEnvironmentAdapter\YandexServerless;
use RC\Infrastructure\Http\Request\Inbound\FromYandexServerlessEnvironmentRequest;
use RC\Infrastructure\Http\Request\Method\Get;
use RC\Infrastructure\Http\Request\Url\Query;
use RC\Infrastructure\Logging\LogId;
use RC\Infrastructure\Logging\Logs\StdOut;
use RC\Infrastructure\Routing\Route\RouteByMethodAndPathPattern;
use RC\Infrastructure\UserStory\ByRoute;
use RC\Infrastructure\Uuid\RandomUUID;
use RC\UserStories\Sample;

(new EnvironmentDependentEnvFile())->load();

function handler(array $message, $context) {
    return
        (new YandexServerless(
            new ByRoute(
                [
                    [
                        new RouteByMethodAndPathPattern(
                            new Get(),
                            '/hello/:id/world/:name'
                        ),
                        function (string $id, string $name, Query $query) {
                            return new Sample($id, $name, $query);
                        }
                    ],
                ],
                new FromYandexServerlessEnvironmentRequest($message)
            ),
            new FallbackResponseBody(),
            new StdOut(new LogId(new RandomUUID()))
        ))
            ->response();
}

// @info: make class that converts ordinary http request into yandex-serverless-specific message
var_dump(
    handler(
        json_decode(
            '{
  "httpMethod": "GET",
  "headers": {
    "Accept": "*/*",
    "Content-Length": "13",
    "Content-Type": "application/x-www-form-urlencoded",
    "User-Agent": "curl/7.58.0",
    "X-Real-Remote-Address": "[88.99.0.24]:37310",
    "X-Request-Id": "cd0d12cd-c5f1-4348-9dff-c50a78f1eb79",
    "X-Trace-Id": "92c5ad34-54f7-41df-a368-d4361bf376eb"
  },
  "path": "",
  "multiValueHeaders": {
    "Accept": [ "*/*" ],
    "Content-Length": [ "13" ],
    "Content-Type": [ "application/x-www-form-urlencoded" ],
    "User-Agent": [ "curl/7.58.0" ],
    "X-Real-Remote-Address": [ "[88.99.0.24]:37310" ],
    "X-Request-Id": [ "cd0d12cd-c5f1-4348-9dff-c50a78f1eb79" ],
    "X-Trace-Id": [ "92c5ad34-54f7-41df-a368-d4361bf376eb" ]
  },
  "queryStringParameters": {
    "ad_hoc_path": "/hello/vasya/world/earth",
    "a": "2",
    "b": "1"
  },
  "multiValueQueryStringParameters": {
    "a": [ "1", "2" ],
    "b": [ "1" ]
  },
  "requestContext": {
    "identity": {
      "sourceIp": "88.99.0.24",
      "userAgent": "curl/7.58.0"
    },
    "httpMethod": "POST",
    "requestId": "cd0d12cd-c5f1-4348-9dff-c50a78f1eb79",
    "requestTime": "26/Dec/2019:14:22:07 +0000",
    "requestTimeEpoch": 1577370127
  },
  "body": "aGVsbG8sIHdvcmxkIQ==",
  "isBase64Encoded": true
}',
            true
        ),
        []
    )
);
