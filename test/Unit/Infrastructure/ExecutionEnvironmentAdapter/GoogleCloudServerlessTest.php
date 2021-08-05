<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\ExecutionEnvironmentAdapter;

use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use RC\Domain\UserStory\Body\TelegramFallbackResponseBody;
use RC\Infrastructure\ExecutionEnvironmentAdapter\GoogleServerless;
use RC\Infrastructure\Logging\Logs\DevNull;
use RC\Infrastructure\UserStory\Body\Arrray;
use RC\Infrastructure\UserStory\Response\NonRetryableServerError;
use RC\Infrastructure\UserStory\Response\Successful;
use RC\Tests\Infrastructure\UserStories\FromResponse;
use RC\Tests\Infrastructure\UserStories\ThrowingException;

class GoogleCloudServerlessTest extends TestCase
{
    public function testWhenUserStoryIsSuccessfulThenCode200IsReturned()
    {
        $response =
            (new GoogleServerless(
                new FromResponse(new Successful(new Arrray(['hello']))),
                new ServerRequest('get', 'vasya'),
                new Successful(new TelegramFallbackResponseBody()),
                new DevNull()
            ))
                ->response();

        $this->assertEquals(
            200,
            $response->getStatusCode()
        );
        $this->assertEquals(
            json_encode(['hello']),
            $response->getBody()->getContents()
        );
    }

    public function testWhenUserStoryHasServerErrorThenCode500IsReturned()
    {
        $response =
            (new GoogleServerless(
                new FromResponse(new NonRetryableServerError(new Arrray(['jopa']))),
                new ServerRequest('get', 'vasya'),
                new Successful(new TelegramFallbackResponseBody()),
                new DevNull()
            ))
                ->response();

        $this->assertEquals(
            500,
            $response->getStatusCode()
        );
        $this->assertEquals(
            json_encode(['jopa']),
            $response->getBody()->getContents()
        );
    }

    public function testWhenUserStoryThrowsExceptionThenFallbackResponseIsReturned()
    {
        $response =
            (new GoogleServerless(
                new ThrowingException(),
                new ServerRequest('get', 'vasya'),
                new Successful(new TelegramFallbackResponseBody()),
                new DevNull()
            ))
                ->response();

        $this->assertEquals(
            200,
            $response->getStatusCode()
        );
        $this->assertEquals(
            json_encode('Простите, у нас что-то сломалось. Скорее всего, мы об этом уже знаем, но на всякий случай, напишите пожалуйста об этом в @gorgonzola_support_bot.'),
            $response->getBody()->getContents()
        );
    }
}