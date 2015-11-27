<?php

namespace TijsVerkoyen\CssToInlineStyles\Css\Rule;

use \TijsVerkoyen\CssToInlineStyles\Css\Property\Processor as PropertyProcessor;
use TijsVerkoyen\CssToInlineStyles\Css\Specificity\Specificity;

class Processor
{
    /**
     * Split a string into seperate rules
     *
     * @param string $rulesString
     * @return array
     */
    public function splitIntoSeparateRules($rulesString)
    {
        $rulesString = $this->cleanup($rulesString);

        return (array) explode('}', $rulesString);
    }

    /**
     * @param $string
     * @return mixed|string
     */
    private function cleanup($string)
    {
        $string = str_replace(array("\r", "\n"), '', $string);
        $string = str_replace(array("\t"), ' ', $string);
        $string = str_replace('"', '\'', $string);
        $string = preg_replace('|/\*.*?\*/|', '', $string);
        $string = preg_replace('/\s\s+/', ' ', $string);

        $string = trim($string);
        $string = rtrim($string, '}');

        return $string;
    }

    /**
     * Convert a rule-string into an object
     *
     * @param string $rule
     * @param int    $orginalOrder
     * @return array
     */
    public function convertToObjects($rule, $orginalOrder)
    {
        $rule = $this->cleanup($rule);

        $chunks = explode('{', $rule);
        if (!isset($chunks[1])) {
            return array();
        }
        $propertiesProcessor = new PropertyProcessor();
        $rules = array();
        $selectors = (array) explode(',', trim($chunks[0]));
        $properties = $propertiesProcessor->splitIntoSeparateProperties($chunks[1]);

        foreach ($selectors as $selector) {
            $selector = trim($selector);

            $rules[] = new Rule(
                $selector,
                $propertiesProcessor->convertArrayToObjects($properties),
                Specificity::fromSelector($selector),
                $orginalOrder
            );
        }

        return $rules;
    }

    /**
     * @param array $rules
     * @return Rule[]
     */
    public function convertArrayToObjects(array $rules, array $objects = array())
    {
        $order = 1;
        foreach ($rules as $rule) {
            $objects = array_merge($objects, $this->convertToObjects($rule, $order));
            $order++;
        }

        return $objects;
    }

    /**
     * Sort an array on the specificity element
     *
     * @return int
     * @param  Rule $e1 The first element.
     * @param  Rule $e2 The second element.
     */
    private static function sortOnSpecificity(Rule $e1, Rule $e2)
    {
        $e1Specificity = ($e1->getSpecificity());
        $value = $e1Specificity->compareTo($e2->getSpecificity());

        // if the specificity is the same, use the order in which the element appeared
        if ($value === 0) {
            $value = $e1->getOrder() - $e2->getOrder();
        }

        return $value;
    }
}
