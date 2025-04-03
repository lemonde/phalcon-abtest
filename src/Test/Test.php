<?php

namespace ABTesting\Test;

use ABTesting\Chooser\ChooserInterface;
use ABTesting\Engine;

/**
 * Class Test
 */
class Test
{
    private string $identifier;

    /**
     * @var Variant[]
     */
    private array $variants = [];

    private ?Variant $defaultVariant = null;

    private ChooserInterface $chooser;

    private ?Variant $winner = null;

    private bool $hasBattled = false;

    /**
     * Test constructor.
     *
     * @param string       $identifier
     * @param Variant[]    $variants
     * @param Variant|null $defaultVariant
     */
    public function __construct(string $identifier, array $variants = [], ?Variant $defaultVariant = null)
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
    public function getVariant(string $identifier): ?Variant
    {
        foreach ($this->variants as $variant) {
            if ($variant->getIdentifier() === $identifier) {
                return $variant;
            }
        }

        return null;
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
    public function addVariant(Variant $variant): void
    {
        $this->variants[] = $variant;

        if ($variant->isDefault()) {
            $this->defaultVariant = $variant;
        }
    }

    /**
     * @param string $identifier
     */
    public function removeVariant(string $identifier): void
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

    public function battle(): void
    {
        if (null !== Engine::getInstance()->getEventsManager()) {
            Engine::getInstance()->getEventsManager()->fire('abtest:beforeBattle', $this);
        }

        if (Engine::getInstance()->isActivated()) {
            $this->winner = $this->getChooser()->choose($this);
        } else {
            $this->winner = $this->getDefaultVariant();
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
     * @param Variant|null $variant
     * @return Test
     */
    public function setWinner(?Variant $variant): Test
    {
        $this->winner = $variant;
        $this->hasBattled = true;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasBattled(): bool
    {
        return $this->hasBattled;
    }
}
