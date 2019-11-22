<?php

namespace ABTesting\Test;

/**
 * Class Variant
 */
class Variant
{
    /**
     * @var mixed
     */
    private $value;

    /**
     * @var string
     */
    private $identifier;

    /**
     * @var bool
     */
    private $default;

    /**
     * Variant constructor.
     * @param string $identifier
     * @param mixed $value
     * @param bool $default
     */
    public function __construct(string $identifier, $value, bool $default = false)
    {
        $this->identifier = $identifier;
        $this->value = $value;
        $this->default = $default;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->default;
    }
}
