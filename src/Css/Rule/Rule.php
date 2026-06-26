<?php

namespace TijsVerkoyen\CssToInlineStyles\Css\Rule;

use Symfony\Component\CssSelector\Node\Specificity;
use TijsVerkoyen\CssToInlineStyles\Css\Property\Property;

final class Rule
{
    /**
     * @var string
     */
    private $selector;

    /**
     * @var Property[]
     */
    private $properties;

    /**
     * @var Specificity
     */
    private $specificity;

    /**
     * @var integer
     */
    private $order;

    /**
     * @var int[]|null the [a, b, c] specificity components
     */
    private $specificityValues;

    /**
     * Rule constructor.
     *
     * @param string      $selector
     * @param Property[]  $properties
     * @param Specificity $specificity
     * @param int         $order
     * @param int[]|null  $specificityValues the [a, b, c] specificity components
     */
    public function __construct($selector, array $properties, Specificity $specificity, $order, ?array $specificityValues = null)
    {
        $this->selector = $selector;
        $this->properties = $properties;
        $this->specificity = $specificity;
        $this->order = $order;
        $this->specificityValues = $specificityValues;
    }

    /**
     * Get selector
     *
     * @return string
     */
    public function getSelector()
    {
        return $this->selector;
    }

    /**
     * Get properties
     *
     * @return Property[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Get specificity
     *
     * @return Specificity
     */
    public function getSpecificity()
    {
        return $this->specificity;
    }

    /**
     * Get order
     *
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Get the number of ID selectors (the "a" specificity component)
     *
     * @return int
     */
    public function getSpecificityA()
    {
        return $this->resolveSpecificityValues()[0];
    }

    /**
     * Get the number of class, attribute and pseudo-class selectors
     * (the "b" specificity component)
     *
     * @return int
     */
    public function getSpecificityB()
    {
        return $this->resolveSpecificityValues()[1];
    }

    /**
     * Get the number of type and pseudo-element selectors
     * (the "c" specificity component)
     *
     * @return int
     */
    public function getSpecificityC()
    {
        return $this->resolveSpecificityValues()[2];
    }

    /**
     * Returns the [a, b, c] specificity components.
     *
     * For rules created without explicit components, the (lossy) packed value is
     * decomposed as a fallback, preserving the previous sorting behavior.
     *
     * @return int[]
     */
    private function resolveSpecificityValues()
    {
        if ($this->specificityValues !== null) {
            return $this->specificityValues;
        }

        $value = $this->specificity->getValue();

        return [
            intdiv($value, 100),
            intdiv($value % 100, 10),
            $value % 10,
        ];
    }
}
