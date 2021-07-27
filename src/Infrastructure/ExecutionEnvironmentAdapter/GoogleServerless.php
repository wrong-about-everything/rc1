<?php

declare(strict_types=1);

namespace RC\Infrastructure\ExecutionEnvironmentAdapter;

use Exception;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RC\Infrastructure\Logging\LogItem\FromInboundPsrHttpServerRequest;
use RC\Infrastructure\Logging\LogItem\FromOutboundHttpResponse;
use RC\Infrastructure\Logging\LogItem\FromThrowable;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\UserStory\LazySafetyNet;
use RC\Infrastructure\UserStory\Response as UserStoryResponse;
use RC\Infrastructure\UserStory\Response\RestfulHttp\FromUserStoryResponse;
use RC\Infrastructure\UserStory\UserStory;
use Throwable;

class GoogleServerless
{
    private $userStory;
    private $inboundRequest;
    private $logs;

    public function __construct(UserStory $userStory, ServerRequestInterface $inboundRequest, UserStoryResponse $fallbackResponse, Logs $logs)
    {
        set_error_handler(
            function ($errno, $errstr, $errfile, $errline, array $errcontex) {
                throw new Exception($errstr, 0);
            },
            E_ALL
        );
        set_exception_handler(
            function (Throwable $throwable) use ($logs) {
                $logs->receive(new FromThrowable($throwable));
                $logs->flush();
                throw $throwable;
            }
        );

        $this->userStory = new LazySafetyNet($userStory, $fallbackResponse, $logs);
        $this->inboundRequest = $inboundRequest;
        $this->logs = $logs;
    }

    public function response(): ResponseInterface
    {
        $this->logs->receive(new FromInboundPsrHttpServerRequest($this->inboundRequest));
        $httpResponse = new FromUserStoryResponse($this->userStory->response());
        $this->logs->receive(new FromOutboundHttpResponse($httpResponse));
        $this->logs->flush();
        // @todo: Fix Header class: add name() method
        return new Response($httpResponse->code()->value(), [], $httpResponse->body());
    }
}