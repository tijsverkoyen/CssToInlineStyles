<?php

namespace TijsVerkoyen\CssToInlineStyles\Tests\Css\Rule;

use TijsVerkoyen\CssToInlineStyles\Css\Property\Property;
use TijsVerkoyen\CssToInlineStyles\Css\Rule\Rule;
use Symfony\Component\CssSelector\Node\Specificity;
use PHPUnit\Framework\TestCase;

class RuleTest extends TestCase
{
    public function testGetters(): void
    {
        $property = new Property('padding', '5px');
        $specificity = new Specificity(0, 0, 0);

        $rule = new Rule(
            'a',
            array($property),
            $specificity,
            1
        );

        $this->assertEquals('a', $rule->getSelector());
        $this->assertEquals(array($property), $rule->getProperties());
        $this->assertEquals($specificity, $rule->getSpecificity());
        $this->assertEquals(1, $rule->getOrder());
    }

    public function testExplicitSpecificityComponents(): void
    {
        $rule = new Rule(
            '#foo.bar',
            array(new Property('padding', '5px')),
            new Specificity(1, 1, 0),
            1,
            array(1, 1, 0)
        );

        $this->assertSame(1, $rule->getSpecificityA());
        $this->assertSame(1, $rule->getSpecificityB());
        $this->assertSame(0, $rule->getSpecificityC());
    }

    public function testSpecificityComponentsFallBackToDecomposedValue(): void
    {
        // When no explicit components are passed, they are decomposed from the
        // (lossy) packed value, preserving the previous behavior.
        $rule = new Rule(
            '#foo.bar a',
            array(new Property('padding', '5px')),
            new Specificity(1, 1, 1),
            1
        );

        $this->assertSame(1, $rule->getSpecificityA());
        $this->assertSame(1, $rule->getSpecificityB());
        $this->assertSame(1, $rule->getSpecificityC());
    }
}
