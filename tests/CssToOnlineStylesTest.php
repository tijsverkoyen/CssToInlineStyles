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

        $this->assertEquals(
            '<!DOCTYPE html><html><body><p style="color: red;">foo</p></body></html>',
            $this->stripAllWhitespaces(
                $this->cssToInlineStyles->convert($html, $css)
            )
        );
    }

    private function stripAllWhitespaces($content)
    {
        $content = str_replace(
            array("\n", "\t"),
            '',
            $content
        );
        $content = preg_replace('|(\s)+<|', '<', $content);
        $content = preg_replace('|>(\s)+|', '>', $content);

        return $content;
    }
}
