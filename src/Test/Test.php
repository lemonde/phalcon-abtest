<?php

namespace ABTesting\Test;

use ABTesting\Chooser\ChooserInterface;
use ABTesting\Engine;
use Phalcon\Di;

/**
 * Class Test
 */
class Test
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var Variant[]
     */
    private $variants = [];

    /**
     * @var Variant
     */
    private $defaultVariant;

    /**
     * @var ChooserInterface
     */
    private $chooser;

    /**
     * @var Variant|null
     */
    private $winner;

    /**
     * @var bool
     */
    private $hasBattled = false;

    /**
     * @var bool
     */
    private $isDefault = null;

    /**
     * Test constructor.
     * @param string $identifier
     * @param Variant[] $variants
     * @param Variant $defaultVariant
     */
    public function __construct(string $identifier, array $variants = [], Variant $defaultVariant = null)
    {
        $this->identifier = $identifier;

        foreach ($variants as $variant) {
            $this->addVariant($variant);
        }

        if (empty($this->defaultVariant) && !empty($defaultVariant)) {
            $this->defaultVariant = $defaultVariant;
        }
    }

    /**
     * @return ChooserInterface
     */
    public function getChooser(): ChooserInterface
    {
        return $this->chooser;
    }

    /**
     * @param ChooserInterface $chooser
     */
    public function setChooser(ChooserInterface $chooser): void
    {
        $this->chooser = $chooser;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return Variant[]
     */
    public function getVariants(): array
    {
        return $this->variants;
    }

    /**
     * @return null|Variant
     */
    public function getDefaultVariant(): ?Variant
    {
        return $this->defaultVariant;
    }

    /**
     * @param Variant $defaultVariant
     */
    public function setDefaultVariant(Variant $defaultVariant): void
    {
        $this->defaultVariant = $defaultVariant;
    }

    /**
     * @param Variant $variant
     */
    public function addVariant(Variant $variant)
    {
        $this->variants[] = $variant;

        if ($variant->isDefault()) {
            $this->defaultVariant = $variant;
        }
    }

    /**
     * @param string $identifier
     */
    public function removeVariant(string $identifier)
    {
        foreach ($this->variants as $key => $variant) {
            if ($variant->getIdentifier() === $identifier) {
                array_splice($this->variants, $key, 1);

                if ($variant->isDefault() && $this->getDefaultVariant() && $this->getDefaultVariant()->getIdentifier() === $variant->getIdentifier()) {
                    $this->defaultVariant = null;
                }
            }
        }
    }

    public function battle()
    {
        if (null !== Engine::getInstance()->getEventsManager()) {
            Engine::getInstance()->getEventsManager()->fire('abtest:beforeBattle', $this);
        }

        if (Engine::getInstance()->isActivated()) {
            $this->winner = $this->getChooser()->choose($this);
            $this->setIsDefault(null === $this->isDefault ? false : $this->isDefault);
        } else {
            $this->winner = $this->getDefaultVariant();
            $this->isDefault = true;
        }

        $this->hasBattled = true;

        if (null !== Engine::getInstance()->getEventsManager()) {
            Engine::getInstance()->getEventsManager()->fire('abtest:afterBattle', $this, $this->winner);
        }
    }

    /**
     * @return Variant|null
     */
    public function getWinner(): ?Variant
    {
        return $this->winner ?? $this->defaultVariant;
    }

    /**
     * @return bool
     */
    public function hasBattled()
    {
        return $this->hasBattled;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    /**
     * @param bool $isDefault
     * @return Test
     */
    public function setIsDefault(bool $isDefault): Test
    {
        $this->isDefault = $isDefault;
        return $this;
    }
}
