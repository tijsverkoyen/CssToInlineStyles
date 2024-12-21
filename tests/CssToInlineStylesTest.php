<?php

namespace TijsVerkoyen\CssToInlineStyles\Tests;

use TijsVerkoyen\CssToInlineStyles\Css\Property\Property;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use PHPUnit\Framework\TestCase;

class CssToInlineStylesTest extends TestCase
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

    public function testNoXMLHeaderPresent(): void
    {
        $this->assertStringNotContainsString(
            '<?xml',
            $this->cssToInlineStyles->convert(
                '<!DOCTYPE html><html><body><p>foo</p></body></html>',
                ''
            )
        );
    }

    public function testApplyNoStylesOnElement(): void
    {
        $document = new \DOMDocument();
        $element = $document->createElement('a', 'foo');
        $inlineElement = $this->cssToInlineStyles->inlineCssOnElement(
            $element,
            array()
        );

        $document->appendChild($inlineElement);

        $html = $document->saveHTML();
        $this->assertIsString($html);

        $this->assertEquals('<a>foo</a>', trim($html));
    }

    public function testApplyBasicStylesOnElement(): void
    {
        $document = new \DOMDocument();
        $element = $document->createElement('a', 'foo');
        $inlineElement = $this->cssToInlineStyles->inlineCssOnElement(
            $element,
            array(
                new Property('padding', '5px'),
            )
        );

        $document->appendChild($inlineElement);

        $html = $document->saveHTML();
        $this->assertIsString($html);

        $this->assertEquals('<a style="padding: 5px;">foo</a>', trim($html));
    }

    public function testApplyBasicStylesOnElementWithInlineStyles(): void
    {
        $document = new \DOMDocument();
        $element = $document->createElement('a', 'foo');
        $element->setAttribute('style', 'color: green; border: 1px;');
        $inlineElement = $this->cssToInlineStyles->inlineCssOnElement(
            $element,
            array(
                new Property('border-bottom', '5px'),
                new Property('border', '2px'),
            )
        );

        $document->appendChild($inlineElement);

        $html = $document->saveHTML();
        $this->assertIsString($html);

        $this->assertEquals(
            '<a style="border-bottom: 5px; color: green; border: 1px;">foo</a>',
            trim($html)
        );
    }

    public function testBasicRealHTMLExample(): void
    {
        $html = '<!doctype html><html><head><style>body{color:blue}</style></head><body><p>foo</p></body></html>';
        $css = 'p { color: red; }';
        $expected = <<<EOF
<!doctype html>
<html>
<head><style>body{color:blue}</style></head>
<body style="color: blue;"><p style="color: red;">foo</p></body>
</html>
EOF;

        $this->assertEquals($expected, $this->cssToInlineStyles->convert($html, $css));
    }

    public function testSimpleElementSelector(): void
    {
        $html = '<div></div>';
        $css = 'div { display: none; }';
        $expected = '<div style="display: none;"></div>';

        $this->assertCorrectConversion($expected, $html, $css);
    }

    public function testSimpleCssSelector(): void
    {
        $html = '<a class="test-class">nodeContent</a>';
        $css = '.test-class { background-color: #aaa; text-decoration: none; }';
        $expected = '<a class="test-class" style="background-color: #aaa; text-decoration: none;">nodeContent</a>';

        $this->assertCorrectConversion($expected, $html, $css);
    }

    public function testSimpleIdSelector(): void
    {
        $html = '<div id="div1">';
        $css = '#div1 { border: 1px solid red; }';
        $expected = '<div id="div1" style="border: 1px solid red;"></div>';

        $this->assertCorrectConversion($expected, $html, $css);
    }

    public function testInlineStylesBlock(): void
    {
        $html = <<<EOF
<html>
<head>
    <style type="text/css">
      a {
        padding: 10px;
        margin: 0;
      }
    </style>
</head>
<body>
    <a></a>
</body>
</html>
EOF;
        $expected = '<a style="padding: 10px; margin: 0;"></a>';

        $this->assertCorrectConversion($expected, $html);
    }

    public function testSpecificity(): void
    {
        $html = <<<EOF
<a class="one" id="ONE" style="padding: 100px;">
  <img class="two" id="TWO" style="padding-top: 30px;">
</a>
EOF;
        $css = <<<EOF
a.one  {
  border-bottom: 2px;
  height: 20px;
}

a {
  border: 1px solid red;
  padding: 10px;
  margin: 20px;
  width: 10px !important;
  height: 10px;
}

.one {
  padding: 15px;
  width: 20px !important;
  height: 5px;
}

#ONE {
  margin: 10px;
  width: 30px;
}

img {
  padding: 0;
}

a img {
  border: none;
}

img {
  border: 2px solid green;
  padding-bottom: 20px;
}

img {
  padding: 0;
}
EOF;
        $expected = <<<EOF
<a class="one" id="ONE" style="border: 1px solid red; width: 20px !important; border-bottom: 2px; height: 20px; margin: 10px; padding: 100px;">
  <img class="two" id="TWO" style="padding-bottom: 20px; padding: 0; border: none; padding-top: 30px;">
</a>
EOF;
        $this->assertCorrectConversion($expected, $html, $css);
    }

    public function testEqualSpecificity(): void
    {
        $html = '<div class="one"></div>';
        $css = ' .one { display: inline; } a > strong {} a {} a {} a {} a {} a {} a {}a {} img { display: block; }';
        $expected = '<div class="one" style="display: inline;"></div>';

        $this->assertCorrectConversion($expected, $html, $css);
    }

    public function testInvalidSelector(): void
    {
        $html = "<p></p>";
        $css = ' p&@*$%& { display: inline; }';
        $expected = $html;

        $this->assertCorrectConversion($expected, $html, $css);
    }

    public function testHtmlEncoding(): void
    {
        $text = 'Žluťoučký kůň pije pivo nebo jak to je dál';
        $expected = $text;

        $this->assertEquals($expected, trim(strip_tags($this->cssToInlineStyles->convert($text))));
    }

    public function testSpecialCharacters(): void
    {
        $text = '1 &lt; 2';
        $expected = $text;

        $this->assertEquals($expected, trim(strip_tags($this->cssToInlineStyles->convert($text))));
    }

    public function testSpecialCharactersExplicit(): void
    {
        $text = '&amp;lt;script&amp;&gt;';
        $expected = $text;

        $this->assertEquals($expected, trim(strip_tags($this->cssToInlineStyles->convert($text))));
    }

    public function testSelfClosingTags(): void
    {
        $html = '<br>';
        $css = '';
        $expected = $html;

        $this->assertCorrectConversion($expected, $html, $css);
    }

    public function testConversionAsciiRegular(): void
    {
        $html = '~';
        $css = '';
        $expected = "<p>{$html}</p>";
        $this->assertCorrectConversion($expected, $html, $css);
    }

    public function testConversionAsciiDelete(): void
    {
        $html = "\u{007F}";
        $css = '';
        $expected = "<p>{$html}</p>";
        $this->assertCorrectConversion($expected, $html, $css);
    }

    public function testConversionLowestCodepoint(): void
    {
        $html = "\u{0080}";
        $css = '';
        $expected = "<p>{$html}</p>";
        $this->assertCorrectConversion($expected, $html, $css);
    }

    public function testConversionHighestCodepoint(): void
    {
        $html = "\u{10FFFF}";
        $css = '';
        $expected = "<p>{$html}</p>";
        $this->assertCorrectConversion($expected, $html, $css);
    }

    public function testMB4character(): void
    {
        $html = '🇳🇱';
        $css = '';
        $expected = "<p>{$html}</p>";
        $this->assertCorrectConversion($expected, $html, $css);
    }

    private function assertCorrectConversion(string $expected, string $html, ?string $css = null): void
    {
        $this->assertEquals(
            $expected,
            $this->getBodyContent(
                $this->cssToInlineStyles->convert($html, $css)
            )
        );
    }

    private function getBodyContent(string $html): ?string
    {
        $matches = array();
        preg_match('|<body>(.*)</body>|ims', $html, $matches);

        if (!isset($matches[1])) {
            return null;
        }

        return trim($matches[1]);
    }
}
