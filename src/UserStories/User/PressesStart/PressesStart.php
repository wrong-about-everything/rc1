<?php

declare(strict_types=1);

namespace RC\UserStories\User\PressesStart;

use RC\Infrastructure\UserStory\Body\ReplyToTelegramUser;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\Successful;

class PressesStart extends Existent
{
    private $message;

    public function __construct(array $message)
    {
        $this->message = $message;
    }

    public function response(): Response
    {
        return
            new Successful(
                new ReplyToTelegramUser($this->message['message']['chat']['id'], '❤️')
            );

    }
}