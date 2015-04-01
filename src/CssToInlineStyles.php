<?php
namespace TijsVerkoyen\CssToInlineStyles;

use Symfony\Component\CssSelector\CssSelector;
use Symfony\Component\CssSelector\Exception\ExceptionInterface;

/**
 * CSS to Inline Styles class
 *
 * @author         Tijs Verkoyen <php-css-to-inline-styles@verkoyen.eu>
 * @version        1.5.4
 * @copyright      Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license        BSD License
 */
class CssToInlineStyles
{
    /**
     * The CSS to use
     *
     * @var    string
     */
    private $css;

    /**
     * The processed CSS rules
     *
     * @var    array
     */
    private $cssRules;

    /**
     * Should the generated HTML be cleaned
     *
     * @var    bool
     */
    private $cleanup = false;

    /**
     * The encoding to use.
     *
     * @var    string
     */
    private $encoding = 'UTF-8';

    /**
     * The HTML to process
     *
     * @var    string
     */
    private $html;

    /**
     * Use inline-styles block as CSS
     *
     * @var    bool
     */
    private $useInlineStylesBlock = false;

    /**
     * Strip original style tags
     *
     * @var bool
     */
    private $stripOriginalStyleTags = false;

    /**
     * Exclude the media queries from the inlined styles
     *
     * @var bool
     */
    private $excludeMediaQueries = true;

    /**
     * Creates an instance, you could set the HTML and CSS here, or load it
     * later.
     *
     * @return void
     * @param  string [optional] $html The HTML to process.
     * @param  string [optional] $css  The CSS to use.
     */
    public function __construct($html = null, $css = null)
    {
        if ($html !== null) {
            $this->setHTML($html);
        }
        if ($css !== null) {
            $this->setCSS($css);
        }
    }

    /**
     * Cleanup the generated HTML
     *
     * @return string
     * @param  string $html The HTML to cleanup.
     */
    private function cleanupHTML($html)
    {
        // remove classes
        $html = preg_replace('/(\s)+class="(.*)"(\s)*/U', ' ', $html);

        // remove IDs
        $html = preg_replace('/(\s)+id="(.*)"(\s)*/U', ' ', $html);

        // return
        return $html;
    }

    /**
     * Converts the loaded HTML into an HTML-string with inline styles based on the loaded CSS
     *
     * @return string
     * @param  bool [optional] $outputXHTML Should we output valid XHTML?
     */
    public function convert($outputXHTML = false)
    {
        // redefine
        $outputXHTML = (bool) $outputXHTML;

        // validate
        if ($this->html == null) {
            throw new Exception('No HTML provided.');
        }

        // should we use inline style-block
        if ($this->useInlineStylesBlock) {
            // init var
            $matches = array();

            // match the style blocks
            preg_match_all('|<style(.*)>(.*)</style>|isU', $this->html, $matches);

            // any style-blocks found?
            if (!empty($matches[2])) {
                // add
                foreach ($matches[2] as $match) {
                    $this->css .= trim($match) . "\n";
                }
            }
        }

        // process css
        $this->processCSS();

        // create new DOMDocument
        $document = new \DOMDocument('1.0', $this->getEncoding());

        // set error level
        $internalErrors = libxml_use_internal_errors(true);

        // load HTML
        $document->loadHTML($this->html);

        // Restore error level
        libxml_use_internal_errors($internalErrors);

        // create new XPath
        $xPath = new \DOMXPath($document);

        // any rules?
        if (!empty($this->cssRules)) {
            // loop rules
            foreach ($this->cssRules as $rule) {
                try {
                    $query = CssSelector::toXPath($rule['selector']);
                } catch (ExceptionInterface $e) {
                    continue;
                }

                // search elements
                $elements = $xPath->query($query);

                // validate elements
                if ($elements === false) {
                    continue;
                }

                // loop found elements
                foreach ($elements as $element) {
                    // no styles stored?
                    if ($element->attributes->getNamedItem(
                            'data-css-to-inline-styles-original-styles'
                        ) == null
                    ) {
                        // init var
                        $originalStyle = '';
                        if ($element->attributes->getNamedItem('style') !== null) {
                            $originalStyle = $element->attributes->getNamedItem('style')->value;
                        }

                        // store original styles
                        $element->setAttribute(
                            'data-css-to-inline-styles-original-styles',
                            $originalStyle
                        );

                        // clear the styles
                        $element->setAttribute('style', '');
                    }

                    // init var
                    $properties = array();

                    // get current styles
                    $stylesAttribute = $element->attributes->getNamedItem('style');

                    // any styles defined before?
                    if ($stylesAttribute !== null) {
                        // get value for the styles attribute
                        $definedStyles = (string) $stylesAttribute->value;

                        // split into properties
                        $definedProperties = $this->splitIntoProperties($definedStyles);
                        // loop properties
                        foreach ($definedProperties as $property) {
                            // validate property
                            if ($property == '') {
                                continue;
                            }

                            // split into chunks
                            $chunks = (array) explode(':', trim($property), 2);

                            // validate
                            if (!isset($chunks[1])) {
                                continue;
                            }

                            // loop chunks
                            $properties[$chunks[0]] = trim($chunks[1]);
                        }
                    }

                    // add new properties into the list
                    foreach ($rule['properties'] as $key => $value) {
                        // If one of the rules is already set and is !important, don't apply it,
                        // except if the new rule is also important.
                        if (
                            !isset($properties[$key])
                            || stristr($properties[$key], '!important') === false
                            || (stristr(implode('', $value), '!important') !== false)
                        ) {
                            $properties[$key] = $value;
                        }
                    }

                    // build string
                    $propertyChunks = array();

                    // build chunks
                    foreach ($properties as $key => $values) {
                        foreach ((array) $values as $value) {
                            $propertyChunks[] = $key . ': ' . $value . ';';
                        }
                    }

                    // build properties string
                    $propertiesString = implode(' ', $propertyChunks);

                    // set attribute
                    if ($propertiesString != '') {
                        $element->setAttribute('style', $propertiesString);
                    }
                }
            }

            // reapply original styles
            // search elements
            $elements = $xPath->query('//*[@data-css-to-inline-styles-original-styles]');

            // loop found elements
            foreach ($elements as $element) {
                // get the original styles
                $originalStyle = $element->attributes->getNamedItem(
                    'data-css-to-inline-styles-original-styles'
                )->value;

                if ($originalStyle != '') {
                    $originalProperties = array();
                    $originalStyles = $this->splitIntoProperties($originalStyle);

                    foreach ($originalStyles as $property) {
                        // validate property
                        if ($property == '') {
                            continue;
                        }

                        // split into chunks
                        $chunks = (array) explode(':', trim($property), 2);

                        // validate
                        if (!isset($chunks[1])) {
                            continue;
                        }

                        // loop chunks
                        $originalProperties[$chunks[0]] = trim($chunks[1]);
                    }

                    // get current styles
                    $stylesAttribute = $element->attributes->getNamedItem('style');
                    $properties = array();

                    // any styles defined before?
                    if ($stylesAttribute !== null) {
                        // get value for the styles attribute
                        $definedStyles = (string) $stylesAttribute->value;

                        // split into properties
                        $definedProperties = $this->splitIntoProperties($definedStyles);

                        // loop properties
                        foreach ($definedProperties as $property) {
                            // validate property
                            if ($property == '') {
                                continue;
                            }

                            // split into chunks
                            $chunks = (array) explode(':', trim($property), 2);

                            // validate
                            if (!isset($chunks[1])) {
                                continue;
                            }

                            // loop chunks
                            $properties[$chunks[0]] = trim($chunks[1]);
                        }
                    }

                    // add new properties into the list
                    foreach ($originalProperties as $key => $value) {
                        $properties[$key] = $value;
                    }

                    // build string
                    $propertyChunks = array();

                    // build chunks
                    foreach ($properties as $key => $values) {
                        foreach ((array) $values as $value) {
                            $propertyChunks[] = $key . ': ' . $value . ';';
                        }
                    }

                    // build properties string
                    $propertiesString = implode(' ', $propertyChunks);

                    // set attribute
                    if ($propertiesString != '') {
                        $element->setAttribute(
                            'style',
                            $propertiesString
                        );
                    }
                }

                // remove placeholder
                $element->removeAttribute(
                    'data-css-to-inline-styles-original-styles'
                );
            }
        }

        // strip original style tags if we need to
        if ($this->stripOriginalStyleTags) {
            $this->stripOriginalStyleTags($xPath);
        }

        // should we output XHTML?
        if ($outputXHTML) {
            // set formating
            $document->formatOutput = true;

            // get the HTML as XML
            $html = $document->saveXML(null, LIBXML_NOEMPTYTAG);

            // remove the XML-header
            $html = ltrim(preg_replace('/<?xml (.*)?>/', '', $html));
        } // just regular HTML 4.01 as it should be used in newsletters
        else {
            // get the HTML
            $html = $document->saveHTML();
        }

        // cleanup the HTML if we need to
        if ($this->cleanup) {
            $html = $this->cleanupHTML($html);
        }

        // return
        return $html;
    }

    /**
     * Split a style string into an array of properties.
     * The returned array can contain empty strings.
     *
     * @param string $styles ex: 'color:blue;font-size:12px;'
     * @return array an array of strings containing css property ex: array('color:blue','font-size:12px')
     */
    private function splitIntoProperties($styles) {
        $properties = (array) explode(';', $styles);

        for ($i = 0; $i < count($properties); $i++) {
            // If next property begins with base64,
            // Then the ';' was part of this property (and we should not have split on it).
            if (isset($properties[$i + 1]) && strpos($properties[$i + 1], 'base64,') === 0) {
                $properties[$i] .= ';' . $properties[$i + 1];
                $properties[$i + 1] = '';
                $i += 1;
            }
        }
        return $properties;
    }

    /**
     * Get the encoding to use
     *
     * @return string
     */
    private function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * Process the loaded CSS
     *
     * @return void
     */
    private function processCSS()
    {
        // init vars
        $css = (string) $this->css;

        // remove newlines
        $css = str_replace(array("\r", "\n"), '', $css);

        // replace double quotes by single quotes
        $css = str_replace('"', '\'', $css);

        // remove comments
        $css = preg_replace('|/\*.*?\*/|', '', $css);

        // remove spaces
        $css = preg_replace('/\s\s+/', ' ', $css);

        if ($this->excludeMediaQueries) {
            $css = preg_replace('/@media [^{]*{([^{}]|{[^{}]*})*}/', '', $css);
        }

        // rules are splitted by }
        $rules = (array) explode('}', $css);

        // init var
        $i = 1;

        // loop rules
        foreach ($rules as $rule) {
            // split into chunks
            $chunks = explode('{', $rule);

            // invalid rule?
            if (!isset($chunks[1])) {
                continue;
            }

            // set the selectors
            $selectors = trim($chunks[0]);

            // get cssProperties
            $cssProperties = trim($chunks[1]);

            // split multiple selectors
            $selectors = (array) explode(',', $selectors);

            // loop selectors
            foreach ($selectors as $selector) {
                // cleanup
                $selector = trim($selector);

                // build an array for each selector
                $ruleSet = array();

                // store selector
                $ruleSet['selector'] = $selector;

                // process the properties
                $ruleSet['properties'] = $this->processCSSProperties(
                    $cssProperties
                );

                // calculate specificity
                $ruleSet['specificity'] = Specificity::fromSelector($selector);

                // remember the order in which the rules appear
                $ruleSet['order'] = $i;

                // add into global rules
                $this->cssRules[] = $ruleSet;
            }

            // increment
            $i++;
        }

        // sort based on specificity
        if (!empty($this->cssRules)) {
            usort($this->cssRules, array(__CLASS__, 'sortOnSpecificity'));
        }
    }

    /**
     * Process the CSS-properties
     *
     * @return array
     * @param  string $propertyString The CSS-properties.
     */
    private function processCSSProperties($propertyString)
    {
        // split into chunks
        $properties = $this->splitIntoProperties($propertyString);

        // init var
        $pairs = array();

        // loop properties
        foreach ($properties as $property) {
            // split into chunks
            $chunks = (array) explode(':', $property, 2);

            // validate
            if (!isset($chunks[1])) {
                continue;
            }

            // cleanup
            $chunks[0] = trim($chunks[0]);
            $chunks[1] = trim($chunks[1]);

            // add to pairs array
            if (!isset($pairs[$chunks[0]]) ||
                !in_array($chunks[1], $pairs[$chunks[0]])
            ) {
                $pairs[$chunks[0]][] = $chunks[1];
            }
        }

        // sort the pairs
        ksort($pairs);

        // return
        return $pairs;
    }

    /**
     * Should the IDs and classes be removed?
     *
     * @return void
     * @param  bool [optional] $on Should we enable cleanup?
     */
    public function setCleanup($on = true)
    {
        $this->cleanup = (bool) $on;
    }

    /**
     * Set CSS to use
     *
     * @return void
     * @param  string $css The CSS to use.
     */
    public function setCSS($css)
    {
        $this->css = (string) $css;
    }

    /**
     * Set the encoding to use with the DOMDocument
     *
     * @return void
     * @param  string $encoding The encoding to use.
     *
     * @deprecated Doesn't have any effect
     */
    public function setEncoding($encoding)
    {
        $this->encoding = (string) $encoding;
    }

    /**
     * Set HTML to process
     *
     * @return void
     * @param  string $html The HTML to process.
     */
    public function setHTML($html)
    {
        $this->html = (string) $html;
    }

    /**
     * Set use of inline styles block
     * If this is enabled the class will use the style-block in the HTML.
     *
     * @return void
     * @param  bool [optional] $on Should we process inline styles?
     */
    public function setUseInlineStylesBlock($on = true)
    {
        $this->useInlineStylesBlock = (bool) $on;
    }

    /**
     * Set strip original style tags
     * If this is enabled the class will remove all style tags in the HTML.
     *
     * @return void
     * @param  bool [optional] $on Should we process inline styles?
     */
    public function setStripOriginalStyleTags($on = true)
    {
        $this->stripOriginalStyleTags = (bool) $on;
    }

    /**
     * Set exclude media queries
     *
     * If this is enabled the media queries will be removed before inlining the rules
     *
     * @return void
     * @param bool [optional] $on
     */
    public function setExcludeMediaQueries($on = true)
    {
        $this->excludeMediaQueries = (bool) $on;
    }

    /**
     * Strip style tags into the generated HTML
     *
     * @return string
     * @param  \DOMXPath $xPath The DOMXPath for the entire document.
     */
    private function stripOriginalStyleTags(\DOMXPath $xPath)
    {
        // Get all style tags
        $nodes = $xPath->query('descendant-or-self::style');

        foreach ($nodes as $node) {
            if ($this->excludeMediaQueries) {
                //Search for Media Queries
                preg_match_all('/@media [^{]*{([^{}]|{[^{}]*})*}/', $node->nodeValue, $mqs);

                // Replace the nodeValue with just the Media Queries
                $node->nodeValue = implode("\n", $mqs[0]);
            } else {
                // Remove the entire style tag
                $node->parentNode->removeChild($node);
            }
        }
    }

    /**
     * Sort an array on the specificity element
     *
     * @return int
     * @param  array $e1 The first element.
     * @param  array $e2 The second element.
     */
    private static function sortOnSpecificity($e1, $e2)
    {
        // Compare the specificity
        $value = $e1['specificity']->compareTo($e2['specificity']);

        // if the specificity is the same, use the order in which the element appeared
        if ($value === 0) {
            $value = $e1['order'] - $e2['order'];
        }

        return $value;
    }
}
