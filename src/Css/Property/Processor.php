<?php

namespace TijsVerkoyen\CssToInlineStyles\Css\Property;

class Processor
{
    /**
     * Split a string into seperate properties
     *
     * @param string $propertiesString
     * @return array
     */
    public function splitIntoSeparateProperties($propertiesString)
    {
        $propertiesString = $this->cleanup($propertiesString);

        $properties = (array) explode(';', $propertiesString);
        $keysToRemove = array();
        $numberOfProperties = count($properties);

        for ($i = 0; $i < $numberOfProperties; $i++) {
            $properties[$i] = trim($properties[$i]);

            // if the new property begins with base64 it is part of the current property
            if (isset($properties[$i + 1]) && strpos(trim($properties[$i + 1]), 'base64,') === 0) {
                $properties[$i] .= ';' . trim($properties[$i + 1]);
                $keysToRemove[] = $i + 1;
            }
        }

        if (!empty($keysToRemove)) {
            foreach ($keysToRemove as $key) {
                unset($properties[$key]);
            }
        }

        return array_values($properties);
    }

    /**
     * @param $string
     * @return mixed|string
     */
    protected function cleanup($string)
    {
        $string = str_replace(array("\r", "\n"), '', $string);
        $string = str_replace(array("\t"), ' ', $string);
        $string = str_replace('"', '\'', $string);
        $string = preg_replace('|/\*.*?\*/|', '', $string);
        $string = preg_replace('/\s\s+/', ' ', $string);

        $string = trim($string);
        $string = rtrim($string, ';');

        return $string;
    }

    /**
     * Convert a property-string into an object
     *
     * @param string $property
     * @return Property|null
     */
    public function convertToObject($property)
    {
        $chunks = (array) explode(':', $property, 2);

        if (!isset($chunks[1]) || $chunks[1] == '') {
            return null;
        }

        return new Property(trim($chunks[0]), trim($chunks[1]));
    }

    /**
     * Convert an array of property-strings into objects
     *
     * @param array $properties
     * @return array
     */
    public function convertArrayToObjects(array $properties)
    {
        $objects = array();

        foreach ($properties as $property) {
            $objects[] = $this->convertToObject($property);
        }

        return $objects;
    }

    /**
     * Build the property-string for multiple properties
     *
     * @param array $properties
     * @return string
     */
    public function buildPropertiesString(array $properties)
    {
        $chunks = array();

        foreach ($properties as $property) {
            $chunks[] = $property->toString();
        }

        return implode(' ', $chunks);
    }
}
