<?php

declare(strict_types=1);

/**
 * Pavex Website v3 - PresenterTest
 *
 * @author  Pavel Machacek <pavex@ines.cz>
 * @package Pavex\Website\Tests\Unit
 */

namespace Pavex\Website\Tests\Unit;

use Pavex\Website\Presenter;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;


class PresenterTest extends TestCase
{


    private function makeSimplePresenter(): Presenter
    {
        $tplDir = dirname(__DIR__) . '/fixtures/templates/';

        return new class($tplDir) extends Presenter
        {

            public string $title = 'Default';
            public int $page = 1;
            private string $dir;


            public function __construct(string $dir)
            {
                $this->dir = $dir;
            }


            protected function getTemplateFilename(): string
            {
                return $this->dir . 'simple.php';
            }


        };
    }


    // -- __set guard ---------------------------------------------------------

    public function testSetDeclaredPropertyWorks(): void
    {
        $p = $this->makeSimplePresenter();
        $p->title = 'New title';

        $this->assertSame('New title', $p->title);
    }


    public function testSetUndeclaredPropertyThrows(): void
    {
        $p = $this->makeSimplePresenter();

        $this->expectException(InvalidArgumentException::class);
        $p->nonexistent = 'x';
    }


    // -- getArgs -------------------------------------------------------------

    public function testGetArgsReturnsPublicProperties(): void
    {
        $p = $this->makeSimplePresenter();
        $p->title = 'Test';
        $args = $p->getArgs();

        $this->assertArrayHasKey('title', $args);
        $this->assertSame('Test', $args['title']);
        $this->assertArrayHasKey('page', $args);
        $this->assertSame(1, $args['page']);
    }


    public function testGetArgsExcludesStaticProperties(): void
    {
        $p = $this->makeSimplePresenter();
        $args = $p->getArgs();

        // $templatePath and $templateExt are static on Renderer — must not appear
        $this->assertArrayNotHasKey('templatePath', $args);
        $this->assertArrayNotHasKey('templateExt', $args);
    }


    public function testGetArgsReflectsCurrentValues(): void
    {
        $p = $this->makeSimplePresenter();
        $p->title = 'Changed';
        $p->page = 5;

        $args = $p->getArgs();
        $this->assertSame('Changed', $args['title']);
        $this->assertSame(5, $args['page']);
    }


    // -- __toString ----------------------------------------------------------

    public function testToStringDelegatesToRender(): void
    {
        $p = $this->makeSimplePresenter();
        $p->title = 'Cast test';

        $this->assertStringContainsString('Cast test', (string) $p);
    }


}
