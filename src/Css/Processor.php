<?php

namespace TijsVerkoyen\CssToInlineStyles\Css;

use DOMDocument;
use DOMElement;
use TijsVerkoyen\CssToInlineStyles\Css\Rule\Processor as RuleProcessor;
use TijsVerkoyen\CssToInlineStyles\Css\Rule\Rule;

class Processor
{
    /**
     * Get the rules from a given CSS-string
     *
     * @param string $css
     * @param Rule[] $existingRules
     *
     * @return Rule[]
     */
    public function getRules($css, $existingRules = array())
    {
        $css = $this->doCleanup($css);
        $rulesProcessor = new RuleProcessor();
        $rules = $rulesProcessor->splitIntoSeparateRules($css);

        return $rulesProcessor->convertArrayToObjects($rules, $existingRules);
    }

    /**
     * Get the CSS from the style-tags in the given HTML-string
     *
     * @param string $html
     *
     * @return string
     */
    public function getCssFromStyleTags($html)
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $internalErrors = libxml_use_internal_errors(true);
        $document->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_use_internal_errors($internalErrors);

        $css = '';

        /** @var DOMElement $style */
        foreach ($document->getElementsByTagName('style') as $style) {
            $css .= $this->trimHtmlComments($style->nodeValue) . "\n";
        }

        return $css;
    }

    /**
     * @param string $css
     * @return string
     */
    protected function trimHtmlComments($css)
    {
        $css = trim($css);
        if (strncmp('<!--', $css, 4) === 0) {
            $css = substr($css, 4);
        }

        if (strlen($css) > 3 && substr($css, -3) === '-->') {
            $css = substr($css, 0, -3);
        }

        return trim($css);
    }

    /**
     * @param string $css
     *
     * @return string
     */
    private function doCleanup($css)
    {
        // remove charset
        $css = preg_replace('/@charset "[^"]++";/', '', $css);
        // remove media queries
        $css = preg_replace('/@media [^{]*+{([^{}]++|{[^{}]*+})*+}/', '', $css);

        $css = str_replace(array("\r", "\n"), '', $css);
        $css = str_replace(array("\t"), ' ', $css);
        $css = str_replace('"', '\'', $css);
        $css = preg_replace('|/\*.*?\*/|', '', $css);
        $css = preg_replace('/\s\s++/', ' ', $css);
        $css = trim($css);

        return $css;
    }
}
