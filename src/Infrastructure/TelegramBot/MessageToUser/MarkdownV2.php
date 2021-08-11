<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\MessageToUser;

class MarkdownV2 implements MessageToUser
{
    private $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function value(): string
    {
        $split = preg_split('//u', $this->message, -1, PREG_SPLIT_NO_EMPTY);
        if (is_null($split)) {
            return $this->message;
        }

        return
            array_reduce(
                $split,
                function (string $carry, string $char) {
                    return
                        $carry
                            .
                        (
                            (in_array($char, ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!', '\\']))
                                ? sprintf('\%s', $char)
                                : $char
                        );
                },
                ''
            );
    }
}