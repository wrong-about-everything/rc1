<?php

declare(strict_types=1);

namespace RC\Infrastructure\Http\Transport;

use GuzzleHttp\Client;
use RC\Infrastructure\Filesystem\DirPath;
use RC\Infrastructure\Filesystem\Filename\PortableFromString;
use RC\Infrastructure\Filesystem\FilePath\FromDirAndFileName;
use RC\Infrastructure\Http\Request\Outbound\Request as OutboundRequest;
use RC\Infrastructure\Http\Response\Inbound\Response;
use RC\Infrastructure\Http\Transport\Guzzle\DefaultGuzzle;
use RC\Infrastructure\Http\Transport\Guzzle\WithConnectTimeout;
use RC\Infrastructure\Http\Transport\Guzzle\WithTimeout;
use RC\Infrastructure\Logging\Logs;

class EnvironmentDependentTransport implements HttpTransport
{
    private $transport;

    public function __construct(DirPath $rootDir, Logs $logs)
    {
        $this->transport = $this->concrete($rootDir, $logs);
    }

    public function response(OutboundRequest $request): Response
    {
        return $this->transport->response($request);
    }

    private function concrete(DirPath $root, Logs $logs): HttpTransport
    {
        if (
            (new FromDirAndFileName($root, new PortableFromString('.env.dev')))->exists()
        ) {
            return new Indifferent();
        }

        return
            new WithLogging(
                new WithConnectTimeout(
                    new WithTimeout(
                        new DefaultGuzzle(new Client()),
                        1
                    ),
                    1
                ),
                $logs
            );
    }
}
