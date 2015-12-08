<?php

namespace TijsVerkoyen\CssToInlineStyles\Tests\Css\Specificity;

use TijsVerkoyen\CssToInlineStyles\Css\Specificity\Specificity;

class PropertyTest extends \PHPUnit_Framework_TestCase
{
    public function testIdBeforeClass()
    {
        $idInstance = new Specificity(1, 0, 0);
        $classInstance = new Specificity(0, 1, 0);

        $this->assertEquals(
            1,
            $idInstance->compareTo($classInstance)
        );
    }

    public function testClassBeforeElement()
    {
        $idInstance = new Specificity(0, 1, 0);
        $classInstance = new Specificity(0, 0, 1);

        $this->assertEquals(
            1,
            $idInstance->compareTo($classInstance)
        );
    }

    public function testCompareEqualItems()
    {
        $instance1 = new Specificity(1, 0, 0);
        $instance2 = new Specificity(1, 0, 0);

        $this->assertEquals(
            0,
            $instance1->compareTo($instance2)
        );
    }

    public function testSingleIdSelector()
    {
        $this->assertEquals(
            new Specificity(1, 0, 0),
            Specificity::fromSelector('#foo')
        );
    }

    public function testSingleClassSelector()
    {
        $this->assertEquals(
            new Specificity(0, 1, 0),
            Specificity::fromSelector('.foo')
        );
    }

    public function testSingleElementSelector()
    {
        $this->assertEquals(
            new Specificity(0, 0, 1),
            Specificity::fromSelector('a')
        );
    }
}
