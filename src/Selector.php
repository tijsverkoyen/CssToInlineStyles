<?php
namespace TijsVerkoyen\CssToInlineStyles;

use Symfony\Component\CssSelector\CssSelector;
use Symfony\Component\CssSelector\Exception\ExceptionInterface;

/**
 * CSS to Inline Styles Selector class.
 *
 */
class Selector
{
    /**
     * The CSS selector
     *
     * @var string
     */
    protected $selector;
    
    public function __construct($selector)
    {
        $this->selector = $selector;
    }
    
    public function toXPath()
    {
        try {
            $query = CssSelector::toXPath($this->selector);
        } catch (ExceptionInterface $e) {
            $query = null;
        }
        
        return $query;
    }
}
