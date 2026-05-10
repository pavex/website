<?php

declare(strict_types=1);

/**
 * Pavex Website v3 - RendererTest
 *
 * @author  Pavel Machacek <pavex@ines.cz>
 * @package Pavex\Website\Tests\Unit
 */

namespace Pavex\Website\Tests\Unit;

use Pavex\Website\Presenter;
use Pavex\Website\Exception\CircularTemplateException;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;


class RendererTest extends TestCase
{

    private string $tplDir;


    protected function setUp(): void
    {
        $this->tplDir = dirname(__DIR__) . '/fixtures/templates/';
    }


    // -- helpers -------------------------------------------------------------

    /** Creates an anonymous Presenter that points to a given template file. */
    private function makePresenter(string $templateFile, array $props = []): Presenter
    {
        $tplDir = $this->tplDir;

        $p = new class($templateFile, $tplDir) extends Presenter
        {

            public string $title = 'Default title';
            public string $value = 'child-value';
            private string $tplFile;
            private string $dir;


            public function __construct(string $tplFile, string $dir)
            {
                $this->tplFile = $tplFile;
                $this->dir = $dir;
            }


            protected function getTemplateFilename(): string
            {
                return $this->dir . $this->tplFile . '.' . static::$templateExt;
            }


        };

        foreach ($props as $k => $v) {
            $p->{$k} = $v;
        }
        return $p;
    }


    // -- 1. Template auto-discovery ------------------------------------------

    public function testSimpleTemplateRenders(): void
    {
        $p = $this->makePresenter('simple');
        $output = $p->render();

        $this->assertStringContainsString('Default title', $output);
        $this->assertStringContainsString('<h1>', $output);
    }


    public function testInjectedPropertyRendered(): void
    {
        $p = $this->makePresenter('simple', ['title' => 'Hello Pavex']);
        $this->assertStringContainsString('Hello Pavex', $p->render());
    }


    // -- 2. Template inheritance ($parent / $children) -----------------------

    public function testTemplateInheritance(): void
    {
        $p = $this->makePresenter('child');
        $output = $p->render();

        $this->assertStringContainsString('<layout>', $output);
        $this->assertStringContainsString('child-value', $output);
        $this->assertStringContainsString('[', $output);
    }


    public function testThisAccessibleInInheritedTemplate(): void
    {
        $p = $this->makePresenter('child', ['value' => 'from-instance']);
        $this->assertStringContainsString('from-instance', $p->render());
    }


    // -- 3. Circular reference detection -------------------------------------

    public function testCircularParentThrows(): void
    {
        $p = $this->makePresenter('circular');

        $this->expectException(CircularTemplateException::class);
        $p->render();
    }


    // -- 4. Missing template -------------------------------------------------

    public function testMissingTemplateThrows(): void
    {
        $tplDir = $this->tplDir;

        $p = new class($tplDir) extends Presenter
        {

            private string $dir;


            public function __construct(string $dir)
            {
                $this->dir = $dir;
            }


            protected function getTemplateFilename(): string
            {
                return $this->dir . 'nonexistent.php';
            }


        };

        $this->expectException(InvalidArgumentException::class);
        $p->render();
    }


    // -- 5. Idempotence ------------------------------------------------------

    public function testRenderIsIdempotent(): void
    {
        $p = $this->makePresenter('simple', ['title' => 'Same every time']);
        $this->assertSame($p->render(), $p->render());
    }


    // -- 6. __toString -------------------------------------------------------

    public function testToStringDelegatesToRender(): void
    {
        $p = $this->makePresenter('simple', ['title' => 'ToString']);
        $this->assertStringContainsString('ToString', (string) $p);
    }


    // -- 7. Custom getTemplateFilename override ------------------------------

    public function testCustomTemplateOverride(): void
    {
        // Inline custom template
        $tmp = sys_get_temp_dir() . '/pavex_test_custom_' . uniqid() . '.php';
        file_put_contents($tmp, '<custom>ok</custom>');

        $p = new class($tmp) extends Presenter
        {

            private string $file;


            public function __construct(string $file)
            {
                $this->file = $file;
            }


            protected function getTemplateFilename(): string
            {
                return $this->file;
            }


        };

        $output = $p->render();

        $this->assertStringContainsString('<custom>', $output);
        @unlink($tmp);
    }


}
