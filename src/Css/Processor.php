<?php

namespace TijsVerkoyen\CssToInlineStyles\Css;

use TijsVerkoyen\CssToInlineStyles\Css\Rule\Processor as RuleProcessor;

class Processor
{
    /**
     * Get the rules from a given CSS-string
     *
     * @param string $css
     * @return array
     */
    public function getRules($css)
    {
        $css = $this->doCleanup($css);
        $rulesProcessor = new RuleProcessor();
        $rules = $rulesProcessor->splitIntoSeparateRules($css);

        return $rulesProcessor->convertArrayToObjects($rules);
    }

    /**
     * @param string $css
     * @return string
     */
    private function doCleanup($css)
    {
        // remove media queries
        $css = preg_replace('/@media [^{]*{([^{}]|{[^{}]*})*}/', '', $css);

        $css = str_replace(array("\r", "\n"), '', $css);
        $css = str_replace(array("\t"), ' ', $css);
        $css = str_replace('"', '\'', $css);
        $css = preg_replace('|/\*.*?\*/|', '', $css);
        $css = preg_replace('/\s\s+/', ' ', $css);
        $css = trim($css);

        return $css;
    }
}
