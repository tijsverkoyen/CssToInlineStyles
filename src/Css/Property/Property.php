<?php

namespace TijsVerkoyen\CssToInlineStyles\Css\Property;

final class Property
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $value;

    /**
     * Property constructor.
     *
     * @param string $name
     * @param string $value
     */
    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Is this property important?
     *
     * @return bool
     */
    public function isImportant()
    {
        return (stristr($this->value, '!important') !== false);
    }

    /**
     * Get the textual representation of the property
     *
     * @return string
     */
    public function toString()
    {
        return sprintf(
            '%1$s: %2$s;',
            $this->name,
            $this->value
        );
    }
}
