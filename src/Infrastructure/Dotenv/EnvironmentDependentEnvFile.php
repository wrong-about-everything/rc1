<?php

declare(strict_types=1);

namespace RC\Infrastructure\Dotenv;

use Dotenv\Dotenv as OneAndOnly;
use RC\Infrastructure\Filesystem\DirPath;
use RC\Infrastructure\Filesystem\Filename\PortableFromString;
use RC\Infrastructure\Filesystem\FilePath\FromDirAndFileName;
use RC\Infrastructure\Http\Request\Inbound\Request;

class EnvironmentDependentEnvFile implements DotEnv
{
    private $concreteDotEnv;

    public function __construct(DirPath $root, Request $incomingRequest)
    {
        $this->concreteDotEnv = $this->concrete($root, $incomingRequest);
    }

    public function load(): void
    {
        $this->concreteDotEnv->load();
    }

    private function concrete(DirPath $root, Request $incomingRequest): DotEnv
    {
        if ((new FromDirAndFileName($root, new PortableFromString('.env.dev')))->exists()) {
            if (isset($incomingRequest->headers()['X-This-Is-Functional-Test']) && $incomingRequest->headers()['X-This-Is-Functional-Test'] === '1') {
                return new DefaultEnvFile(OneAndOnly::createUnsafeImmutable($root->value(), '.env.dev.testing_mode'));
            } else {
                return new DefaultEnvFile(OneAndOnly::createUnsafeImmutable($root->value(), '.env.dev'));
            }
        }

        return new NonExistentEnvFile();
    }
}
