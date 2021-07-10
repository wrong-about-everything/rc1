<?php

declare(strict_types=1);

namespace RC\UserStories\User\PressesStart;

use RC\Domain\TelegramBot\Method\SendMessage;
use RC\Infrastructure\Http\Request\Method\Post;
use RC\Infrastructure\Http\Request\Outbound\OutboundRequest;
use RC\Infrastructure\Http\Request\Url\Query\FromArray;
use RC\Infrastructure\Http\Transport\HttpTransport;
use RC\Infrastructure\Logging\LogItem\InformationMessage;
use RC\Infrastructure\Logging\Logs;
use RC\Infrastructure\TelegramBot\BotApiUrl;
use RC\Infrastructure\UserStory\Body\Emptie;
use RC\Infrastructure\UserStory\Body\ReplyToTelegramUser;
use RC\Infrastructure\UserStory\Existent;
use RC\Infrastructure\UserStory\Response;
use RC\Infrastructure\UserStory\Response\Successful;

class PressesStart extends Existent
{
    private $message;
    private $httpTransport;
    private $logs;

    public function __construct(array $message, HttpTransport $httpTransport, Logs $logs)
    {
        $this->message = $message;
        $this->httpTransport = $httpTransport;
        $this->logs = $logs;
    }

    public function response(): Response
    {
        $this->logs->receive(new InformationMessage('User presses start scenario started'));

        $response =
            $this->httpTransport
                ->response(
                    new OutboundRequest(
                        new Post(),
                        new BotApiUrl(
                            new SendMessage(),
                            new FromArray([
                                'chat_id' => $this->message['message']['chat']['id'],
                                'text' => '❤️',
                            ])
                        ),
                        [],
                        ''
                    )
                );

        return new Successful(new Emptie());
    }
}