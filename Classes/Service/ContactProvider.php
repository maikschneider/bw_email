<?php

namespace Blueways\BwEmail\Service;

use Blueways\BwEmail\Domain\Model\Contact;
use Blueways\BwEmail\Domain\Model\ContactProviderOption;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Class ContactProvider
 */
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
     * @var ContactProviderOption[]
     */
    protected $options;

    /**
     * @var mixed
     */
    protected $settings;

    /**
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;

    public function __construct()
    {
        $this->createOptions();
    }

    /**
     * @return mixed
     */
    abstract protected function createOptions();

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
     * @return array
     */
    public function getModalConfiguration()
    {
        return [
            'fqcn' => get_class($this),
            'name' => $this->getProviderName(),
            'description' => $this->getProviderDescription(),
            'options' => $this->getOptions(),
            'contacts' => $this->getContacts(),
        ];
    }

    /**
     * @return string
     */
    public function getProviderName()
    {
        return $this->getLanguageService()->sL($this->name);
    }

    /**
     * @return mixed|LanguageService
     */
    private function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return string
     */
    public function getProviderDescription()
    {
        return $this->getLanguageService()->sL($this->description);
    }

    /**
     * @return ContactProviderOption[]
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return Contact[]
     */
    abstract public function getContacts();

    /**
     * @param $optionsConfiguration
     */
    public function applyConfiguration($optionsConfiguration)
    {
        if (!$optionsConfiguration || !count($optionsConfiguration)) {
            return;
        }

        foreach ($this->options as $key => $option) {
            if (isset($optionsConfiguration[$key])) {
                $option->value = (int)$optionsConfiguration[$key];
            }
        }
    }

    /**
     * @TODO: Do something meaningful like setting defaults.
     * Warning: these settings are not available when initialized in preview and sendAction
     * @param $providerSettings
     */
    public function applySettings($providerSettings)
    {
        $this->settings = $providerSettings;
    }
}
