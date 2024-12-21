<?php

namespace TijsVerkoyen\CssToInlineStyles\Tests\Css\Rule;

use Symfony\Component\CssSelector\Node\Specificity;
use TijsVerkoyen\CssToInlineStyles\Css\Rule\Processor;
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

    public function testMostBasicRule(): void
    {
        $css = <<<EOF
            a {
                padding: 5px;
                display: block;
            }
EOF;

        $rules = $this->processor->convertToObjects($css, 1);

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

    public function testMaintainOrderOfProperties(): void
    {
        $css = <<<EOF
            div {
                width: 200px;
                _width: 211px;
            }
EOF;
        $rules = $this->processor->convertToObjects($css, 1);

        $this->assertCount(1, $rules);
        $this->assertInstanceOf('TijsVerkoyen\CssToInlineStyles\Css\Rule\Rule', $rules[0]);
        $this->assertEquals('div', $rules[0]->getSelector());
        $this->assertCount(2, $rules[0]->getProperties());
        $this->assertEquals('width', $rules[0]->getProperties()[0]->getName());
        $this->assertEquals('200px', $rules[0]->getProperties()[0]->getValue());
        $this->assertEquals('_width', $rules[0]->getProperties()[1]->getName());
        $this->assertEquals('211px', $rules[0]->getProperties()[1]->getValue());
        $this->assertEquals(1, $rules[0]->getOrder());
    }

    public function testSingleIdSelector(): void
    {
        $this->assertEquals(
            new Specificity(1, 0, 0),
            $this->processor->calculateSpecificityBasedOnASelector('#foo')
        );
    }

    public function testSingleClassSelector(): void
    {
        $this->assertEquals(
            new Specificity(0, 1, 0),
            $this->processor->calculateSpecificityBasedOnASelector('.foo')
        );
    }

    public function testSingleElementSelector(): void
    {
        $this->assertEquals(
            new Specificity(0, 0, 1),
            $this->processor->calculateSpecificityBasedOnASelector('a')
        );
    }
}
