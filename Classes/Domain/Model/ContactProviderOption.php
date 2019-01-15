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

}
