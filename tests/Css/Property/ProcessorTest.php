<?php

namespace TijsVerkoyen\CssToInlineStyles\Tests\Css\Property;

use TijsVerkoyen\CssToInlineStyles\Css\Property\Processor;
use TijsVerkoyen\CssToInlineStyles\Css\Property\Property;
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

    public function testMostBasicProperty(): void
    {
        $propertiesString = 'padding: 0;';
        $this->assertEquals(
            array(
                'padding: 0',
            ),
            $this->processor->splitIntoSeparateProperties($propertiesString)
        );
    }

    public function testInvalidProperty(): void
    {
        $this->assertNull(
            $this->processor->convertToObject('foo:')
        );
    }

    public function testBase64ContainsSemiColon(): void
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

    public function testBuildingPropertiesString(): void
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

    public function testFaultyProperties(): void
    {
        $this->assertNull($this->processor->convertToObject('foo'));
        $this->assertNull($this->processor->convertToObject('foo:'));
    }
}
