<?php

/**
 * Pavex Website v3 - Control
 *
 * Application entry point. Wires together the Router, HttpRequest and HttpResponse,
 * dispatches the matched presenter and sends the response.
 *
 * Static configuration (set once in index.php before run()):
 *   Control::$isDevelopment  — enables full exception output (default: false)
 *   Control::$xGeneratorBy   — sends X-Generator-By header (default: true)
 *   Control::$xGeneratorTimer — sends X-Generator-Timer header (default: true)
 *
 * @author    Pavel Machacek <pavex@ines.cz>
 * @copyright 2009–2026 Pavel Macháček
 * @license   MIT
 * @package   Pavex\Website
 */

namespace Pavex\Website;

use Pavex\Http\Request as HttpRequest;
use Pavex\Http\Response as HttpResponse;
use Pavex\Http\HttpExceptionInterface;
use Pavex\Http\Exception\NotFoundException;
use Pavex\Website\PresenterInterface;
use Pavex\Website\RouterInterface;
use Pavex\Website\Exception\InvalidPresenterException;
use Exception;
use Tracy\Debugger;


class Control
{

    public static bool $isDevelopment = false;
    public static bool $xGeneratorBy = true;
    public static bool $xGeneratorTimer = true;

    private RouterInterface $router;
    private HttpRequest $httpRequest;
    private HttpResponse $httpResponse;


    public function __construct(RouterInterface $router,
        ?HttpRequest $request = null, ?HttpResponse $response = null)
    {
        $this->router = $router;
        $this->httpRequest = $request ?? HttpRequest::fromGlobals();
        $this->httpResponse = $response ?? new HttpResponse();
    }


    public function getHttpRequest(): HttpRequest
    {
        return $this->httpRequest;
    }


    public function getHttpResponse(): HttpResponse
    {
        return $this->httpResponse;
    }


    /**
     * Instantiates a presenter via the ActionHandler constructor callable,
     * verifies it implements PresenterInterface, then injects route args
     * as public property values.
     *
     * @throws InvalidPresenterException when the constructor does not return a PresenterInterface
     */
    public function createPresenterInstance(callable $constructor, array $args): PresenterInterface
    {
        $presenter = $constructor();

        if (!$presenter instanceof PresenterInterface) {
            throw new InvalidPresenterException(sprintf(
                'Constructor must return a PresenterInterface instance, got %s.',
                get_debug_type($presenter)
            ));
        }
        foreach ($args as $name => $value) {
            $presenter->{$name} = $value;
        }
        return $presenter;
    }


    /**
     * Creates the presenter, renders it and stores output in the response.
     * Passes exceptions to throwException() for centralised handling.
     */
    public function renderPresenter(ActionHandler $handler): void
    {
        try {
            $presenter = $this->createPresenterInstance($handler->constructor, $handler->args);
            $contents  = (string) $presenter;

            if ($contents !== '' && empty($this->httpResponse->contents)) {
                $this->httpResponse->contents = $contents;
            }
            unset($presenter);
        }
        catch (Exception $e) {
            $this->throwException($e);
        }
    }


    /**
     * Handles exceptions during request dispatch.
     *
     * In development (Control::$isDevelopment = true): rethrows for full stack traces.
     * In production: logs via Tracy or error_log, maps HTTP exceptions to status codes.
     */
    private function throwException(Exception $e): void
    {
        if (static::$isDevelopment) {
            throw $e;
        }

        if (class_exists(Debugger::class)) {
            Debugger::log($e, Debugger::EXCEPTION);
        }
        else {
            error_log($e->getMessage());
        }

        if ($e instanceof HttpExceptionInterface) {
            $this->httpResponse->setStatus($e->getStatusCode());
            return;
        }
    }


    /**
     * Main request dispatch loop.
     * Resolves content type, finds the matching presenter, renders it and sends the response.
     */
    public function run(): void
    {
        $time = -microtime(true);

        try {
            $request  = $this->httpRequest;
            $response = $this->httpResponse;
            $response->setContentType($request->getContentType());
            $handler = $this->router->getActionHandler($request);

            if ($handler === null) {
                throw new NotFoundException('No route matched the request.');
            }
            $this->renderPresenter($handler);
        }
        catch (Exception $e) {
            $this->throwException($e);
        }

        $time += microtime(true);

        // Setup extra headers
        if (static::$xGeneratorBy) {
            $this->httpResponse->setHeader('X-Generator-By', 'pavex/website');
        }
        if (static::$xGeneratorTimer) {
            $this->httpResponse->setHeader('X-Generator-Timer', sprintf('%0.3f s', $time));
        }
        $this->httpResponse->send();
    }


}
