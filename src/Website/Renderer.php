<?php

/**
 * Pavex Website v3 - Renderer
 *
 * Extends Component with PHP template discovery and rendering.
 * Template name is derived from the class name via CamelCase to kebab-case conversion.
 * Templates are resolved relative to the directory of the concrete class file.
 *
 * Static configuration (set once in index.php before run()):
 *   Renderer::$templatePath — template subdirectory (default: 'templates/')
 *   Renderer::$templateExt  — template file extension (default: 'php')
 *
 * Template inheritance:
 *   Call $parent('layout-name') inside a template to wrap its output in a parent template.
 *   Call $children() inside a parent template to render the child output.
 *   Circular parent references are detected and throw CircularTemplateException.
 *
 * @author    Pavel Machacek <pavex@ines.cz>
 * @copyright 2009–2026 Pavel Macháček
 * @license   MIT
 * @package   Pavex\Website
 */

namespace Pavex\Website;

use Pavex\Website\Exception\CircularTemplateException;
use ReflectionClass;
use InvalidArgumentException;


abstract class Renderer extends Component
{

    const DEFAULT_TEMPLATE_PATH = 'templates/';
    const DEFAULT_TEMPLATE_EXT = 'php';

    public static string $templatePath = self::DEFAULT_TEMPLATE_PATH;
    public static string $templateExt = self::DEFAULT_TEMPLATE_EXT;

    private array $templates = [];
    private ?string $template = null;
    private string $contents = '';


    /**
     * Returns the absolute path to the template file for this presenter.
     * Derived automatically from the class name (CamelCase to kebab-case).
     * Override in a subclass to use a custom template path.
     */
    protected function getTemplateFilename(): string
    {
        $reflection = new ReflectionClass($this);
        $filename = $reflection->getFileName();
        $dirname = dirname($filename) . DIRECTORY_SEPARATOR . static::$templatePath;
        $class_name = basename($filename, '.' . static::$templateExt);
        $name = strtolower(preg_replace('/([A-Z][a-z]+)$/', '', $class_name));
        return $this->makeTemplateFilename($name, $dirname);
    }


    /**
     * Registers the next template to process in the render loop.
     * Throws CircularTemplateException on circular references.
     */
    private function setTemplate(string $name): void
    {
        if (in_array($name, $this->templates, true)) {
            throw new CircularTemplateException(sprintf(
                'Circular template reference detected: %s',
                implode(' -> ', [...$this->templates, $name])
            ));
        }
        $this->template = $name;
        $this->templates[] = $name;
    }


    private function makeTemplateFilename(string $template, string $path): string
    {
        return $path . $template . '.' . static::$templateExt;
    }


    private function includeTemplateFile(string $filename): string
    {
        if (!file_exists($filename)) {
            throw new InvalidArgumentException(sprintf('Template file not found: %s', $filename));
        }

        // Template addon function
        $parent = function (string $name): void {
            $this->setTemplate($name);
        };

        // Template addon function
        $children = function (): string {
            return $this->contents;
        };

        ob_start();
        try {
            include $filename;
        }
        finally {
            $result = ob_get_clean();
        }
        return $result;
    }


    public function render(): ?string
    {
        $this->templates = [];
        $this->template = null;
        $this->contents = '';

        $template_filename = $this->getTemplateFilename();
        $dirname = dirname($template_filename) . DIRECTORY_SEPARATOR;
        $template = basename($template_filename, '.' . static::$templateExt);

        $this->setTemplate($template);

        do {
            $filename = $this->makeTemplateFilename($this->template, $dirname);
            $this->template = null;
            $this->contents = $this->includeTemplateFile($filename);
        }
        while ($this->template !== null);
        return $this->contents;
    }


}
