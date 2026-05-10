<?php

declare(strict_types=1);

/**
 * Pavex Website v3 - RouterTest
 *
 * @author  Pavel Machacek <pavex@ines.cz>
 * @package Pavex\Website\Tests\Unit
 */

namespace Pavex\Website\Tests\Unit;

use Pavex\Website\Router;
use Pavex\Website\Router\InputRule;
use Pavex\Website\Router\OutputRule;
use Pavex\Website\ActionHandler;
use Pavex\Website\Presenter;
use Pavex\Http\Request;
use Pavex\UrlScript;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;


// -- Fixtures ---------------------------------------------------------------

class RouterFixturePresenter extends Presenter
{

    public string $slug = '';
    public int $page = 1;


    protected function getTemplateFilename(): string
    {
        return '/nonexistent/router-fixture.php';
    }


}


class RouterOtherPresenter extends Presenter
{


    protected function getTemplateFilename(): string
    {
        return '/nonexistent/other.php';
    }


}


// ---------------------------------------------------------------------------

class RouterTest extends TestCase
{


    private function makeRequest(string $url, string $method = 'GET'): Request
    {
        $parts = parse_url($url);
        $urlScript = new UrlScript($url, '/');
        $urlScript->scheme = $parts['scheme'] ?? 'http';
        $urlScript->host = $parts['host'] ?? 'example.com';
        $urlScript->port = isset($parts['port']) ? (int) $parts['port'] : null;
        $urlScript->path = $parts['path'] ?? '/';

        return new Request($urlScript, $method);
    }


    // -- InputRule -----------------------------------------------------------

    public function testInputRuleMatchesUrl(): void
    {
        $rule = new InputRule('/^https?:\/\/example\.com\/$/i',
            fn() => new ActionHandler(fn() => new RouterFixturePresenter(), [])
        );

        $this->assertNotNull($rule->getActionHandler($this->makeRequest('http://example.com/')));
        $this->assertNull($rule->getActionHandler($this->makeRequest('http://example.com/other')));
    }


    public function testInputRuleCapture(): void
    {
        $rule = new InputRule(
            '/\/article\/([a-z\-]+)$/i',
            fn(array $m) => new ActionHandler(fn() => new RouterFixturePresenter(), ['slug' => $m[1]])
        );

        $h = $rule->getActionHandler($this->makeRequest('http://example.com/article/hello-world'));

        $this->assertNotNull($h);
        $this->assertSame('hello-world', $h->args['slug']);
        $this->assertNull($rule->getActionHandler($this->makeRequest('http://example.com/other')));
    }


    // -- OutputRule ----------------------------------------------------------

    public function testOutputRuleStaticUrl(): void
    {
        $rule = new OutputRule(RouterFixturePresenter::class, '/fixture');

        $this->assertSame('/fixture', $rule->getUrl(RouterFixturePresenter::class));
        $this->assertNull($rule->getUrl(RouterOtherPresenter::class));
    }


    public function testOutputRulePlaceholders(): void
    {
        $rule = new OutputRule(RouterFixturePresenter::class, '/article/{slug}/page/{page}');
        $url = $rule->getUrl(RouterFixturePresenter::class, ['slug' => 'hello', 'page' => 3]);

        $this->assertSame('/article/hello/page/3', $url);
    }


    public function testOutputRuleMissingPlaceholderThrows(): void
    {
        $rule = new OutputRule(RouterFixturePresenter::class, '/article/{slug}');

        $this->expectException(InvalidArgumentException::class);
        $rule->getUrl(RouterFixturePresenter::class, []);
    }


    public function testOutputRuleClosure(): void
    {
        $rule = new OutputRule(RouterFixturePresenter::class, fn(array $args) => '/dyn/' . $args['slug']);

        $this->assertSame('/dyn/test', $rule->getUrl(RouterFixturePresenter::class, ['slug' => 'test']));
    }


    // -- Router dispatch -----------------------------------------------------

    public function testRouterDispatchesFirstMatch(): void
    {
        $router = new Router([
            new InputRule('/\/home$/i', fn() => new ActionHandler(fn() => new RouterFixturePresenter(), ['slug' => 'home'])),
            new InputRule('/\/other$/i', fn() => new ActionHandler(fn() => new RouterFixturePresenter(), ['slug' => 'other'])),
        ]);

        $h = $router->getActionHandler($this->makeRequest('http://example.com/home'));

        $this->assertNotNull($h);
        $this->assertSame('home', $h->args['slug']);

        $h2 = $router->getActionHandler($this->makeRequest('http://example.com/other'));
        $this->assertSame('other', $h2->args['slug']);

        $this->assertNull($router->getActionHandler($this->makeRequest('http://example.com/nope')));
    }


    public function testRouterFirstRuleWins(): void
    {
        $router = new Router([
            new InputRule('/\//i', fn() => new ActionHandler(fn() => new RouterFixturePresenter(), ['slug' => 'first'])),
            new InputRule('/\//i', fn() => new ActionHandler(fn() => new RouterFixturePresenter(), ['slug' => 'second'])),
        ]);

        $h = $router->getActionHandler($this->makeRequest('http://example.com/'));
        $this->assertSame('first', $h->args['slug']);
    }


    public function testRouterGetUrl(): void
    {
        $router = new Router([
            new OutputRule(RouterFixturePresenter::class, '/fixture'),
            new OutputRule(RouterOtherPresenter::class, '/other'),
        ]);

        $this->assertSame('/fixture', $router->getUrl(RouterFixturePresenter::class));
        $this->assertSame('/other', $router->getUrl(RouterOtherPresenter::class));
        $this->assertNull($router->getUrl('UnknownPresenter'));
    }


    // -- getPresenterLink ----------------------------------------------------

    public function testGetPresenterLinkFromInstance(): void
    {
        $router = new Router([
            new OutputRule(RouterFixturePresenter::class, '/article/{slug}'),
        ]);
        $p = new RouterFixturePresenter();
        $p->slug = 'my-post';

        $this->assertSame('/article/my-post', $router->getPresenterLink($p));
    }


    public function testGetPresenterLinkFromClass(): void
    {
        $router = new Router([
            new OutputRule(RouterFixturePresenter::class, '/article/{slug}'),
        ]);

        $this->assertSame('/article/test', $router->getPresenterLink(RouterFixturePresenter::class, ['slug' => 'test']));
    }


    public function testGetPresenterLinkStripsDefaults(): void
    {
        $router = new Router([
            new OutputRule(RouterFixturePresenter::class, '/article/{slug}'),
        ]);
        $p = new RouterFixturePresenter();
        $p->slug = 'hello'; // page stays at default 1 → stripped

        $this->assertSame('/article/hello', $router->getPresenterLink($p));
    }


    public function testGetPresenterLinkThrowsForNonPresenter(): void
    {
        $router = new Router([new OutputRule(RouterFixturePresenter::class, '/fixture')]);

        $this->expectException(InvalidArgumentException::class);
        $router->getPresenterLink(\stdClass::class);
    }


    public function testGetPresenterLinkThrowsWhenNoRuleMatches(): void
    {
        $router = new Router([new OutputRule(RouterFixturePresenter::class, '/fixture')]);

        $this->expectException(InvalidArgumentException::class);
        $router->getPresenterLink(RouterOtherPresenter::class);
    }


    // -- Event hooks ---------------------------------------------------------

    public function testOnInputRuleHookFires(): void
    {
        $fired = [];
        $router = new Router([
            new InputRule('/\//i', fn() => new ActionHandler(fn() => new RouterFixturePresenter(), [])),
        ]);
        $router->onInputRule[] = function ($r, $rule, $handler) use (&$fired): void {
            $fired[] = $handler !== null;
        };

        $router->getActionHandler($this->makeRequest('http://example.com/'));
        $this->assertCount(1, $fired);
        $this->assertTrue($fired[0]);
    }


    public function testOnOutputRuleHookFires(): void
    {
        $captured = [];
        $router = new Router([
            new OutputRule(RouterFixturePresenter::class, '/fixture'),
        ]);
        $router->onOutputRule[] = function ($r, $rule, $presenter, $args, $url) use (&$captured): void {
            $captured[] = $url;
        };

        $router->getUrl(RouterFixturePresenter::class);
        $this->assertCount(1, $captured);
        $this->assertSame('/fixture', $captured[0]);
    }


}
