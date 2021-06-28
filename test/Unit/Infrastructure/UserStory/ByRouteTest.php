<?php

declare(strict_types=1);

namespace RC\Tests\Unit\Infrastructure\UserStory;

use PHPUnit\Framework\TestCase;
use RC\Infrastructure\Http\Request\Inbound\Composite as CompositeRequest;
use RC\Infrastructure\Http\Request\Method\Get;
use RC\Infrastructure\Http\Request\Method\Post;
use RC\Infrastructure\Http\Request\Url\Fragment\NonSpecified as NonSpecifiedFragment;
use RC\Infrastructure\Http\Request\Url\Composite as CompositeUrl;
use RC\Infrastructure\Http\Request\Url\Host\FromString;
use RC\Infrastructure\Http\Request\Url\Path\NonSpecified as NonSpecifiedPath;
use RC\Infrastructure\Http\Request\Url\Port\FromInt;
use RC\Infrastructure\Http\Request\Url\Query;
use RC\Infrastructure\Http\Request\Url\Query\FromArray;
use RC\Infrastructure\Http\Request\Url\Query\NonSpecified;
use RC\Infrastructure\Http\Request\Url\Scheme\Http;
use RC\Infrastructure\UserStory\Body\Json;
use RC\Infrastructure\UserStory\ByRoute;
use RC\Infrastructure\UserStory\Response\Successful;
use RC\Tests\Infrastructure\Http\Request\Url\Test;
use RC\Tests\Infrastructure\Routing\FoundWithNoParams;
use RC\Tests\Infrastructure\Routing\FoundWithParams;
use RC\Tests\Infrastructure\Routing\NotFound;
use RC\Tests\Infrastructure\UserStories\FromResponse;

class ByRouteTest extends TestCase
{
    public function testWhenRouteWithNoPlaceholdersForGetRequestExistsThenTheCorrespondingUserStoryIsExecuted()
    {
        $userStory =
            new ByRoute(
                [
                    [
                        new FoundWithNoParams(),
                        function (Query $query) {
                            return
                                new FromResponse(
                                    new Successful(
                                        new Json(['vass' => 'nakvass', 'query' => $query])
                                    )
                                );
                        }
                    ]
                ],
                new CompositeRequest(new Get(), new Test(), [], '')
            );

        $this->assertTrue($userStory->exists());
        $this->assertTrue($userStory->response()->isSuccessful());
        $this->assertEquals(
            (new Json(['vass' => 'nakvass', 'query' => new NonSpecified()]))->value(),
            $userStory->response()->body()
        );
    }

    public function testWhenRouteWithPlaceholdersForGetRequestExistsThenTheCorrespondingUserStoryIsExecuted()
    {
        $userStory =
            new ByRoute(
                [
                    [
                        new FoundWithParams(['there', 'are_you']),
                        function (string $there, string $areYou, Query $query) {
                            return
                                new FromResponse(
                                    new Successful(
                                        new Json(['hello' => $there, 'how' => $areYou, 'query' => $query])
                                    )
                                );
                        }
                    ]
                ],
                new CompositeRequest(
                    new Get(),
                    new CompositeUrl(
                        new Http(),
                        new FromString('example.org'),
                        new FromInt(9000),
                        new NonSpecifiedPath(),
                        new FromArray(['filter' => 'registered_at:desc']),
                        new NonSpecifiedFragment()
                    ),
                    [],
                    ''
                )
            );

        $this->assertTrue($userStory->exists());
        $this->assertTrue($userStory->response()->isSuccessful());
        $this->assertEquals(
            (new Json([
                'hello' => 'there', 'how' => 'are_you', 'query' => new FromArray(['filter' => 'registered_at:desc'])
            ]))
                ->value(),
            $userStory->response()->body()
        );
    }

    public function testWhenRouteForPostRequestExistsThenTheCorrespondingUserStoryIsExecuted()
    {
        $userStory =
            new ByRoute(
                [
                    [
                        new FoundWithNoParams(),
                        function (string $body) {
                            return
                                new FromResponse(
                                    new Successful(
                                        new Json(['vass' => 'nakvass', 'body' => $body])
                                    )
                                );
                        }
                    ]
                ],
                new CompositeRequest(new Post(), new Test(), [], 'hello, Vasya!')
            );

        $this->assertTrue($userStory->exists());
        $this->assertTrue($userStory->response()->isSuccessful());
        $this->assertEquals(
            (new Json(['vass' => 'nakvass', 'body' => 'hello, Vasya!']))->value(),
            $userStory->response()->body()
        );
    }

    public function testWhenRouteDoesNotExistThen404Returned()
    {
        $userStory =
            new ByRoute(
                [
                    [
                        new NotFound(),
                        function () {
                            return
                                new FromResponse(
                                    new Successful(
                                        new Json(['vass' => 'nakvass'])
                                    )
                                );
                        }
                    ]
                ],
                new CompositeRequest(new Get(), new Test(), [], '')
            );

        $this->assertFalse($userStory->exists());
    }
}