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
     * @var array
     */
    protected $settings;

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
        $this->createSettings();
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
     * @param array $optionSelections
     * @return \Blueways\BwEmail\Domain\Model\Contact[]
     */
    abstract public function getContacts(array $optionSelections);

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

    /**
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @param array $settings
     */
    public function setSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    /**
     * @return void
     */
    protected function createSettings()
    {
        foreach ($this->options as $option) {
            $this->settings[$option->inputName] = $option->options[0] ?? '';
        }
    }
}
