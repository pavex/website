<?php

/**
 * Pavex Website v3 - InputRulePanel
 *
 * Tracy debug bar panel for monitoring incoming router rule evaluation.
 * Displays each InputRule that was tested against the current request
 * and the ActionHandler it produced (or null when it did not match).
 *
 * @author  Pavel Machacek <pavex@ines.cz>
 * @package Pavex\Bridges\WebsiteTracy
 */

namespace Pavex\Bridges\WebsiteTracy;

use Pavex\Website\RouterInterface;
use Pavex\Website\Router\InputRuleInterface;
use Pavex\Website\ActionHandler;
use Tracy;


class InputRulePanel implements Tracy\IBarPanel
{

    public const PANEL_CAPTION = 'Router input';
    public const ICON = '&#10233;';

    public string $name = self::PANEL_CAPTION;
    private array $messages = [];


    public static function initialize(RouterInterface $router, string $name = ''): static
    {
        $panel = new static($router);
        $panel->name = $name ?: $panel->name;
        Tracy\Debugger::getBar()->addPanel($panel);
        return $panel;
    }


    public function __construct(RouterInterface $router)
    {
        $router->onInputRule[] = $this->record(...);
    }


    private function record(RouterInterface $router, InputRuleInterface $rule, ?ActionHandler $handler): void
    {
        $this->messages[] = [$rule, $handler];
    }


    public function getTab(): string
    {
        return sprintf('<span title="%s">%s<span class="tracy-label">%s %dx</span></span>',
            $this->name, self::ICON, $this->name, count($this->messages)
        );
    }


    public function getPanel(): string
    {
        $dump = fn (mixed $v): string => Tracy\Dumper::toHtml($v, [
            Tracy\Dumper::DEPTH => 2,
            Tracy\Dumper::TRUNCATE => 128,
        ]);

        $rows = '';
        foreach ($this->messages as [$rule, $handler]) {
            $rows .= '<tr>'
                . '<td>' . $dump($rule) . '</td>'
                . '<td>&rarr;</td>'
                . '<td>' . $dump($handler) . '</td>'
                . '</tr>';
        }

        return sprintf('
            <h1>%s&nbsp;%s</h1>
            <div class="tracy-inner">
                <div class="tracy-inner-container">
                    <table class="tracy-sortable">
                        <tbody>
                            <tr>
                                <th>Input rule</th>
                                <th></th>
                                <th>Action handler</th>
                            </tr>
                            %s
                        </tbody>
                    </table>
                </div>
            </div>
        ', self::ICON, $this->name, $rows);
    }


}
