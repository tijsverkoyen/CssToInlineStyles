<?php

namespace TijsVerkoyen\CssToInlineStyles;

use Symfony\Component\CssSelector\CssSelector;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\CssSelector\Exception\ExceptionInterface;
use TijsVerkoyen\CssToInlineStyles\Css\Processor;
use TijsVerkoyen\CssToInlineStyles\Css\Property\Processor as PropertyProcessor;
use TijsVerkoyen\CssToInlineStyles\Css\Rule\Rule;

class CssToInlineStyles
{
    private $cssConverter;

    public function __construct()
    {
        if (class_exists('Symfony\Component\CssSelector\CssSelectorConverter')) {
            $this->cssConverter = new CssSelectorConverter();
        }
    }

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
     * Inline the given properties on an given DOMElement
     *
     * @param \DOMElement             $element
     * @param Css\Property\Property[] $properties
     * @return \DOMElement
     */
    public function inlineCssOnElement(\DOMElement $element, array $properties)
    {
        if (empty($properties)) {
            return $element;
        }

        $cssProperties = array();
        $cssInlineProperties = array();
        $inlineProperties = $this->getInlineStyles($element);

        foreach ($inlineProperties as $property) {
            $cssInlineProperties[$property->getName()] = $property;
        }

        foreach ($properties as $property) {
            if (!isset($cssInlineProperties[$property->getName()])) {
                $cssProperties[$property->getName()] = $property;
            }
        }

        $rules = array();
        foreach (array_merge($cssProperties, $cssInlineProperties) as $property) {
            $rules[] = $property->toString();
        }
        $element->setAttribute('style', implode(' ', $rules));

        return $element;
    }

    /**
     * Get the current inline styles for a given DOMElement
     *
     * @param \DOMElement $element
     * @return Css\Property\Property[]
     */
    public function getInlineStyles(\DOMElement $element)
    {
        $processor = new PropertyProcessor();

        return $processor->convertArrayToObjects(
            $processor->splitIntoSeparateProperties(
                $element->getAttribute('style')
            )
        );
    }

    /**
     * @param string $html
     * @return \DOMDocument
     */
    protected function createDomDocumentFromHtml($html)
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $internalErrors = libxml_use_internal_errors(true);
        $document->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
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
        // retrieve the document element
        // we do it this way to preserve the utf-8 encoding
        $htmlElement = $document->documentElement;
        $html = $document->saveHTML($htmlElement);
        $html = trim($html);

        // retrieve the doctype
        $document->removeChild($htmlElement);
        $doctype = $document->saveHTML();
        $doctype = trim($doctype);

        // if it is the html5 doctype convert it to lowercase
        if ($doctype === '<!DOCTYPE html>') {
            $doctype = strtolower($doctype);
        }

        return $doctype."\n".$html;
    }

    /**
     * @param \DOMDocument    $document
     * @param Css\Rule\Rule[] $rules
     * @return \DOMDocument
     */
    protected function inline(\DOMDocument $document, array $rules)
    {
        if (empty($rules)) {
            return $document;
        }

        $propertyStorage = new \SplObjectStorage();

        $xPath = new \DOMXPath($document);
        foreach ($rules as $rule) {
            try {
                if (null !== $this->cssConverter) {
                    $expression = $this->cssConverter->toXPath($rule->getSelector());
                } else {
                    // Compatibility layer for Symfony 2.7 and older
                    $expression = CssSelector::toXPath($rule->getSelector());
                }
            } catch (ExceptionInterface $e) {
                continue;
            }

            $elements = $xPath->query($expression);

            if ($elements === false) {
                continue;
            }

            foreach ($elements as $element) {
                $propertyStorage[$element] = $this->calculatePropertiesToBeApplied(
                    $rule->getProperties(),
                    $propertyStorage->contains($element) ? $propertyStorage[$element] : array()
                );
            }
        }

        foreach ($propertyStorage as $element) {
            $this->inlineCssOnElement($element, $propertyStorage[$element]);
        }

        return $document;
    }

    /**
     * Merge the CSS rules to determine the applied properties.
     *
     * @param Css\Property\Property[] $properties
     * @param Css\Property\Property[] $cssProperties existing applied properties indexed by name
     *
     * @return Css\Property\Property[] updated properties, indexed by name
     */
    private function calculatePropertiesToBeApplied(array $properties, array $cssProperties)
    {
        if (empty($properties)) {
            return $cssProperties;
        }

        foreach ($properties as $property) {
            if (isset($cssProperties[$property->getName()])) {
                $existingProperty = $cssProperties[$property->getName()];

                if (
                    ($existingProperty->isImportant() && $property->isImportant()) &&
                    ($existingProperty->getOriginalSpecificity()->compareTo($property->getOriginalSpecificity()) <= 0)
                ) {
                    // if both the properties are important we should use the specificity
                    $cssProperties[$property->getName()] = $property;
                } elseif (!$existingProperty->isImportant() && $property->isImportant()) {
                    // if the existing property is not important but the new one is, it should be overruled
                    $cssProperties[$property->getName()] = $property;
                } elseif (
                    !$existingProperty->isImportant() &&
                    ($existingProperty->getOriginalSpecificity()->compareTo($property->getOriginalSpecificity()) <= 0)
                ) {
                    // if the existing property is not important we should check the specificity
                    $cssProperties[$property->getName()] = $property;
                }
            } else {
                $cssProperties[$property->getName()] = $property;
            }
        }

        return $cssProperties;
    }
}
