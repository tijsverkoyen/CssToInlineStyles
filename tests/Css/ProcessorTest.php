<?php

namespace TijsVerkoyen\CssToInlineStyles\Tests\Css;

use TijsVerkoyen\CssToInlineStyles\Css\Processor;
use PHPUnit\Framework\TestCase;

class ProcessorTest extends TestCase
{
    /**
     * @var Processor
     */
    protected $processor;

    /**
     * @before
     */
    protected function prepare(): void
    {
        $this->processor = new Processor();
    }

    public function testCssWithOneRule(): void
    {
        $css = <<<EOF
            a {
                padding: 5px;
                display: block;
            }
EOF;

        $rules = $this->processor->getRules($css);

        $this->assertCount(1, $rules);
        $this->assertInstanceOf('TijsVerkoyen\CssToInlineStyles\Css\Rule\Rule', $rules[0]);
        $this->assertEquals('a', $rules[0]->getSelector());
        $this->assertCount(2, $rules[0]->getProperties());
        $this->assertEquals('padding', $rules[0]->getProperties()[0]->getName());
        $this->assertEquals('5px', $rules[0]->getProperties()[0]->getValue());
        $this->assertEquals('display', $rules[0]->getProperties()[1]->getName());
        $this->assertEquals('block', $rules[0]->getProperties()[1]->getValue());
        $this->assertEquals(1, $rules[0]->getOrder());
    }

    public function testCssWithComments(): void
    {
        $css = <<<CSS
a {
    padding: 5px;
    display: block;
}
/* style the titles */
h1 {
    color: rebeccapurple;
}
/* end of title styles */
CSS;

        $rules = $this->processor->getRules($css);

        $this->assertCount(2, $rules);
        $this->assertEquals('a', $rules[0]->getSelector());
        $this->assertCount(2, $rules[0]->getProperties());
        $this->assertEquals(1, $rules[0]->getOrder());
        $this->assertEquals('h1', $rules[1]->getSelector());
        $this->assertCount(1, $rules[1]->getProperties());
        $this->assertEquals(2, $rules[1]->getOrder());
    }

    public function testCssWithMediaQueries(): void
    {
        $css = <<<EOF
@media (max-width: 600px) {
    a {
        color: green;
    }
}

a {
  color: red;
}
EOF;

        $rules = $this->processor->getRules($css);

        $this->assertCount(1, $rules);
        $this->assertInstanceOf('TijsVerkoyen\CssToInlineStyles\Css\Rule\Rule', $rules[0]);
        $this->assertEquals('a', $rules[0]->getSelector());
        $this->assertCount(1, $rules[0]->getProperties());
        $this->assertEquals('color', $rules[0]->getProperties()[0]->getName());
        $this->assertEquals('red', $rules[0]->getProperties()[0]->getValue());
        $this->assertEquals(1, $rules[0]->getOrder());
    }

    public function testCssWithBigMediaQueries(): void
    {
        $css = file_get_contents(__DIR__.'/test.css');
        $this->assertIsString($css, 'The fixture CSS file should be readable.');
        $rules = $this->processor->getRules($css);

        $this->assertCount(414, $rules);
    }

    public function testMakeSureMediaQueriesAreRemoved(): void
    {
        $css = '@media tv and (min-width: 700px) and (orientation: landscape) {.foo {display: none;}}';
        $this->assertEmpty($this->processor->getRules($css));

        $css = '@media (min-width: 700px), handheld and (orientation: landscape) {.foo {display: none;}}';
        $this->assertEmpty($this->processor->getRules($css));

        $css = '@media not screen and (color), print and (color)';
        $this->assertEmpty($this->processor->getRules($css));

        $css = '@media screen and (min-aspect-ratio: 1/1) {.foo {display: none;}}';
        $this->assertEmpty($this->processor->getRules($css));

        $css = '@media screen and (device-aspect-ratio: 16/9), screen and (device-aspect-ratio: 16/10) {.foo {display: none;}}';
        $this->assertEmpty($this->processor->getRules($css));
    }

    public function testCssWithCharset(): void
    {
        $css = <<<EOF
@charset "UTF-8";
a {
  color: red;
}
EOF;

        $rules = $this->processor->getRules($css);

        $this->assertCount(1, $rules);
        $this->assertInstanceOf('TijsVerkoyen\CssToInlineStyles\Css\Rule\Rule', $rules[0]);
        $this->assertEquals('a', $rules[0]->getSelector());
        $this->assertCount(1, $rules[0]->getProperties());
        $this->assertEquals('color', $rules[0]->getProperties()[0]->getName());
        $this->assertEquals('red', $rules[0]->getProperties()[0]->getValue());
        $this->assertEquals(1, $rules[0]->getOrder());
    }

    public function testSimpleStyleTagsInHtml(): void
    {
        $expected = 'p { color: #F00; }' . "\n";
        $this->assertEquals(
            $expected,
            $this->processor->getCssFromStyleTags(
                <<<EOF
                    <html>
    <head>
        <style>
            p { color: #F00; }
        </style>
    </head>
    <body>
        <p>foo</p>
    </body>
    </html>
EOF
            )
        );
    }

    public function testStyleTagsWithAttributeInHtml(): void
    {
        $expected = 'p { color: #F00; }' . "\n";
        $this->assertEquals(
            $expected,
            $this->processor->getCssFromStyleTags(
                <<<HTML
                    <html>
    <head>
        <style type="text/css">
            p { color: #F00; }
        </style>
    </head>
    <body>
        <p>foo</p>
    </body>
    </html>
HTML
            )
        );
    }

    public function testMultipleStyleTagsInHtml(): void
    {
        $expected = 'p { color: #F00; }' . "\n" . 'p { color: #0F0; }' . "\n";
        $this->assertEquals(
            $expected,
            $this->processor->getCssFromStyleTags(
                <<<EOF
                    <html>
    <head>
        <style>
            p { color: #F00; }
        </style>
    </head>
    <body>
        <style>
            p { color: #0F0; }
        </style>
        <p>foo</p>
    </body>
    </html>
EOF
            )
        );
    }

    public function testWeirdTagsInHtml(): void
    {
        $expected = 'p { color: #F00; }' . "\n";
        $this->assertEquals(
            $expected,
            $this->processor->getCssFromStyleTags(
                <<<HTML
                    <html>
    <head>
        <!-- weird tag name starting with style -->
        <stylesheet></stylesheet>
        <style>
            p { color: #F00; }
        </style>
    </head>
    <body>
        <p>foo</p>
    </body>
    </html>
HTML
            )
        );

    }

    public function testStyleTagsInCommentInHtml(): void
    {
        $expected = 'p { color: #F00; }' . "\n";
        $this->assertEquals(
            $expected,
            $this->processor->getCssFromStyleTags(
                <<<EOF
                    <html>
    <head>
        <style>
            p { color: #F00; }
        </style>
<!--
        <style>
            p { color: #0F0; }
        </style>
-->
<!--[if mso]>
        <style>
            p { color: #00F; }
        </style>
<![endif]-->
    </head>
    <body>
        <p>foo</p>
    </body>
    </html>
EOF
            )
        );
    }
}
