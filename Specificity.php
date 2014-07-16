<?php
namespace TijsVerkoyen\CssToInlineStyles;

/**
 * CSS to Inline Styles Specificity class.
 *
 * Compare specificity based on the CSS3 spec.
 *
 * @see http://www.w3.org/TR/selectors/#specificity
 *
 */
class Specificity{

    /**
     * The number of ID selectors in the selector
     *
     * @var int
     */
    private $a;

    /**
     *
     * The number of class selectors, attributes selectors, and pseudo-classes in the selector
     *
     * @var int
     */
    private $b;

    /**
     * The number of type selectors and pseudo-elements in the selector
     *
     * @var int
     */
    private $c;

    /**
     * @param int $a The number of ID selectors in the selector
     * @param int $b The number of class selectors, attributes selectors, and pseudo-classes in the selector
     * @param int $c The number of type selectors and pseudo-elements in the selector
     */
    public function __construct($a=0, $b=0, $c=0)
    {
        $this->a = $a;
        $this->b = $b;
        $this->c = $c;
    }

    /**
     * Increase the current specificity by adding the three values
     *
     * @param int $a The number of ID selectors in the selector
     * @param int $b The number of class selectors, attributes selectors, and pseudo-classes in the selector
     * @param int $c The number of type selectors and pseudo-elements in the selector
     */
    public function increase($a, $b, $c)
    {
        $this->a += $a;
        $this->b += $b;
        $this->c += $c;
    }

    /**
     * Calculate the specificity based on a CSS Selector string
     *
     * @param string $selector
     * @return static
     */
    public static function fromSelector($selector)
    {
        // cleanup selector
        $selector = str_replace(array('>', '+'), array(' > ', ' + '), $selector);

        // create a new instance
        $specificity = new static;

        // split the selector into chunks based on spaces
        $chunks = explode(' ', $selector);

        // loop chunks
        foreach ($chunks as $chunk) {
            // an ID is important, so give it a high specificity
            if(strstr($chunk, '#') !== false) $specificity->increase(1,0,0);

            // classes are more important than a tag, but less important then an ID
            elseif(strstr($chunk, '.')) $specificity->increase(0,1,0);

            // anything else isn't that important
            else $specificity->increase(0,0,1);
        }

        // return
        return $specificity;

    }

    /**
     * Returns <0 when $specificity is greater, 0 when equal, >0 when smaller
     *
     * @param Specificity $specificity
     * @return int
     */
    public function compare(Specificity $specificity)
    {
        if ($this->a !== $specificity->a) {
            return $this->a - $specificity->a;
        } elseif ($this->b !== $specificity->b) {
            return $this->b - $specificity->b;
        } else {
            return $this->c - $specificity->c;
        }
    }
}