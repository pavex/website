<?php

/**
 * Pavex Website v3 - OutputRulePanel
 *
 * Tracy debug bar panel for monitoring outgoing router rule evaluation (reverse routing).
 * Displays each OutputRule that was queried, the presenter and args passed to it,
 * and the URL it produced (or null when it did not match).
 *
 * @author  Pavel Machacek <pavex@ines.cz>
 * @package Pavex\Bridges\WebsiteTracy
 */

namespace Pavex\Bridges\WebsiteTracy;

use Pavex\Website\RouterInterface;
use Pavex\Website\Router\OutputRuleInterface;
use Tracy;


class OutputRulePanel implements Tracy\IBarPanel
{

    public const PANEL_CAPTION = 'Router output';
    public const ICON = '&#8656;';

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
        $router->onOutputRule[] = $this->record(...);
    }


    private function record(RouterInterface $router, OutputRuleInterface $rule,
        string $presenter, array $args, ?string $url): void
    {
        $this->messages[] = [$rule, $presenter, $args, $url];
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
        foreach ($this->messages as [$rule, $presenter, $args, $url]) {
            $rows .= '<tr>'
                . '<td>' . $dump($rule) . '</td>'
                . '<td><pre title="' . htmlspecialchars($presenter) . '">'
                    . htmlspecialchars(basename(str_replace('\\', '/', $presenter)))
                    . '</pre>' . $dump($args) . '</td>'
                . '<td>&rarr;</td>'
                . '<td><pre>' . $dump($url) . '</pre></td>'
                . '</tr>';
        }

        return sprintf('
            <h1>%s&nbsp;%s</h1>
            <div class="tracy-inner">
                <div class="tracy-inner-container">
                    <table class="tracy-sortable">
                        <tbody>
                            <tr>
                                <th>Output rule</th>
                                <th>Presenter / Args</th>
                                <th></th>
                                <th>URL</th>
                            </tr>
                            %s
                        </tbody>
                    </table>
                </div>
            </div>
        ', self::ICON, $this->name, $rows);
    }


}
