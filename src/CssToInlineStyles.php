<?php

namespace TijsVerkoyen\CssToInlineStyles;

use Symfony\Component\CssSelector\CssSelector;
use Symfony\Component\CssSelector\Exception\SyntaxErrorException;
use TijsVerkoyen\CssToInlineStyles\Css\Processor;
use TijsVerkoyen\CssToInlineStyles\Css\Property\Processor as PropertyProcessor;
use TijsVerkoyen\CssToInlineStyles\Css\Rule\Rule;

class CssToInlineStyles
{
    /**
     * Will inline the $css into the given $html
     *
     * Remark: if the html contains <style>-tags those will be used, the rules
     * in $css will be appended.
     *
     * @param string $html
     * @param string $css
     * @return string
     */
    public function convert($html, $css = null)
    {
        $document = $this->createDomDocumentFromHtml($html);
        $processor = new Processor();

        // get all styles from the style-tags
        $rules = $processor->getRules(
            $processor->getCssFromStyleTags($html)
        );

        if ($css !== null) {
            $rules = $processor->getRules($css, $rules);
        }
        $document = $this->inline($document, $rules);

        return $this->getHtmlFromDocument($document);
    }

    /**
     * Inline the given properties on a DOMElement
     *
     * @param \DOMElement $element
     * @param array       $properties
     * @return \DOMElement
     */
    public function inlineCssOnElement(\DOMElement $element, array $properties)
    {
        if (empty($properties)) {
            return $element;
        }

        $propertyProcessor = new PropertyProcessor();
        $cssProperties = array();
        $currentStyles = $element->attributes->getNamedItem('style');

        if ($currentStyles !== null) {
            $currentProperties = $propertyProcessor->convertArrayToObjects(
                $propertyProcessor->splitIntoSeparateProperties($currentStyles->value)
            );

            foreach ($currentProperties as $property) {
                $cssProperties[$property->getName()] = $property;
            }
        }

        foreach ($properties as $property) {
            if (isset($cssProperties[$property->getName()])) {
                // only overrule it if the the not-inline-style property is important
                if ($property->isImportant()) {
                    $cssProperties[$property->getName()] = $property;
                }
            } else {
                $cssProperties[$property->getName()] = $property;
            }
        }

        $element->setAttribute(
            'style',
            $propertyProcessor->buildPropertiesString(array_values($cssProperties))
        );

        return $element;
    }

    /**
     * @param string $html
     * @return \DOMDocument
     */
    protected function createDomDocumentFromHtml($html)
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $internalErrors = libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_use_internal_errors($internalErrors);
        $document->formatOutput = true;

        return $document;
    }

    /**
     * @param \DOMDocument $document
     * @return string
     */
    protected function getHtmlFromDocument(\DOMDocument $document)
    {
        $xml = $document->saveXML(null, LIBXML_NOEMPTYTAG);

        $html = preg_replace(
            '|<\?xml (.*)\?>|',
            '',
            $xml
        );

        return ltrim($html);
    }

    /**
     * @param \DOMDocument $document
     * @param array        $rules
     * @return \DOMDocument
     */
    protected function inline(\DOMDocument $document, array $rules)
    {
        if (empty($rules)) {
            return $document;
        }

        $xPath = new \DOMXPath($document);
        foreach ($rules as $rule) {
            /** @var Rule $rule */

            try {
                $expression = CssSelector::toXPath($rule->getSelector());
            } catch(SyntaxErrorException $e) {
                continue;
            }

            $elements = $xPath->query($expression);

            if ($elements === false) {
                continue;
            }

            foreach ($elements as $element) {
                $this->inlineCssOnElement($element, $rule->getProperties());
            }
        }

        return $document;
    }
}
