<?php

namespace TijsVerkoyen\CssToInlineStyles\tests;

use \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use voku\helper\UTF8;

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

    public function teardown()
    {
        $this->cssToInlineStyles = null;
    }

    public function testSimpleElementSelector()
    {
        $html = '<div></div>';
        $css = 'div { display: none; }';
        $expected = '<div style="display: none;"></div>';
        $this->runHTMLToCSS($html, $css, $expected);
    }

    public function testSimpleCssSelector()
    {
        $html = '<a class="test-class">nodeContent</a>';
        $css = '.test-class { background-color: #aaa; text-decoration: none; }';
        $expected = '<a class="test-class" style="background-color: #aaa; text-decoration: none;">nodeContent</a>';
        $this->runHTMLToCSS($html, $css, $expected);
    }

    public function testSimpleIdSelector()
    {
        $html = '<img id="IMG1">';
        $css = '#IMG1 { border: 1px solid red; }';
        $expected = '<img id="IMG1" style="border: 1px solid red;">';
        $this->runHTMLToCSS($html, $css, $expected);
    }

    public function testInlineStylesBlock()
    {
        $html = <<<EOF
<style type="text/css">
  a {
    padding: 10px;
    margin: 0;
  }
</style>
<a></a>
EOF;
        $expected = '<a style="margin: 0; padding: 10px;"></a>';
        $this->cssToInlineStyles->setUseInlineStylesBlock();
        $this->cssToInlineStyles->setHTML($html);
        $actual = $this->findAndSaveNode($this->cssToInlineStyles->convert(), '//a');
        self::assertEquals($expected, $actual);
    }

    public function testStripOriginalStyleTags()
    {
        $html = <<<EOF
<style type="text/css">
  a {
    padding: 10px;
    margin: 0;
  }
</style>
<a></a>
EOF;
        $expected = '<a></a>';
        $this->cssToInlineStyles->setStripOriginalStyleTags();
        $this->cssToInlineStyles->setHTML($html);
        $actual = $this->findAndSaveNode($this->cssToInlineStyles->convert(), '//a');
        self::assertEquals($expected, $actual);

        self::assertNull($this->findAndSaveNode($actual, '//style'));
    }

    public function testSpecificity()
    {
        $html = '<a class="one" id="ONE"><img class="two" id="TWO"></a>';
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
img {
  border: 2px solid green;
}
a img {
  border: none;
}
EOF;
        $expected = '<a class="one" id="ONE" style="border: 1px solid red; margin: 10px; padding: 15px; width: 20px !important;"><img class="two" id="TWO" style="border: none;"></a>';
        $this->runHTMLToCSS($html, $css, $expected);
    }

    public function testMergeOriginalStyles()
    {
        $html = '<p style="padding: 20px; margin-top: 10px;">text</p>';
        $css = <<<EOF
p {
  margin-top: 20px;
  text-indent: 1em;
}
EOF;
        $expected = '<p style="margin-top: 10px; text-indent: 1em; padding: 20px;">text</p>';
        $this->runHTMLToCSS($html, $css, $expected);
    }

    public function testXHTMLOutput()
    {
        $html = '<a><img></a>';
        $css = 'a { display: block; }';

        $this->cssToInlineStyles->setHTML($html);
        $this->cssToInlineStyles->setCSS($css);
        $actual = $this->cssToInlineStyles->convert(true);

        self::assertContains('<img></img>', $actual);
    }

    public function testCleanup()
    {
        $html = '<div id="id" class="className"></div>';
        $css = ' #id { display: inline; } .className { margin-right: 10px; }';
        $expected = '<div style="margin-right: 10px; display: inline;"></div>';
        $this->cssToInlineStyles->setCleanup();
        $this->runHTMLToCSS($html, $css, $expected);
    }

    public function testEqualSpecificity()
    {
        $html = '<img class="one">';
        $css = ' .one { display: inline; } a > strong {} a {} a {} a {} a {} a {} a {}a {} img { display: block; }';
        $expected = '<img class="one" style="display: inline;">';
        $this->runHTMLToCSS($html, $css, $expected);
    }

    public function testInvalidSelector()
    {
        $html = "<p></p>";
        $css = ' p&@*$%& { display: inline; }';
        $expected = $html;
        $this->runHTMLToCSS($html, $css, $expected);
    }

    public function testEncodingIso()
    {
        $testString = UTF8::file_get_contents(__DIR__ . '/test1Latin.txt');
        self::assertContains('Iñtërnâtiônàlizætiøn', $testString);

        $html = '<p>' . $testString . '</p>';
        $css = '';
        $expected = '';

        $this->cssToInlineStyles->setEncoding('ISO-8859-1');
        $result = $this->runHTMLToCSS($html, $css, $expected);

        self::assertContains('<p>Hírek', $result);
        self::assertContains('Iñtërnâtiônàlizætiøn', $result);
    }

    public function testEncodingUtf8()
    {
        $testString = UTF8::file_get_contents(__DIR__ . '/test1Utf8.txt');
        self::assertContains('Iñtërnâtiônàlizætiøn', $testString);

        $html = '<p>' . $testString . '</p>';
        $css = '';
        $expected = '';

        $this->cssToInlineStyles->setEncoding('UTF-8');
        $result = $this->runHTMLToCSS($html, $css, $expected);

        self::assertContains('<p>Hírek', $result);
        self::assertContains('Iñtërnâtiônàlizætiøn', $result);
    }

    public function testXMLHeaderIsRemoved()
    {
        $html = '<html><body><p>Foo</p></body>';

        $this->cssToInlineStyles->setHTML($html);
        $this->cssToInlineStyles->setCSS('');

        self::assertNotContains('<?xml', $this->cssToInlineStyles->convert(true));
    }

    /**
     * @param            $html
     * @param            $css
     * @param            $expected
     * @param bool|false $asXHTML
     *
     * @return string
     * @throws \TijsVerkoyen\CssToInlineStyles\Exception
     */
    private function runHTMLToCSS($html, $css, $expected, $asXHTML = false)
    {
        $cssToInlineStyles = $this->cssToInlineStyles;
        $cssToInlineStyles->setHTML($html);
        $cssToInlineStyles->setCSS($css);
        $output = $cssToInlineStyles->convert($asXHTML);
        $actual = $this->stripBody($output, $asXHTML);

        if ($expected) {
            self::assertEquals($expected, $actual);
        }

        return $actual;
    }

    private function stripBody($html, $asXHTML = false)
    {
        $dom = new \DOMDocument();
        /*if ($asXHTML) {
          $dom->loadXML($html);
        } else {*/
        $dom->loadHTML($html);
        /*}*/
        $xpath = new \DOMXPath($dom);
        $nodelist = $xpath->query('//body/*');
        $result = '';
        for ($i = 0; $i < $nodelist->length; $i++) {
            $node = $nodelist->item($i);
            if ($asXHTML) {
                $result .= $dom->saveXML($node);
            } else {
                $result .= $dom->saveHTML($node);
            }
        }

        return $result;
    }

    private function findAndSaveNode($html, $query)
    {
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $nodelist = $xpath->query($query);
        if ($nodelist->length > 0) {
            $node = $nodelist->item(0);

            return $dom->saveHTML($node);
        } else {
            return null;
        }
    }
}
