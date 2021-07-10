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
use RC\Infrastructure\Http\Request\Url\Path\FromString as Path;
use RC\Infrastructure\Http\Request\Url\Port\FromInt;
use RC\Infrastructure\Http\Request\Url\Query;
use RC\Infrastructure\Http\Request\Url\Query\FromArray;
use RC\Infrastructure\Http\Request\Url\Query\NonSpecified;
use RC\Infrastructure\Http\Request\Url\Scheme\Http;
use RC\Infrastructure\UserStory\Body\Arrray;
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
                                        new Arrray(['vass' => 'nakvass'])
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
            (new Arrray(['vass' => 'nakvass']))->value()->raw(),
            $userStory->response()->body()->raw()
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
                                        new Arrray(['hello' => $there, 'how' => $areYou])
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
                        new Path(''),
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
            (new Arrray([
                'hello' => 'there', 'how' => 'are_you'
            ]))
                ->value()->raw(),
            $userStory->response()->body()->raw()
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
                                        new Arrray(['vass' => 'nakvass', 'body' => $body])
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
            (new Arrray(['vass' => 'nakvass', 'body' => 'hello, Vasya!']))->value(),
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
                                        new Arrray(['vass' => 'nakvass'])
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