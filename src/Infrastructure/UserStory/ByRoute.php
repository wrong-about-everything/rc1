<?php

declare(strict_types=1);

namespace RC\Infrastructure\UserStory;

use RC\Infrastructure\Http\Request\Inbound\Request;
use RC\Infrastructure\Http\Request\Method\Get;
use RC\Infrastructure\Http\Request\Url\Query\FromUrl;
use RC\Infrastructure\Routing\MatchResult;

class ByRoute implements UserStory
{
    private $concrete;

    public function __construct(array $route2UserStoryPairs, Request $request)
    {
        $this->concrete = $this->concrete($route2UserStoryPairs, $request);
    }

    public function response(): Response
    {
        return $this->concrete->response();
    }

    public function exists(): bool
    {
        return $this->concrete->exists();
    }

    private function concrete(array $route2UserStoryPairs, Request $request): UserStory
    {
        foreach ($route2UserStoryPairs as $route2UserStoryPair) {
            /**
             * @var $matchResult MatchResult
             */
            $matchResult = $route2UserStoryPair[0]->matchResult($request);
            if ($matchResult->matches()) {
                if ($request->method()->equals(new Get())) {
                    return $route2UserStoryPair[1](... array_merge($matchResult->params(), [new FromUrl($request->url())]));
                } else {
                    return $route2UserStoryPair[1](... array_merge($matchResult->params(), [$request->body()]));
                }
            }
        }

        return new NonExistent();
    }
}