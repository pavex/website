<?php

/**
 * Pavex Website v3 - Router
 *
 * Orchestrates a collection of InputRule and OutputRule instances.
 * Iterates rules in order and returns the first match.
 * Fires onInputRule and onOutputRule event hooks after each rule evaluation
 * (used by Tracy debug panels).
 *
 * @author  Pavel Machacek <pavex@ines.cz>
 * @package Pavex\Website
 */

namespace Pavex\Website;

use Pavex\Http\Request;
use Pavex\Website\RouterInterface;
use Pavex\Website\PresenterInterface;
use Pavex\Website\Router\InputRuleInterface;
use Pavex\Website\Router\OutputRuleInterface;
use ReflectionClass;
use ReflectionProperty;
use InvalidArgumentException;


final class Router implements RouterInterface
{

    /** @var InputRuleInterface[] */
    private array $inputRules = [];

    /** @var OutputRuleInterface[] */
    private array $outputRules = [];

    /** @var array<callable(self, InputRuleInterface, ActionHandler|null): void> */
    public array $onInputRule = [];

    /** @var array<callable(self, OutputRuleInterface, string, array, string|null): void> */
    public array $onOutputRule = [];


    public function __construct(array $rules)
    {
        foreach ($rules as $rule) {
            if ($rule instanceof InputRuleInterface) {
                $this->inputRules[] = $rule;
            }
            if ($rule instanceof OutputRuleInterface) {
                $this->outputRules[] = $rule;
            }
        }
    }


    public function getActionHandler(Request $request): ?ActionHandler
    {
        foreach ($this->inputRules as $rule) {
            $handler = $rule->getActionHandler($request);

            foreach ($this->onInputRule as $callback) {
                $callback($this, $rule, $handler);
            }
            if ($handler !== null) {
                return $handler;
            }
        }
        return null;
    }


    public function getUrl(string $presenter, array $args = [], mixed $options = null): ?string
    {
        foreach ($this->outputRules as $rule) {
            $url = $rule->getUrl($presenter, $args, $options);

            foreach ($this->onOutputRule as $callback) {
                $callback($this, $rule, $presenter, $args, $url);
            }
            if ($url !== null) {
                return $url;
            }
        }
        return null;
    }


    /**
     * Filters public presenter properties against $params, excluding properties
     * whose value equals the class default — keeps generated URLs minimal.
     */
    private function getPresenterArgs(string $presenter, array $params = []): array
    {
        $args = [];
        $reflection = new ReflectionClass($presenter);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        $defaults   = $reflection->getDefaultProperties();

        foreach ($properties as $property) {
            $name = $property->name;

            if (!array_key_exists($name, $params)) {
                continue;
            }
            if (array_key_exists($name, $defaults) && $params[$name] === $defaults[$name]) {
                continue;
            }
            $args[$name] = $params[$name];
        }
        return $args;
    }


    /**
     * Generates a URL for a presenter instance or class name.
     * When an instance is passed, its current public property values are merged with $params.
     * When a class name string is passed, only $params are used.
     *
     * @throws InvalidArgumentException when no output rule matches or input is invalid
     */
    public function getPresenterLink(PresenterInterface|string $presenter, array $params = [], mixed $options = null): string
    {
        if (!is_subclass_of($presenter, PresenterInterface::class)) {
            if (is_string($presenter)) {
                throw new InvalidArgumentException(sprintf('Invalid link: %s does not implement PresenterInterface.', $presenter));
            }
            throw new InvalidArgumentException('Invalid link: argument is not a presenter class name or instance.');
        }

        $class = $presenter instanceof PresenterInterface ? get_class($presenter) : $presenter;
        $baseArgs = $presenter instanceof PresenterInterface ? $presenter->getArgs() : [];
        $args = $this->getPresenterArgs($class, array_merge($baseArgs, $params));
        $url = $this->getUrl($class, $args, $options);

        if ($url !== null) {
            return $url;
        }
        throw new InvalidArgumentException(sprintf(
            'No output rule found for presenter: %s', basename(str_replace('\\', '/', $class))
        ));
    }


}
