<?php

namespace TijsVerkoyen\CssToInlineStyles\tests;

use PHPUnit\Framework\TestCase;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class HTML5ParserTest extends TestCase
{
    /**
     * @var CssToInlineStyles
     */
    protected $cssToInlineStyles;

    /**
     * @before
     */
    protected function prepare()
    {
        $this->cssToInlineStyles = new CssToInlineStyles(true);
    }

    /**
     * @after
     */
    protected function clear()
    {
        $this->cssToInlineStyles = null;
    }

    public function testBasicHtml()
    {
        $html = '<!doctype html><html><head><style>body{color:blue}</style></head><body><p>foo</p></body></html>';
        $css = 'p { color: red; }';
        $expected = <<<EOF
<!doctype html>
<html><head><style>body{color:blue}</style></head><body style="color: blue;"><p style="color: red;">foo</p></body></html>
EOF;

        $this->assertEquals($expected, $this->cssToInlineStyles->convert($html, $css));
    }
}
