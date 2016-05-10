<?php

namespace TijsVerkoyen\CssToInlineStyles\tests;

use TijsVerkoyen\CssToInlineStyles\Css\Property\Property;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class CssToInlineStylesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CssToInlineStyles
     */
    protected $cssToInlineStyles;

    public function setUp()
    {
        $this->cssToInlineStyles = new CssToInlineStyles();
    }

    public function tearDown()
    {
        $this->cssToInlineStyles = null;
    }

    public function testNoXMLHeaderPresent()
    {
        $this->assertNotContains(
            '<?xml',
            $this->cssToInlineStyles->convert(
                '<!DOCTYPE html><html><body><p>foo</p></body></html>',
                ''
            )
        );
    }

    public function testApplyNoStylesOnElement()
    {
        $document = new \DOMDocument();
        $element = $document->createElement('a', 'foo');
        $inlineElement = $this->cssToInlineStyles->inlineCssOnElement(
            $element,
            array()
        );

        $document->appendChild($inlineElement);
        $this->assertEquals('<a>foo</a>', trim($document->saveHTML()));
    }

    public function testApplyBasicStylesOnElement()
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

        $this->assertEquals('<a style="padding: 5px;">foo</a>', trim($document->saveHTML()));
    }

    public function testApplyBasicStylesOnElementWithInlineStyles()
    {
        $document = new \DOMDocument();
        $element = $document->createElement('a', 'foo');
        $element->setAttribute('style', 'color: green;');
        $inlineElement = $this->cssToInlineStyles->inlineCssOnElement(
            $element,
            array(
                new Property('padding', '5px'),
            )
        );

        $document->appendChild($inlineElement);

        $this->assertEquals(
            '<a style="color: green; padding: 5px;">foo</a>',
            trim($document->saveHTML())
        );
    }

    public function testBasicRealHTMLExample()
    {
        $html = '<!DOCTYPE html><html><body><p>foo</p></body></html>';
        $css = 'p { color: red; }';
        $expected = '<p style="color: red;">foo</p>';

        $this->assertCorrectConversion($expected, $html, $css);
    }

    public function testSimpleElementSelector()
    {
        $html = '<div></div>';
        $css = 'div { display: none; }';
        $expected = '<div style="display: none;"></div>';

        $this->assertCorrectConversion($expected, $html, $css);
    }

    public function testSimpleCssSelector()
    {
        $html = '<a class="test-class">nodeContent</a>';
        $css = '.test-class { background-color: #aaa; text-decoration: none; }';
        $expected = '<a class="test-class" style="background-color: #aaa; text-decoration: none;">nodeContent</a>';

        $this->assertCorrectConversion($expected, $html, $css);
    }

    public function testSimpleIdSelector()
    {
        $html = '<div id="div1">';
        $css = '#div1 { border: 1px solid red; }';
        $expected = '<div id="div1" style="border: 1px solid red;"></div>';

        $this->assertCorrectConversion($expected, $html, $css);
    }

    public function testInlineStylesBlock()
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

    public function testSpecificity()
    {
        $html = <<<EOF
<a class="one" id="ONE" style="padding: 100px;">
  <img class="two" id="TWO">
</a>
EOF;
        $css = <<<EOF
a {
  border: 1px solid red;
  padding: 10px;
  margin: 20px;
  width: 10px !important;
}

.one {
  padding: 15px;
  width: 20px !important;
}

#ONE {
  margin: 10px;
  width: 30px;
}

a img {
  border: none;
}

img {
  border: 2px solid green;
}
EOF;
        $expected = <<<EOF
<a class="one" id="ONE" style="padding: 100px; border: 1px solid red; margin: 10px; width: 20px !important;">
  <img class="two" id="TWO" style="border: none;"></img></a>
EOF;
        $this->assertCorrectConversion($expected, $html, $css);
    }

    public function testEqualSpecificity()
    {
        $html = '<div class="one"></div>';
        $css = ' .one { display: inline; } a > strong {} a {} a {} a {} a {} a {} a {}a {} img { display: block; }';
        $expected = '<div class="one" style="display: inline;"></div>';

        $this->assertCorrectConversion($expected, $html, $css);
    }

    public function testInvalidSelector()
    {
        $html = "<p></p>";
        $css = ' p&@*$%& { display: inline; }';
        $expected = $html;

        $this->assertCorrectConversion($expected, $html, $css);
    }

    public function testHtmlEncoding()
    {
        $text = 'Žluťoučký kůň pije pivo nebo jak to je dál';
        $expectedText = '&#x17D;lu&#x165;ou&#x10D;k&#xFD; k&#x16F;&#x148; pije pivo nebo jak to je d&#xE1;l';

        $this->assertEquals($expectedText, trim(strip_tags($this->cssToInlineStyles->convert($text, ''))));
    }

    private function assertCorrectConversion($expected, $html, $css = null)
    {
        $this->assertEquals(
            $expected,
            $this->getBodyContent(
                $this->cssToInlineStyles->convert($html, $css)
            )
        );
    }

    private function getBodyContent($html)
    {
        $matches = array();
        preg_match('|<body>(.*)</body>|ims', $html, $matches);

        if (!isset($matches[1])) {
            return null;
        }

        return trim($matches[1]);
    }
}
