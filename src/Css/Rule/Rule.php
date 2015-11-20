<?php

namespace TijsVerkoyen\CssToInlineStyles\Css\Rule;

final class Rule
{
    /**
     * @var string
     */
    private $selector;

    /**
     * @var array
     */
    private $properties;

    private $specificity;

    /**
     * @var integer
     */
    private $order;

    /**
     * Rule constructor.
     * @param string $selector
     * @param array  $properties
     * @param        $specificity
     * @param int    $order
     */
    public function __construct($selector, array $properties, $specificity, $order)
    {
        $this->selector = $selector;
        $this->properties = $properties;
        $this->specificity = $specificity;
        $this->order = $order;
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
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Get specificity
     *
     * @return mixed
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
}
