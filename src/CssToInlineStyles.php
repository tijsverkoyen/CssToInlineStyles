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
     * Inle the given properties on an given DOMElement
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
        $inlineProperties = $this->getInlineStyles($element);

        if (!empty($inlineProperties)) {
            foreach ($inlineProperties as $property) {
                $cssProperties[$property->getName()] = $property;
            }
        }

        foreach ($properties as $property) {
            if (!isset($cssProperties[$property->getName()])) {
                $cssProperties[$property->getName()] = $property;
            }
        }

        $rules = array();
        foreach ($cssProperties as $property) {
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
     * @param \DOMDocument    $document
     * @param Css\Rule\Rule[] $rules
     * @return \DOMDocument
     */
    protected function inline(\DOMDocument $document, array $rules)
    {
        if (empty($rules)) {
            return $document;
        }

        $xPath = new \DOMXPath($document);
        foreach ($rules as $rule) {
            try {
                if (class_exists('Symfony\Component\CssSelector\CssSelectorConverter')) {
                    $converter = new CssSelectorConverter();
                    $expression = $converter->toXPath($rule->getSelector());
                } else {
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
                $this->calculatePropertiesToBeApplied($element, $rule->getProperties());
            }
        }

        $elements = $xPath->query('//*[@data-css-to-inline-styles]');

        foreach ($elements as $element) {
            $propertiesToBeApplied = $element->attributes->getNamedItem('data-css-to-inline-styles');
            $element->removeAttribute('data-css-to-inline-styles');

            if ($propertiesToBeApplied !== null) {
                $properties = unserialize(base64_decode($propertiesToBeApplied->value));
                $this->inlineCssOnElement($element, $properties);
            }
        }

        return $document;
    }

    /**
     * Store the calculated values in a temporary data-attribute
     *
     * @param \DOMElement             $element
     * @param Css\Property\Property[] $properties
     * @return \DOMElement
     */
    private function calculatePropertiesToBeApplied(
        \DOMElement $element,
        array $properties
    ) {
        if (empty($properties)) {
            return $element;
        }

        $cssProperties = array();
        $currentStyles = $element->attributes->getNamedItem('data-css-to-inline-styles');

        if ($currentStyles !== null) {
            $currentProperties = unserialize(
                base64_decode(
                    $currentStyles->value
                )
            );

            foreach ($currentProperties as $property) {
                $cssProperties[$property->getName()] = $property;
            }
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

        $element->setAttribute(
            'data-css-to-inline-styles',
            base64_encode(
                serialize(
                    array_values($cssProperties)
                )
            )
        );

        return $element;
    }
}
