<?php

declare(strict_types=1);

namespace RC\Infrastructure\ExecutionEnvironmentAdapter\RawPhpFpmWebService;

use RC\Infrastructure\ExecutionEnvironmentAdapter\RawPhpFpmWebService;
use SoapServer as NativePhpSoapServer;

/**
 * @example
 *
 * # Server
 * $server = new SoapServer(null, array('uri' => 'http://test-uri/')); // if first parameter is null, request is assumed to be raw data from POST request
 *  // MyApplication class has all the route names as methods. It acts like an yc.php. Each user story returns an stdClass with appropriate fields.
 * $server->setClass('MyApplication');
 * $server->handle();
 *
 * # Client
 * $client =
 *      new SoapClient(
 *          null,
 *          [
 *              'location' => "http://localhost/soap/server.php",
 *              'uri' => "http://localhost/soap/server.php",
 *              'trace' => 1
 *          ]
 *      );
 *  $response = $client->__soapCall('registerOrder', [['guest' => [...], 'bag' => [...]]]);
 *      OR
 *  $response = $client->registerOrder(['guest' => [...], 'bag' => [...]]);
 */
class Soap implements RawPhpFpmWebService
{
    private $server;

    public function __construct(NativePhpSoapServer $server)
    {
        $this->server = $server;
    }

    public function response(): void
    {
        $this->server->handle();
    }
}