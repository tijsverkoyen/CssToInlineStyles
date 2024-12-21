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
}
