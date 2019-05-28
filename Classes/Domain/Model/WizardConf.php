<?php

namespace Blueways\BwEmail\Domain\Model;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Class WizardConf
 *
 * @package Blueways\BwEmail\Domain\Model
 */
class WizardConf
{

    /**
     * @var array
     */
    public $settings = [];

    /**
     * WizardConf constructor.
     *
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function __construct()
    {
        $this->createFromTypoScript();
    }

    /**
     * Init $settings with values from TypoScript
     *
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function createFromTypoScript()
    {
        $objectManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
        /** @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManager $configurationManager */
        $configurationManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
        $typoScript = $configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );

        ArrayUtility::mergeRecursiveWithOverrule(
            $this->settings,
            $typoScript['plugin.']['tx_bwemail.']['settings.'],
            true,
            false
        );
    }

    /**
     * @return array
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public function getDataAttributesForButton()
    {
        return [
            'wizard-uri' => $this->getWizardUri(),
            'modal-title' => $this->settings['modalTitle'],
            'modal-send-button-text' => $this->settings['modalSendButton'],
            'modal-cancel-button-text' => $this->settings['modalCancelButton']
        ];
    }

    /**
     * @return string
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public function getWizardUri()
    {
        $routeName = 'ajax_wizard_modal_page';

        $uriArguments = [];
        $uriArguments['arguments'] = json_encode($this->settings);
        $uriArguments['signature'] = GeneralUtility::hmac(
            $uriArguments['arguments'],
            $routeName
        );

        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);

        return (string)$uriBuilder->buildUriFromRoute($routeName, $uriArguments);
    }

    /**
     * Used in custom FormElement
     *
     * @param array $tcaConfig
     */
    public function setTcaConfig(array $tcaConfig)
    {
        if (!$tcaConfig || !is_array($tcaConfig)) {
            return;
        }

        // merge with TCA (TCA can unset settings)
        ArrayUtility::mergeRecursiveWithOverrule($this->settings, $tcaConfig['parameterArray']['fieldConf']['config']);

        // set fixed values (even if record was not saved before)
        $this->settings['databaseTable'] = $tcaConfig['tableName'];
        $this->settings['databaseUid'] = $tcaConfig['vanillaUid'];
        $this->settings['databasePid'] = $tcaConfig['effectivePid'];

        /**
         * Alter config fields
         *
         * @param string $item
         * @param $key
         * @param $tcaConfig
         */
        $editFields = function (&$item, $key, $tcaConfig) {
            if (substr($item, 0, 6) === 'FIELD:' && $savedData = $tcaConfig['databaseRow'][substr($item, 6)]) {
                $item = $savedData;
            }
        };
        // insert data from record
        array_walk_recursive($this->settings, $editFields, $tcaConfig);

        $this->translateFields();
    }

    public function translateFields()
    {
        $editFields = function (&$item, $key, $self) {
            if (substr($item, 0, 4) === 'LLL:') {
                $item = $self->getLanguageService()->sL($item);
            }
        };
        array_walk_recursive($this->settings, $editFields, $this);
    }

    /**
     * @param int $pageUid
     */
    public function preparePageRendering(int $pageUid)
    {
        $this->settings['databaseTable'] = 'pages';
        $this->settings['databaseUid'] = $pageUid;
        $this->settings['databasePid'] = $pageUid;
        $this->translateFields();

        /**
         * Replace FIELD:pid in settings with actual pid
         *
         * @param string $item
         * @param $key
         * @param $pageUid
         */
        $editFields = function (&$item, $key, $pageUid) {
            if (substr($item, 0, 9) === 'FIELD:pid') {
                $item = $pageUid;
            }
        };
        // insert data from record
        array_walk_recursive($this->settings, $editFields, $pageUid);
    }

    /**
     * @return mixed|\TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
