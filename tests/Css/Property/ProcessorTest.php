<?php

namespace TijsVerkoyen\CssToInlineStyles\Tests\Css\Property;

use TijsVerkoyen\CssToInlineStyles\Css\Property\Processor;
use TijsVerkoyen\CssToInlineStyles\Css\Property\Property;

class ProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Processor
     */
    protected $processor;

    public function setUp()
    {
        $this->processor = new Processor();
    }

    public function tearDown()
    {
        $this->processor = null;
    }

    public function testMostBasicProperty()
    {
        $propertiesString = 'padding: 0;';
        $this->assertEquals(
            array(
                'padding: 0',
            ),
            $this->processor->splitIntoSeparateProperties($propertiesString)
        );
    }

    public function testInvalidProperty()
    {
        $this->assertNull(
            $this->processor->convertToObject('foo:')
        );
    }

    public function testBase64ContainsSemiColon()
    {
        $propertiesString = <<<EOF
            background:
                url(data:image/gif;base64,R0lGODlhEAAQAMQAAORHHOVSKudfOulrSOp3WOyDZu6QdvCchPGolfO0o/XBs/fNwfjZ0frl3/zy7////wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAkAABAALAAAAAAQABAAAAVVICSOZGlCQAosJ6mu7fiyZeKqNKToQGDsM8hBADgUXoGAiqhSvp5QAnQKGIgUhwFUYLCVDFCrKUE1lBavAViFIDlTImbKC5Gm2hB0SlBCBMQiB0UjIQA7)
                no-repeat
                left center;
            padding: 5px 0 5px 25px;
EOF;

        $this->assertEquals(
            array(
                'background: url(data:image/gif;base64,R0lGODlhEAAQAMQAAORHHOVSKudfOulrSOp3WOyDZu6QdvCchPGolfO0o/XBs/fNwfjZ0frl3/zy7////wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAkAABAALAAAAAAQABAAAAVVICSOZGlCQAosJ6mu7fiyZeKqNKToQGDsM8hBADgUXoGAiqhSvp5QAnQKGIgUhwFUYLCVDFCrKUE1lBavAViFIDlTImbKC5Gm2hB0SlBCBMQiB0UjIQA7) no-repeat left center',
                'padding: 5px 0 5px 25px',
            ),
            $this->processor->splitIntoSeparateProperties($propertiesString)
        );
    }

    public function testBuildingPropertiesString()
    {
        $properties = array(
            new Property('padding', '5px'),
            new Property('display', 'block'),
        );

        $this->assertEquals(
            'padding: 5px; display: block;',
            $this->processor->buildPropertiesString($properties)
        );
    }

    public function testFaultyProperties()
    {
        $this->assertNull($this->processor->convertToObject('foo'));
        $this->assertNull($this->processor->convertToObject('foo:'));
    }
}
