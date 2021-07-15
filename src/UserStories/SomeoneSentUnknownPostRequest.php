<?php

declare(strict_types=1);

namespace RC\UserStories;

use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\UserStory\Body\Emptie;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\Successful;

/**
 * This is a catch-all class from telegram bot user.
 * @todo:
 *  1. Output a message with info about whether a user is registered or not
 *  2. Show all available commands
 *  3. In case of some unexpected query, give a link to a support bot/chat/etc.
 */
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
        $this->logs->receive(new InformationMessage(sprintf('Someone sent unknown POST request with body %s', $this->body)));

        return new Successful(new Emptie());
    }
}