<?php

namespace TijsVerkoyen\CssToInlineStyles\Css\Specificity;

class Specificity
{
    /**
     * The number of ID selectors in the selector
     *
     * @var int
     */
    private $numberOfIdSelectors;

    /**
     *
     * The number of class selectors, attributes selectors, and pseudo-classes in the selector
     *
     * @var int
     */
    private $numberOfClassAttributesPseudoClassSelectors;

    /**
     * The number of type selectors and pseudo-elements in the selector
     *
     * @var int
     */
    private $numberOfTypePseudoElementSelectors;

    /**
     * @param int $numberOfIdSelectors                         The number of ID selectors in the selector
     * @param int $numberOfClassAttributesPseudoClassSelectors The number of class selectors, attributes selectors, and pseudo-classes in the selector
     * @param int $numberOfTypePseudoElementSelectors          The number of type selectors and pseudo-elements in the selector
     */
    public function __construct(
        $numberOfIdSelectors = 0,
        $numberOfClassAttributesPseudoClassSelectors = 0,
        $numberOfTypePseudoElementSelectors = 0
    ) {
        $this->numberOfIdSelectors = $numberOfIdSelectors;
        $this->numberOfClassAttributesPseudoClassSelectors = $numberOfClassAttributesPseudoClassSelectors;
        $this->numberOfTypePseudoElementSelectors = $numberOfTypePseudoElementSelectors;
    }

    /**
     * Increase the current specificity by adding the three values
     *
     * @param int $numberOfIdSelectors                         The number of ID selectors in the selector
     * @param int $numberOfClassAttributesPseudoClassSelectors The number of class selectors, attributes selectors, and pseudo-classes in the selector
     * @param int $numberOfTypePseudoElementSelectors          The number of type selectors and pseudo-elements in the selector
     */
    public function increase(
        $numberOfIdSelectors,
        $numberOfClassAttributesPseudoClassSelectors,
        $numberOfTypePseudoElementSelectors
    ) {
        $this->numberOfIdSelectors += $numberOfIdSelectors;
        $this->numberOfClassAttributesPseudoClassSelectors += $numberOfClassAttributesPseudoClassSelectors;
        $this->numberOfTypePseudoElementSelectors += $numberOfTypePseudoElementSelectors;
    }

    /**
     * Get the specificity values as an array
     *
     * @return array
     */
    public function getValues()
    {
        return array(
            $this->numberOfIdSelectors,
            $this->numberOfClassAttributesPseudoClassSelectors,
            $this->numberOfTypePseudoElementSelectors
        );
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

    /**
     * Returns <0 when $specificity is greater, 0 when equal, >0 when smaller
     *
     * @param Specificity $specificity
     * @return int
     */
    public function compareTo(Specificity $specificity)
    {
        if ($this->numberOfIdSelectors !== $specificity->numberOfIdSelectors) {
            return $this->numberOfIdSelectors - $specificity->numberOfIdSelectors;
        } elseif ($this->numberOfClassAttributesPseudoClassSelectors !== $specificity->numberOfClassAttributesPseudoClassSelectors) {
            return $this->numberOfClassAttributesPseudoClassSelectors - $specificity->numberOfClassAttributesPseudoClassSelectors;
        } else {
            return $this->numberOfTypePseudoElementSelectors - $specificity->numberOfTypePseudoElementSelectors;
        }
    }
}
