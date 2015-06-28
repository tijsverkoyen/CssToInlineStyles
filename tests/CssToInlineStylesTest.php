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

    public function testMediaQueryDisabledByDefault()
    {
        $html = '<html><body><style>@media (max-width: 600px) { .test { display: none; } } h1 { color : "red" }</style><div class="test"><h1>foo</h1><h1>foo2</h1></div></body></html';
        $css = '';
        $expected = '<style>@media (max-width: 600px) { .test { display: none; } } h1 { color : "red" }</style>
<div class="test">
<h1 style="color: \'red\';">foo</h1>
<h1 style="color: \'red\';">foo2</h1>
</div>';
        $this->cssToInlineStyles->setUseInlineStylesBlock(true);
        $this->runHTMLToCSS($html, $css, $expected);
    }

    public function testMediaQueryDisabled()
    {
        $html = '<html><body><style>@media (max-width: 600px) { .test { display: none; } } h1 { color : "red" }</style><div class="test"><h1>foo</h1><h1>foo2</h1></div></body></html';
        $css = '';
        $expected = '<style>@media (max-width: 600px) { .test { display: none; } } h1 { color : "red" }</style>
<div class="test">
<h1 style="color: \'red\';">foo</h1>
<h1 style="color: \'red\';">foo2</h1>
</div>';
        $this->cssToInlineStyles->setUseInlineStylesBlock(true);
        $this->cssToInlineStyles->setStripOriginalStyleTags(false);
        $this->cssToInlineStyles->setExcludeMediaQueries(true);
        $this->runHTMLToCSS($html, $css, $expected);
    }

    public function testKeepMediaQuery()
    {
        $html = UTF8::file_get_contents(__DIR__ . '/test2Html.html');
        $css = UTF8::file_get_contents(__DIR__ . '/test2Css.css');
        $expected = UTF8::file_get_contents(__DIR__ . '/test2Html_result.html');

        $cssToInlineStyles = $this->cssToInlineStyles;
        $cssToInlineStyles->setExcludeConditionalInlineStylesBlock(false);
        $cssToInlineStyles->setUseInlineStylesBlock(true);
        $cssToInlineStyles->setStripOriginalStyleTags(true);
        $cssToInlineStyles->setExcludeMediaQueries(true);
        $cssToInlineStyles->setHTML($html);
        $cssToInlineStyles->setCSS($css);
        $actual = $cssToInlineStyles->convert();
        $this->assertEquals($expected, $actual);
    }

    public function testMediaQuery()
    {
        $html = '<html><body><style>@media (max-width: 600px) { .test { display: none; } } h1 { color : "red" }</style><div class="test"><h1>foo</h1><h1>foo2</h1></div></body></html';
        $css = '';
        $expected = '<div class="test">
<h1 style="color: \'red\';">foo</h1>
<h1 style="color: \'red\';">foo2</h1>
</div>';
        $this->cssToInlineStyles->setUseInlineStylesBlock(true);
        $this->cssToInlineStyles->setStripOriginalStyleTags(true);
        $this->cssToInlineStyles->setExcludeMediaQueries(false);
        $this->runHTMLToCSS($html, $css, $expected);
    }

    public function testMediaQueryV2()
    {
        $html = '<html><body><style>@media (max-width: 600px) { .test { display: none; } } h1 { color : "red" }</style><div class="test"><h1>foo</h1><h1>foo2</h1></div></body></html';
        $css = '';
        $expected = '<style>@media (max-width: 600px) { .test { display: none; } }</style>
<div class="test">
<h1 style="color: \'red\';">foo</h1>
<h1 style="color: \'red\';">foo2</h1>
</div>';
        $this->cssToInlineStyles->setUseInlineStylesBlock(true);
        $this->cssToInlineStyles->setStripOriginalStyleTags(true);
        $this->cssToInlineStyles->setExcludeMediaQueries(true);
        $this->runHTMLToCSS($html, $css, $expected);
    }

    public function testMediaQueryV3()
    {
        $html = '<html><body><style>@media (max-width: 600px) { .test { display: none; } } @media (max-width: 500px) { .test { top: 1rem; } } h1 { color : "red" }</style><div class="test"><h1>foo</h1><h1>foo2</h1></div></body></html';
        $css = '';
        $expected = '<style>@media (max-width: 600px) { .test { display: none; } }
@media (max-width: 500px) { .test { top: 1rem; } }</style>
<div class="test">
<h1 style="color: \'red\';">foo</h1>
<h1 style="color: \'red\';">foo2</h1>
</div>';
        $this->cssToInlineStyles->setUseInlineStylesBlock(true);
        $this->cssToInlineStyles->setStripOriginalStyleTags(true);
        $this->cssToInlineStyles->setExcludeMediaQueries(true);
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
        $this->cssToInlineStyles->setUseInlineStylesBlock(true);
        $this->cssToInlineStyles->setHTML($html);
        $actual = $this->findAndSaveNode($this->cssToInlineStyles->convert(), '//a');
        $this->assertEquals($expected, $actual);
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
        $this->assertEquals($expected, $actual);

        $this->assertNull($this->findAndSaveNode($actual, '//style'));
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

        $this->assertContains('<img></img>', $actual);
    }

    public function testCleanup()
    {
        $html = '<div id="id" class="className"> id="foo" class="bar" </div>';
        $css = ' #id { display: inline; } .className { margin-right: 10px; }';
        $expected = '<div style="margin-right: 10px; display: inline;"> id="foo" class="bar" </div>';
        $this->cssToInlineStyles->setCleanup();
        $this->runHTMLToCSS($html, $css, $expected);
    }

    public function testCleanupWithoutStyleTagCleanup()
    {
        $html = '<style>div { top: 1em; }</style><style>div { left: 1em; }</style><div id="id" class="className"> id="foo" class="bar" </div>';
        $css = ' #id { display: inline; } .className { margin-right: 10px; }';
        $expected = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<html>
<head>
<style>div { top: 1em; }</style>
<style>div { left: 1em; }</style>
</head>
<body><div style="top: 1em; left: 1em; margin-right: 10px; display: inline;"> id="foo" class="bar" </div></body>
</html>
';
        $this->cssToInlineStyles->setUseInlineStylesBlock(true);
        $this->cssToInlineStyles->setStripOriginalStyleTags(false);
        $this->cssToInlineStyles->setCleanup(true);
        $this->cssToInlineStyles->setHTML($html);
        $this->cssToInlineStyles->setCSS($css);
        $actual = $this->cssToInlineStyles->convert();
        $this->assertEquals($expected, $actual);
    }

    public function testCleanupWithStyleTagCleanup()
    {
        $html = '<style class="cleanup">div { top: 1em; }</style><style>.cleanup { top: 1em; } div { left: 1em; }</style><div id="id" class="className"> id="foo" class="bar" </div>';
        $css = ' #id { display: inline; } .className { margin-right: 10px; }';
        $expected = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<html>
<head><style>.cleanup { top: 1em; } div { left: 1em; }</style></head>
<body><div style="left: 1em; margin-right: 10px; display: inline;"> id="foo" class="bar" </div></body>
</html>
';
        $this->cssToInlineStyles->setUseInlineStylesBlock(true);
        $this->cssToInlineStyles->setStripOriginalStyleTags(false);
        $this->cssToInlineStyles->setCleanup(true);
        $this->cssToInlineStyles->setHTML($html);
        $this->cssToInlineStyles->setCSS($css);
        $actual = $this->cssToInlineStyles->convert();
        $this->assertEquals($expected, $actual);
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

    public function testBoilerplateEmail()
    {
      $html = UTF8::file_get_contents(__DIR__ . '/test1Html.html');
      $css = '';
      $expected = UTF8::file_get_contents(__DIR__ . '/test1Html_result.html');

      $cssToInlineStyles = $this->cssToInlineStyles;
      $cssToInlineStyles->setUseInlineStylesBlock(true);
      $cssToInlineStyles->setHTML($html);
      $cssToInlineStyles->setCSS($css);
      $actual = $cssToInlineStyles->convert();
      $this->assertEquals($expected, $actual);
    }

    public function testEncodingIso()
    {
        $testString = UTF8::file_get_contents(__DIR__ . '/test1Latin.txt');
        $this->assertContains('Iñtërnâtiônàlizætiøn', $testString);

        $html = '<p>' . $testString . '</p>';
        $css = '';
        $expected = '';

        $this->cssToInlineStyles->setEncoding('ISO-8859-1');
        $result = $this->runHTMLToCSS($html, $css, $expected);

        $this->assertContains('<p>Hírek', $result);
        $this->assertContains('Iñtërnâtiônàlizætiøn', $result);
    }

    public function testEncodingUtf8()
    {
        $testString = UTF8::file_get_contents(__DIR__ . '/test1Utf8.txt');
        $this->assertContains('Iñtërnâtiônàlizætiøn', $testString);

        $html = '<p>' . $testString . '</p>';
        $css = '';
        $expected = '';

        $this->cssToInlineStyles->setEncoding('UTF-8');
        $result = $this->runHTMLToCSS($html, $css, $expected);

        $this->assertContains('<p>Hírek', $result);
        $this->assertContains('Iñtërnâtiônàlizætiøn', $result);
    }

    public function testXMLHeaderIsRemoved()
    {
        $html = '<html><body><p>Foo</p></body>';

        $this->cssToInlineStyles->setHTML($html);
        $this->cssToInlineStyles->setCSS('');

        $this->assertNotContains('<?xml', $this->cssToInlineStyles->convert(true));
    }

    public function testXMLHeaderIsRemovedv2()
    {
        $html = '<html><body><p>Foo</p></body>';
        $expected = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<html>
  <body>
    <p>Foo</p>
  </body>
</html>
';
        $this->cssToInlineStyles->setHTML($html);
        $this->cssToInlineStyles->setCSS('');
        $actual = $this->cssToInlineStyles->convert(true);
        $this->assertEquals($expected, $actual);
    }

    /**
     * run html-to-css (and test it, if we set the "expected"-variable)
     *
     * @param string $html
     * @param string $css
     * @param string $expected
     * @param bool   $asXHTML
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
            $this->assertEquals($expected, $actual);
        }

        return $actual;
    }

    /**
     * stripe html-body
     *
     * @param string $html
     * @param bool   $asXHTML
     *
     * @return string
     */
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

    /**
     * find and save node
     *
     * @param string $html
     * @param string $query
     *
     * @return null|string
     */
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
