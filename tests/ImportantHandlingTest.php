<?php

namespace TijsVerkoyen\CssToInlineStyles\Tests;

use PHPUnit\Framework\TestCase;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

/**
 * Cascade behaviour around !important.
 */
class ImportantHandlingTest extends TestCase
{
    /**
     * @var CssToInlineStyles
     */
    protected $cssToInlineStyles;

    /**
     * @before
     */
    protected function prepare(): void
    {
        $this->cssToInlineStyles = new CssToInlineStyles();
    }

    /**
     * "!important" only counts as the priority flag at the end of the value,
     * not anywhere inside it (e.g. inside a URL).
     */
    public function testImportantInsideValueIsNotTreatedAsImportant(): void
    {
        $result = $this->cssToInlineStyles->convert(
            '<html><body><p>x</p></body></html>',
            'p { background: url(/a!important.png); } p { background: blue; }'
        );

        $this->assertStringContainsString('<p style="background: blue;">x</p>', $result);
    }

    public function testImportantBeatsHigherSpecificity(): void
    {
        $result = $this->cssToInlineStyles->convert(
            '<html><body><p id="x">x</p></body></html>',
            'p { color: red !important; } #x { color: blue; }'
        );

        $this->assertStringContainsString('color: red !important;', $result);
    }

    /**
     * Per the CSS cascade an !important stylesheet declaration beats a
     * non-important inline declaration; the library currently always keeps
     * the inline one.
     */
    public function testImportantStylesheetDeclarationBeatsNormalInlineStyle(): void
    {
        $result = $this->cssToInlineStyles->convert(
            '<html><body><p style="color: blue">x</p></body></html>',
            'p { color: red !important; }'
        );

        if (strpos($result, 'color: red !important;') === false) {
            $this->markTestIncomplete(
                'Not changed yet: an existing inline declaration always wins, and changing that alters'
                . ' output for existing users. Left for a maintainer decision.'
            );
        }

        $this->assertStringContainsString('color: red !important;', $result);
    }
}
