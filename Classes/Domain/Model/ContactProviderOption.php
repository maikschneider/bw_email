<?php

namespace Blueways\BwEmail\Domain\Model;

class ContactProviderOption
{

    /**
     * @var string
     */
    public $label;

    /**
     * @var string
     */
    public $inputName;

    /**
     * @var string
     */
    public $inputType;

    /**
     * @var array
     */
    public $options;

    /**
     * ContactProviderOption constructor.
     *
     * @param string $label
     * @param string $inputName
     * @param string $inputType
     * @param array $options
     */
    public function __construct(string $label, string $inputName, string $inputType, array $options)
    {
        $this->label = $label;
        $this->inputName = $inputName;
        $this->inputType = $inputType;
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function getInputName(): string
    {
        return $this->inputName;
    }

    /**
     * @param string $inputName
     */
    public function setInputName(string $inputName): void
    {
        $this->inputName = $inputName;
    }

    /**
     * @return string
     */
    public function getInputType(): string
    {
        return $this->inputType;
    }

    /**
     * @param string $inputType
     */
    public function setInputType(string $inputType): void
    {
        $this->inputType = $inputType;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

}
