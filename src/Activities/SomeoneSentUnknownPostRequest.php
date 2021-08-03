<?php

declare(strict_types=1);

namespace RC\Activities;

use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\UserStory\Body\Emptie;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\RetryableServerError;
use RC\Infrastructure\UserStory\Response\Successful;

class SomeoneSentUnknownPostRequest extends Existent
{
    private $body;
    private $logs;

    public function __construct(string $body, Logs $logs)
    {
        $this->body = $body;
        $this->logs = $logs;
    }

    public function response(): Response
    {
        if ($this->body === '') {
            $this->logs->receive(new InformationMessage('Someone sent unknown POST request with empty body'));
            return new RetryableServerError(new Emptie());
        }

        $this->logs->receive(new InformationMessage(sprintf('Someone sent unknown POST request with body %s', $this->body)));
        return new Successful(new Emptie());
    }
}