<?php

namespace TijsVerkoyen\CssToInlineStyles\Css\Specificity;

class Specificity
{
    const A_FACTOR = 100;
    const B_FACTOR = 10;
    const C_FACTOR = 1;

    /**
     * @var int
     */
    private $a;

    /**
     * @var int
     */
    private $b;

    /**
     * @var int
     */
    private $c;

    /**
     * Constructor.
     *
     * @param int $a
     * @param int $b
     * @param int $c
     */
    public function __construct($a, $b, $c)
    {
        $this->a = $a;
        $this->b = $b;
        $this->c = $c;
    }

    /**
     * @param Specificity $specificity
     *
     * @return Specificity
     */
    public function plus(Specificity $specificity)
    {
        return new self($this->a + $specificity->a, $this->b + $specificity->b, $this->c + $specificity->c);
    }

    /**
     * Returns global specificity value.
     *
     * @return int
     */
    public function getValue()
    {
        return $this->a * self::A_FACTOR + $this->b * self::B_FACTOR + $this->c * self::C_FACTOR;
    }

    /**
     * Returns -1 if the object specificity is lower than the argument,
     * 0 if they are equal, and 1 if the argument is lower.
     *
     * @param Specificity $specificity
     *
     * @return int
     */
    public function compareTo(Specificity $specificity)
    {
        if ($this->a !== $specificity->a) {
            return $this->a > $specificity->a ? 1 : -1;
        }

        if ($this->b !== $specificity->b) {
            return $this->b > $specificity->b ? 1 : -1;
        }

        if ($this->c !== $specificity->c) {
            return $this->c > $specificity->c ? 1 : -1;
        }

        return 0;
    }

    /**
     * Calculate the specificity based on a CSS Selector string,
     * Based on the patterns from premailer/css_parser by Alex Dunae
     *
     * @see https://github.com/premailer/css_parser/blob/master/lib/css_parser/regexps.rb
     * @param string $selector
     * @return static
     */
    public static function fromSelector($selector)
    {
        $idSelectorsPattern = "  \#";
        $classAttributesPseudoClassesSelectorsPattern = "  (\.[\w]+)                     # classes
                        |
                        \[(\w+)                       # attributes
                        |
                        (\:(                          # pseudo classes
                          link|visited|active
                          |hover|focus
                          |lang
                          |target
                          |enabled|disabled|checked|indeterminate
                          |root
                          |nth-child|nth-last-child|nth-of-type|nth-last-of-type
                          |first-child|last-child|first-of-type|last-of-type
                          |only-child|only-of-type
                          |empty|contains
                        ))";

        $typePseudoElementsSelectorPattern = "  ((^|[\s\+\>\~]+)[\w]+       # elements
                        |
                        \:{1,2}(                    # pseudo-elements
                          after|before
                          |first-letter|first-line
                          |selection
                        )
                      )";

        return new static(
            preg_match_all("/{$idSelectorsPattern}/ix", $selector, $matches),
            preg_match_all("/{$classAttributesPseudoClassesSelectorsPattern}/ix", $selector, $matches),
            preg_match_all("/{$typePseudoElementsSelectorPattern}/ix", $selector, $matches)
        );
    }
}
