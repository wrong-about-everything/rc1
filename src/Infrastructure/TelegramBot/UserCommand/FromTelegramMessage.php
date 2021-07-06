<?php

declare(strict_types=1);

namespace RC\Infrastructure\TelegramBot\UserCommand;

class FromTelegramMessage extends UserCommand
{
    private $concrete;

    public function __construct(string $telegramMessage, AvailableTelegramBotCommands $availableCommands)
    {
        $this->concrete = $this->concrete($telegramMessage, $availableCommands);
    }

    public function value(): string
    {
        return $this->concrete->value();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }

    private function concrete(string $telegramMessage, AvailableTelegramBotCommands $availableCommands): UserCommand
    {
        return
            (json_decode($telegramMessage, true)['message']['entities'][0]['type'] ?? '') === 'bot_command'
                ? new FromString(json_decode($telegramMessage, true)['message']['text'], $availableCommands)
                : new NonExistent();
    }
}