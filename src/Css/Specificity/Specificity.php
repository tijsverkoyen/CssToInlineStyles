<?php

namespace TijsVerkoyen\CssToInlineStyles\Css\Specificity;

class Specificity extends \Symfony\Component\CssSelector\Node\Specificity
{
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
