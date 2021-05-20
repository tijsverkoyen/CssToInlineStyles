<?php

namespace TijsVerkoyen\CssToInlineStyles;

use LogicException;
use Masterminds\HTML5;
use Symfony\Component\CssSelector\CssSelector;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\CssSelector\Exception\ExceptionInterface;
use TijsVerkoyen\CssToInlineStyles\Css\Processor;
use TijsVerkoyen\CssToInlineStyles\Css\Property\Processor as PropertyProcessor;
use TijsVerkoyen\CssToInlineStyles\Css\Rule\Processor as RuleProcessor;

class CssToInlineStyles
{
    private $cssConverter;

    /** @var HTML5|null */
    private $html5Parser;

    /** @var bool */
    private $isHtml5Document = false;

    /**
     * @param bool|null $useHtml5Parser Whether to use a HTML5 parser or the native DOM parser
     */
    public function __construct($useHtml5Parser = null)
    {
        if (class_exists('Symfony\Component\CssSelector\CssSelectorConverter')) {
            $this->cssConverter = new CssSelectorConverter();
        }

        if ($useHtml5Parser) {
            if (! class_exists(HTML5::class)) {
                throw new LogicException('Using the HTML5 parser requires the html5-php library. Try running "composer require masterminds/html5".');
            }

            $this->html5Parser = new HTML5(['disable_html_ns' => true]);
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
     *
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
     *
     * @return \DOMElement
     */
    public function inlineCssOnElement(\DOMElement $element, array $properties)
    {
        if (empty($properties)) {
            return $element;
        }

        $cssProperties = array();
        $inlineProperties = array();

        foreach ($this->getInlineStyles($element) as $property) {
            $inlineProperties[$property->getName()] = $property;
        }

        foreach ($properties as $property) {
            if (!isset($inlineProperties[$property->getName()])) {
                $cssProperties[$property->getName()] = $property;
            }
        }

        $rules = array();
        foreach (array_merge($cssProperties, $inlineProperties) as $property) {
            $rules[] = $property->toString();
        }
        $element->setAttribute('style', implode(' ', $rules));

        return $element;
    }

    /**
     * Get the current inline styles for a given DOMElement
     *
     * @param \DOMElement $element
     *
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
     *
     * @return \DOMDocument
     */
    protected function createDomDocumentFromHtml($html)
    {
        $this->isHtml5Document = false;

        if ($this->canParseHtml5String($html)) {
            return $this->parseHtml5($html);
        }

        return $this->parseXhtml($html);
    }

    /**
     * @param string $html
     * @return \DOMDocument
     */
    protected function parseHtml5($html)
    {
        $this->isHtml5Document = true;

        return $this->html5Parser->parse($this->convertToHtmlEntities($html));
    }

    /**
     * @param string $html
     * @return \DOMDocument
     */
    protected function parseXhtml($html)
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $internalErrors = libxml_use_internal_errors(true);
        $document->loadHTML($this->convertToHtmlEntities($html));
        libxml_use_internal_errors($internalErrors);
        $document->formatOutput = true;

        return $document;
    }

    /**
     * @param string $content
     * @return bool
     */
    protected function canParseHtml5String($content)
    {
        if (null === $this->html5Parser) {
            return false;
        }

        if (false === ($pos = stripos($content, '<!doctype html>'))) {
            return false;
        }

        $header = substr($content, 0, $pos);

        return '' === $header || $this->isValidHtml5Heading($header);
    }

    /**
     * @param string $heading
     * @return bool
     */
    protected function isValidHtml5Heading($heading)
    {
        return 1 === preg_match('/^\x{FEFF}?\s*(<!--[^>]*?-->\s*)*$/u', $heading);
    }

    /**
     * @param string $html
     * @return array|false|string
     */
    protected function convertToHtmlEntities($html)
    {
        return mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
    }

    /**
     * @param \DOMDocument $document
     *
     * @return string
     */
    protected function getHtmlFromDocument(\DOMDocument $document)
    {
        $parser = $document;
        if (null !== $this->html5Parser && $this->isHtml5Document) {
            $parser = $this->html5Parser;
        }

        // retrieve the document element
        // we do it this way to preserve the utf-8 encoding
        $htmlElement = $document->documentElement;
        $html = $parser->saveHTML($htmlElement);
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
     *
     * @return \DOMDocument
     */
    protected function inline(\DOMDocument $document, array $rules)
    {
        if (empty($rules)) {
            return $document;
        }

        $propertyStorage = new \SplObjectStorage();

        $xPath = new \DOMXPath($document);

        usort($rules, array(RuleProcessor::class, 'sortOnSpecificity'));

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

                //skip check to overrule if existing property is important and current is not
                if ($existingProperty->isImportant() && !$property->isImportant()) {
                    continue;
                }

                //overrule if current property is important and existing is not, else check specificity
                $overrule = !$existingProperty->isImportant() && $property->isImportant();
                if (!$overrule) {
                    $overrule = $existingProperty->getOriginalSpecificity()->compareTo($property->getOriginalSpecificity()) <= 0;
                }

                if ($overrule) {
                    unset($cssProperties[$property->getName()]);
                    $cssProperties[$property->getName()] = $property;
                }
            } else {
                $cssProperties[$property->getName()] = $property;
            }
        }

        return $cssProperties;
    }
}
