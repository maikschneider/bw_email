<?php

namespace Blueways\BwEmail\Service;

use \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

abstract class ContactProvider
{

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var \Blueways\BwEmail\Domain\Model\ContactProviderOption[]
     */
    protected $options;

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected $configurationManager;

    public function __construct()
    {
        $this->createOptions();
    }

    /**
     * Injects the Configuration Manager and loads the settings
     *
     * @param ConfigurationManagerInterface $configurationManager
     */
    public function injectConfigurationManager(
        ConfigurationManagerInterface $configurationManager
    ) {
        $this->configurationManager = $configurationManager;
    }

    /**
     * @return \Blueways\BwEmail\Domain\Model\Contact[]
     */
    abstract public function getContacts();

    /**
     * @return string
     */
    public function getProviderName()
    {
        return $this->getLanguageService()->sL($this->name);
    }

    /**
     * @return string
     */
    public function getProviderDescription()
    {
        return $this->getLanguageService()->sL($this->description);
    }

    /**
     * @return \Blueways\BwEmail\Domain\Model\ContactProviderOption[]
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return mixed
     */
    abstract protected function createOptions();

    /**
     * @return mixed|\TYPO3\CMS\Lang\LanguageService
     */
    private function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    public function getModalConfiguration()
    {
        return [
            'fqcn' => get_class($this),
            'name' => $this->getProviderName(),
            'description' => $this->getProviderDescription(),
            'options' => $this->getOptions(),
            'contacts' => $this->getContacts()
        ];
    }

    public function applyConfiguration($optionsConfiguration)
    {
        if (!$optionsConfiguration || !sizeof($optionsConfiguration)) {
            return;
        }

        foreach ($this->options as $key => $option) {
            if (isset($optionsConfiguration[$key])) {
                $option->value = (int)$optionsConfiguration[$key];
            }
        }
    }

}
