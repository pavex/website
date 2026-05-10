<?php

declare(strict_types=1);

/**
 * Pavex Website v3 - ControlTest
 *
 * @author  Pavel Machacek <pavex@ines.cz>
 * @package Pavex\Website\Tests\Unit
 */

namespace Pavex\Website\Tests\Unit;

use Pavex\Website\Control;
use Pavex\Website\Router;
use Pavex\Website\Router\InputRule;
use Pavex\Website\ActionHandler;
use Pavex\Website\Presenter;
use Pavex\Website\Exception\InvalidPresenterException;
use Pavex\Http\Request;
use Pavex\Http\Response;
use Pavex\Http\Exception\NotFoundException;
use Pavex\UrlScript;
use PHPUnit\Framework\TestCase;
use ReflectionObject;


// -- Fixtures ---------------------------------------------------------------

class ControlFixturePresenter extends Presenter
{

    public string $slug = '';
    public int $page = 1;


    protected function getTemplateFilename(): string
    {
        return '/nonexistent/control-fixture.php';
    }


}


class ControlRenderablePresenter extends Presenter
{

    public string $output = 'hello-world';


    protected function getTemplateFilename(): string
    {
        return sys_get_temp_dir() . '/pavex_renderable_test.php';
    }


}


// ---------------------------------------------------------------------------

class ControlTest extends TestCase
{

    private Request $request;
    private string $tmpTemplate;


    protected function setUp(): void
    {
        $us = new UrlScript('http://example.com/', '/');
        $us->scheme = 'http';
        $us->host = 'example.com';
        $us->path = '/';
        $this->request = new Request($us, 'GET');

        $this->tmpTemplate = sys_get_temp_dir() . '/pavex_renderable_test.php';
    }


    protected function tearDown(): void
    {
        @unlink($this->tmpTemplate);
    }


    private function makeControl(?Router $router = null, ?Response $response = null): Control
    {
        return new Control(
            $router ?? new Router([]),
            $this->request,
            $response ?? new Response()
        );
    }


    // -- createPresenterInstance ---------------------------------------------

    public function testCreatePresenterInstanceInjectsArgs(): void
    {
        $control = $this->makeControl();
        $presenter = $control->createPresenterInstance(
            fn() => new ControlFixturePresenter(),
            ['slug' => 'injected', 'page' => 5]
        );

        $this->assertSame('injected', $presenter->slug);
        $this->assertSame(5, $presenter->page);
    }


    public function testCreatePresenterInstanceThrowsForNonPresenter(): void
    {
        $control = $this->makeControl();

        $this->expectException(InvalidPresenterException::class);
        $control->createPresenterInstance(fn() => new \stdClass(), []);
    }


    // -- renderPresenter -----------------------------------------------------

    public function testRenderPresenterStoresOutput(): void
    {
        file_put_contents($this->tmpTemplate, '<?= $this->output ?>');

        $response = new Response();
        $control = $this->makeControl(null, $response);
        $control->renderPresenter(
            new ActionHandler(fn() => new ControlRenderablePresenter(), [])
        );

        $this->assertSame('hello-world', $response->contents);
    }


    public function testRenderPresenterDoesNotOverwriteExistingContents(): void
    {
        file_put_contents($this->tmpTemplate, '<?= $this->output ?>');

        $response = new Response();
        $response->contents = 'already-set';

        $control = $this->makeControl(null, $response);
        $control->renderPresenter(
            new ActionHandler(fn() => new ControlRenderablePresenter(), [])
        );

        $this->assertSame('already-set', $response->contents);
    }


    // -- run() ---------------------------------------------------------------

    public function testRunThrowsNotFoundExceptionInDevMode(): void
    {
        Control::$isDevelopment = true;
        $control = new Control(new Router([]), $this->request, new Response());

        $this->expectException(NotFoundException::class);

        try {
            $control->run();
        }
        finally {
            Control::$isDevelopment = false;
        }
    }


    // -- X-Generator headers -------------------------------------------------

    public function testXGeneratorByFlagSetsHeader(): void
    {
        file_put_contents($this->tmpTemplate, '<?= $this->output ?>');

        $response = new Response();
        $control = $this->makeControl(null, $response);
        $control->renderPresenter(
            new ActionHandler(fn() => new ControlRenderablePresenter(), [])
        );

        Control::$xGeneratorBy = true;
        Control::$xGeneratorTimer = false;
        $response->setHeader('X-Generator-By', 'Pavex');

        $ref = new ReflectionObject($response);
        $prop = $ref->getProperty('headers');
        $prop->setAccessible(true);
        $headers = $prop->getValue($response);

        $this->assertArrayHasKey('X-Generator-By', $headers);
        $this->assertSame('Pavex', $headers['X-Generator-By']);
        $this->assertArrayNotHasKey('X-Generator-Timer', $headers);

        Control::$xGeneratorBy = true;
    }


    // -- getHttpRequest / getHttpResponse accessors --------------------------

    public function testGetHttpRequestReturnsInjectedRequest(): void
    {
        $control = $this->makeControl();
        $this->assertSame($this->request, $control->getHttpRequest());
    }


    public function testGetHttpResponseReturnsInjectedResponse(): void
    {
        $response = new Response();
        $control = $this->makeControl(null, $response);
        $this->assertSame($response, $control->getHttpResponse());
    }


}
