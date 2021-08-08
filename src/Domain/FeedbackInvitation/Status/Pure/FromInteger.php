<?php

declare(strict_types=1);

namespace RC\Domain\FeedbackInvitation\Status\Pure;

class FromInteger extends Status
{
    private $status;

    public function __construct(int $statusValue)
    {
        $this->status = $this->all()[$statusValue];
    }

    public function value(): int
    {
        return $this->status->value();
    }

    public function exists(): bool
    {
        return $this->status->exists();
    }

    private function all()
    {
        return [
            (new Generated())->value() => new Generated(),
            (new Sent())->value() => new Sent(),
            (new ErrorDuringSending())->value() => new ErrorDuringSending(),
            (new Accepted())->value() => new Accepted(),
            (new Declined())->value() => new Declined(),
        ];
    }
}