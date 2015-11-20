<?php

namespace TijsVerkoyen\CssToInlineStyles\Tests\Css\Property;

use TijsVerkoyen\CssToInlineStyles\Css\Property\Property;

class PropertyTest extends \PHPUnit_Framework_TestCase
{
    public function testGetters()
    {
        $property = new Property('padding', '5px');

        $this->assertEquals('padding', $property->getName());
        $this->assertEquals('5px', $property->getValue());
    }

    public function testSimplePropertyToString()
    {
        $property = new Property('padding', '5px');

        $this->assertEquals(
            'padding: 5px;',
            $property->toString()
        );
    }

    public function testIfImportantIsDetected()
    {
        $property = new Property('padding', '5px !important');
        $this->assertTrue($property->isImportant());

        $property = new Property('padding', '5px');
        $this->assertFalse($property->isImportant());
    }
}
