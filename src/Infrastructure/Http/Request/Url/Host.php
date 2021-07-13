<?php

declare(strict_types = 1);

namespace RC\Infrastructure\Http\Request\Url;

/**
 * It is not necessarily an IP host, or a domain name. It could be a host name within a local network , like `localhost`.
 * Hence name constraints are pretty loose.
 */
interface Host
{
    /**
     * Host string with a trailing slash.
     */
    public function value(): string;
}
