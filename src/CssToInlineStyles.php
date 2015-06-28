<?php
namespace TijsVerkoyen\CssToInlineStyles;

use voku\helper\UTF8;
use Symfony\Component\CssSelector\CssSelector;
use Symfony\Component\CssSelector\Exception\ExceptionInterface;

/**
 * CSS to Inline Styles class
 *
 * @author     Tijs Verkoyen <php-css-to-inline-styles@verkoyen.eu>
 * @author     Lars Moellelen <lars@moelleken.org>
 *
 * @version    1.5.4
 * @license    BSD License
 * @since      1.0.0
 */
class CssToInlineStyles
{

    /**
     * The CSS to use
     *
     * @var  string
     */
    private $css;

    /**
     * The processed CSS rules
     *
     * @var  array
     */
    private $cssRules;

    /**
     * Should the generated HTML be cleaned
     *
     * @var  bool
     */
    private $cleanup = false;

    /**
     * The encoding to use.
     *
     * @var  string
     */
    private $encoding = 'UTF-8';

    /**
     * The HTML to process
     *
     * @var  string
     */
    private $html;

    /**
     * Use inline-styles block as CSS
     *
     * @var  bool
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
     * and keep media queries for styles blocks
     *
     * @var bool
     */
    private $excludeMediaQueries = true;

    /**
     * css media queries regular expression
     *
     * @var string
     */
    private $cssMediaQueriesRegEx = '/@media [^{]*{([^{}]|{[^{}]*})*}/';

    /**
     * Creates an instance, you could set the HTML and CSS here, or load it
     * later.
     *
     * @param  null|string $html [optional] The HTML to process.
     * @param  null|string $css  [optional] The CSS to use.
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
     * Remove id and class attributes.
     *
     * @param  \DOMXPath $xPath The DOMXPath for the entire document.
     *
     * @return string
     */
    private function cleanupHTML(\DOMXPath $xPath)
    {
        $nodes = $xPath->query('//@class | //@id');
        foreach ($nodes as $node) {
            $node->ownerElement->removeAttributeNode($node);
        }
    }

    /**
     * Converts the loaded HTML into an HTML-string with inline styles based on the loaded CSS
     *
     * @return string
     *
     * @param  bool [optional] $outputXHTML Should we output valid XHTML?
     *
     * @throws Exception
     */
    public function convert($outputXHTML = false)
    {
        // redefine
        $outputXHTML = (bool)$outputXHTML;

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
                    $this->css .= UTF8::trim($match) . "\n";
                }
            }
        }

        // process css
        $this->processCSS();

        // create new DOMDocument
        $document = $this->createDOMDocument();

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

                /**
                 * @var $element \DOMElement
                 */

                // search elements
                $elements = $xPath->query($query);

                // validate elements
                if ($elements === false) {
                    continue;
                }

                // loop found elements
                foreach ($elements as $element) {
                    // no styles stored?
                    if ($element->attributes->getNamedItem('data-css-to-inline-styles-original-styles') == null) {

                        // init var
                        $originalStyle = '';

                        if ($element->attributes->getNamedItem('style') !== null) {
                            $originalStyle = $element->attributes->getNamedItem('style')->value;
                        }

                        // store original styles
                        $element->setAttribute('data-css-to-inline-styles-original-styles', $originalStyle);

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
                        $definedStyles = (string)$stylesAttribute->value;

                        // split into properties
                        $definedProperties = $this->splitIntoProperties($definedStyles);

                        // loop properties
                        foreach ($definedProperties as $property) {
                            // validate property
                            if ($property == '') {
                                continue;
                            }

                            // split into chunks
                            $chunks = (array)explode(':', UTF8::trim($property), 2);

                            // validate
                            if (!isset($chunks[1])) {
                                continue;
                            }

                            // loop chunks
                            $properties[$chunks[0]] = UTF8::trim($chunks[1]);
                        }
                    }

                    // add new properties into the list
                    foreach ($rule['properties'] as $key => $value) {
                        // If one of the rules is already set and is !important, don't apply it,
                        // except if the new rule is also important.
                        if (
                            !isset($properties[$key])
                            ||
                            UTF8::stristr($properties[$key], '!important') === false
                            ||
                            (UTF8::stristr(implode('', $value), '!important') !== false)
                        ) {
                            $properties[$key] = $value;
                        }
                    }

                    // build string
                    $propertyChunks = array();

                    // build chunks
                    foreach ($properties as $key => $values) {
                        foreach ((array)$values as $value) {
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
                $originalStyle = $element->attributes->getNamedItem('data-css-to-inline-styles-original-styles')->value;

                if ($originalStyle != '') {
                    $originalProperties = array();
                    $originalStyles = $this->splitIntoProperties($originalStyle);

                    foreach ($originalStyles as $property) {
                        // validate property
                        if ($property == '') {
                            continue;
                        }

                        // split into chunks
                        $chunks = (array)explode(':', UTF8::trim($property), 2);

                        // validate
                        if (!isset($chunks[1])) {
                            continue;
                        }

                        // loop chunks
                        $originalProperties[$chunks[0]] = UTF8::trim($chunks[1]);
                    }

                    // get current styles
                    $stylesAttribute = $element->attributes->getNamedItem('style');
                    $properties = array();

                    // any styles defined before?
                    if ($stylesAttribute !== null) {
                        // get value for the styles attribute
                        $definedStyles = (string)$stylesAttribute->value;

                        // split into properties
                        $definedProperties = $this->splitIntoProperties($definedStyles);

                        // loop properties
                        foreach ($definedProperties as $property) {
                            // validate property
                            if ($property == '') {
                                continue;
                            }

                            // split into chunks
                            $chunks = (array)explode(':', UTF8::trim($property), 2);

                            // validate
                            if (!isset($chunks[1])) {
                                continue;
                            }

                            // loop chunks
                            $properties[$chunks[0]] = UTF8::trim($chunks[1]);
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
                        foreach ((array)$values as $value) {
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

                // remove placeholder
                $element->removeAttribute('data-css-to-inline-styles-original-styles');
            }
        }

        // strip original style tags if we need to
        if ($this->stripOriginalStyleTags === true) {
            $this->stripOriginalStyleTags($xPath);
        }

        // cleanup the HTML if we need to
        if ($this->cleanup === true) {
          $this->cleanupHTML($xPath);
        }

        // should we output XHTML?
        if ($outputXHTML === true) {
            // set formatting
            $document->formatOutput = true;

            // get the HTML as XML
            $html = $document->saveXML(null, LIBXML_NOEMPTYTAG);

            // remove the XML-header
            $html = UTF8::ltrim(preg_replace('/<\?xml.*?>/', '', $html));
        } // just regular HTML 4.01 as it should be used in newsletters
        else {
            // get the HTML
            $html = $document->saveHTML();
        }

        // return
        return $html;
    }

    /**
     * create DOMDocument from HTML
     *
     * @return \DOMDocument
     */
    private function createDOMDocument()
    {
        // create new DOMDocument
        $document = new \DOMDocument('1.0', $this->getEncoding());

        // DOMDocument settings
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;

        // set error level
        $internalErrors = libxml_use_internal_errors(true);

        // load HTML
        //
        // with UTF-8 hack: http://php.net/manual/en/domdocument.loadhtml.php#95251
        //
        $document->loadHTML('<?xml encoding="' . $this->getEncoding() . '">' . $this->html);

        // remove the "xml-encoding" hack
        foreach ($document->childNodes as $child) {
            if ($child->nodeType == XML_PI_NODE) {
                $document->removeChild($child);
            }
        }

        // set encoding
        $document->encoding = $this->getEncoding();

        // restore error level
        libxml_use_internal_errors($internalErrors);

        return $document;
    }

    /**
     * Split a style string into an array of properties.
     * The returned array can contain empty strings.
     *
     * @param string $styles ex: 'color:blue;font-size:12px;'
     *
     * @return array an array of strings containing css property ex: array('color:blue','font-size:12px')
     */
    private function splitIntoProperties($styles)
    {
        $properties = (array)explode(';', $styles);
        for ($i = 0; $i < count($properties); $i++) {
            // If next property begins with base64,
            // Then the ';' was part of this property (and we should not have split on it).
            if (isset($properties[$i + 1]) && UTF8::strpos($properties[$i + 1], 'base64,') === 0) {
                $properties[$i] .= ';' . $properties[$i + 1];
                $properties[$i + 1] = '';
                ++$i;
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
        $css = (string)$this->css;

        // remove newlines
        $css = str_replace(array("\r", "\n"), '', $css);

        // replace double quotes by single quotes
        $css = str_replace('"', '\'', $css);

        // remove comments
        $css = preg_replace('|/\*.*?\*/|', '', $css);

        // remove spaces
        $css = preg_replace('/\s\s+/', ' ', $css);

        // remove css media queries
        $css = $this->stripeMediaQueries($css);

        // rules are splitted by }
        $rules = (array)explode('}', $css);

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
            $selectors = UTF8::trim($chunks[0]);

            // get cssProperties
            $cssProperties = UTF8::trim($chunks[1]);

            // split multiple selectors
            $selectors = (array)explode(',', $selectors);

            // loop selectors
            foreach ($selectors as $selector) {
                // cleanup
                $selector = UTF8::trim($selector);

                // build an array for each selector
                $ruleSet = array();

                // store selector
                $ruleSet['selector'] = $selector;

                // process the properties
                $ruleSet['properties'] = $this->processCSSProperties($cssProperties);


                // calculate specificity
                $ruleSet['specificity'] = Specificity::fromSelector($selector);

                // remember the order in which the rules appear
                $ruleSet['order'] = $i;

                // add into rules
                $this->cssRules[] = $ruleSet;

                // increment
                $i++;
            }
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
     *
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
            $chunks = (array)explode(':', $property, 2);

            // validate
            if (!isset($chunks[1])) {
                continue;
            }

            // cleanup
            $chunks[0] = UTF8::trim($chunks[0]);
            $chunks[1] = UTF8::trim($chunks[1]);

            // add to pairs array
            if (
                !isset($pairs[$chunks[0]]) ||
                !in_array($chunks[1], $pairs[$chunks[0]], true)
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
     *
     * @param  bool [optional] $on Should we enable cleanup?
     */
    public function setCleanup($on = true)
    {
        $this->cleanup = (bool)$on;
    }

    /**
     * Set CSS to use
     *
     * @return void
     *
     * @param  string $css The CSS to use.
     */
    public function setCSS($css)
    {
        $this->css = (string)$css;
    }

    /**
     * Set the encoding to use with the DOMDocument
     *
     * @return void
     *
     * @param  string $encoding The encoding to use.
     *
     * @deprecated Doesn't have any effect
     */
    public function setEncoding($encoding)
    {
        $this->encoding = (string)$encoding;
    }

    /**
     * Set HTML to process
     *
     * @return void
     *
     * @param  string $html The HTML to process.
     */
    public function setHTML($html)
    {
        // strip style definitions, if we use css-class "cleanup" on a style-element
        $this->html = (string)preg_replace('/<style[^>]+class="cleanup"[^>]*>.*<\/style>/Usi', ' ', $html);
    }

    /**
     * Set use of inline styles block
     * If this is enabled the class will use the style-block in the HTML.
     *
     * @return void
     *
     * @param  bool [optional] $on Should we process inline styles?
     */
    public function setUseInlineStylesBlock($on = true)
    {
        $this->useInlineStylesBlock = (bool)$on;
    }

    /**
     * Set strip original style tags
     * If this is enabled the class will remove all style tags in the HTML.
     *
     * @return void
     *
     * @param  bool [optional] $on Should we process inline styles?
     */
    public function setStripOriginalStyleTags($on = true)
    {
        $this->stripOriginalStyleTags = (bool)$on;
    }

    /**
     * Set exclude media queries
     *
     * If this is enabled the media queries will be removed before inlining the rules.
     *
     * WARNING: If you use inline styles block "<style>" the this option will keep the media queries.
     *
     * @return void
     *
     * @param bool [optional] $on
     */
    public function setExcludeMediaQueries($on = true)
    {
        $this->excludeMediaQueries = (bool)$on;
    }

    /**
     * Strip style tags into the generated HTML
     *
     * @param  \DOMXPath $xPath The DOMXPath for the entire document.
     *
     * @return string
     */
    private function stripOriginalStyleTags(\DOMXPath $xPath)
    {
        // Get all style tags
        $nodes = $xPath->query('descendant-or-self::style');
        foreach ($nodes as $node) {
            if ($this->excludeMediaQueries === true) {
                // Search for Media Queries
                preg_match_all($this->cssMediaQueriesRegEx, $node->nodeValue, $mqs);
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
     *
     * @param Specificity[] $e1 The first element.
     * @param Specificity[] $e2 The second element.
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

    /**
     * remove css media queries from the string
     *
     * @param string $css
     *
     * @return string
     */
    private function stripeMediaQueries($css) {
        if ($this->excludeMediaQueries === true) {
            $css = preg_replace($this->cssMediaQueriesRegEx, '', $css);
        }

        return (string)$css;
    }

}
