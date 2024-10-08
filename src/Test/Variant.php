<?php

namespace ABTesting\Test;

/**
 * Class Variant
 */
class Variant
{
    private mixed $value;

    private string $identifier;

    private bool $default;

    /**
     * Variant constructor.
     * @param string $identifier
     * @param mixed $value
     * @param bool $default
     */
    public function __construct(string $identifier, mixed $value, bool $default = false)
    {
        $this->identifier = $identifier;
        $this->value = $value;
        $this->default = $default;
    }

    /**
     * @return mixed
     */
    public function getValue(): mixed
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
