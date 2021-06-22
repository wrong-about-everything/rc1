<?php

declare(strict_types=1);

namespace RC\Infrastructure\ExecutionEnvironmentAdapter;

/**
 * As of time of writing, there is no server grpc implementation.
 *
 * Anyways, conceptually it's not very different from soap:
 * you have a specification as a map, where a key is a requested action, and value is a function to be executed.
 * This map is described in a .proto file.
 * Mapping (aka routing) itself is taken care of in a language-specific grpc server implementation.
 * Each user story returns json which is handled, again, by internal grpc server implementation.
 *
 * @see https://github.com/grpc/grpc/blob/v1.38.0/examples/node/dynamic_codegen/route_guide/route_guide_server.js
 */
class GRpcServer
{
    private $service;

    public function __construct(ServiceSpecificationBasedOnProtoFile $service)
    {
        $this->service = $service;
    }

    public function response(): void
    {
        $this->service->handle();
    }
}