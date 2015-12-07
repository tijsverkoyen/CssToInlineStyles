<?php
namespace TijsVerkoyen\CssToInlineStyles;

use Symfony\Component\CssSelector\CssSelector;
use Symfony\Component\CssSelector\CssSelectorConverter;
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
    
    /**
     * @param  string $selector The CSS selector
     */
    public function __construct($selector)
    {
        $this->selector = $selector;
    }
    
    public function toXPath()
    {
        try {
            if (class_exists('Symfony\Component\CssSelector\CssSelectorConverter')) {
                $converter = new CssSelectorConverter();
                $query = $converter->toXPath($this->selector);
            } else {
                $query = CssSelector::toXPath($this->selector);
            }
        } catch (ExceptionInterface $e) {
            $query = null;
        }
        
        return $query;
    }
}
