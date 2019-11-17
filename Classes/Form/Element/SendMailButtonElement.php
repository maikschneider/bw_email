<?php

namespace Blueways\BwEmail\Form\Element;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Class SendMailButtonElement
 *
 * @package Blueways\BwEmail\Form\Element
 */
class SendMailButtonElement extends AbstractFormElement
{

    /**
     * @var array
     */
    protected $config = [
        'modalTitle' => 'LLL:EXT:bw_email/Resources/Private/Language/locallang.xlf:modalTitle',
        'modalSendButton' => 'LLL:EXT:bw_email/Resources/Private/Language/locallang.xlf:modalSendButton',
        'modalCancelButton' => 'LLL:EXT:bw_email/Resources/Private/Language/locallang.xlf:modalCancelButton',
        'buttonText' => 'LLL:EXT:bw_email/Resources/Private/Language/locallang.xlf:buttonText',

        // single || multiple
        'recipientCount' => 'single',
        // possible values:
        // - empty value
        // - fixed value (e.g. hi@example.com)
        // - field value (e.g. FIELD:email)
        'recipientAddress' => '',
        // samle as recipientAddress
        'recipientName' => '',
        // if empty, value taken from typoscript
        'senderAddress' => '',
        'senderName' => '',
        'replytoAddress' => '',
        'subject' => '',
        'template' => ''
    ];

    /**
     * @return array|string
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function render()
    {
        $this->generateConfig();
        $wizardUri = $this->getWizardUri();

        $resultArray = $this->initializeResultArray();
        $resultArray['requireJsModules'][] = 'TYPO3/CMS/BwEmail/EmailWizard';

        $html = '';
        $html .= '<div class="formengine-field-item t3js-formengine-field-item">';
        $html .= '<div class="form-wizards-wrap">';
        $html .= '<div class="form-wizards-element">';
        $html .= '<div class="form-control-wrap">';
        $html .= '<button 
                id="sendMailButton"
            class="btn btn-default t3js-sendmail-trigger viewmodule_email_button"
            data-wizard-uri="' . $wizardUri . '" 
            data-modal-title="' . $this->config['modalTitle'] . '" 
            data-modal-send-button-text="' . $this->config['modalSendButton'] . '" 
            data-modal-cancel-button-text="' . $this->config['modalCancelButton'] . '">
			  <span class="t3-icon fa fa-envelope-o"></span> ' . $this->config['buttonText'] . '</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        $resultArray['html'] = $html;

        return $resultArray;
    }

    /**
     * Merge settings from TCA and TypoScript with default config
     * Set and transform data needed for content injection
     *
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    private function generateConfig()
    {
        // merge with TypoScript (TypoScript cannot unset settings)
        $objectManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
        /** @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManager $configurationManager */
        $configurationManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
        $typoScript = $configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );
        ArrayUtility::mergeRecursiveWithOverrule(
            $this->config,
            $typoScript['plugin.']['tx_bwemail.']['settings.'],
            true,
            false
        );

        // merge with TCA (TCA can unset settings)
        ArrayUtility::mergeRecursiveWithOverrule($this->config, $this->data['parameterArray']['fieldConf']['config']);

        // set fixed values (even if record was not saved before)
        $this->config['databaseTable'] = $this->data['tableName'];
        $this->config['databaseUid'] = $this->data['vanillaUid'];
        $this->config['databasePid'] = $this->data['effectivePid'];

        /**
         * Alter config fields
         *
         * @param string $item
         * @param $key
         * @param self $self
         */
        $editFields = function (&$item, $key, $self) {

            // check for FIELDS
            preg_match_all('/(FIELD:)(\w+)((?:\.)(\w+))?/', $item, $fieldStatements);

            foreach ($fieldStatements[0] as $key => $fieldStatement) {
                $propertyName = $fieldStatements[2][$key];
                $replaceWith = '';
                if (isset($self->data['databaseRow'][$propertyName])) {
                    if (is_string($self->data['databaseRow'][$propertyName])) {
                        $replaceWith = $self->data['databaseRow'][$propertyName];
                    }
                }
                $item = str_replace($fieldStatement, $replaceWith, $item);
            }

            // check for LLLs
            preg_match_all('/(LLL:)(EXT\:)?([\w\-\/]+\.\w+\:[\.?\w]+)/', $item, $llStatements);

            foreach ($llStatements[0] as $key => $llStatement) {
                $translation = $self->getLanguageService()->sL($llStatement);
                $item = str_replace($llStatement, $translation, $item);
            }
        };
        array_walk_recursive($this->config, $editFields, $this);
    }

    /**
     * @param array $uriArguments
     * @return string
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    private function getWizardUri()
    {
        $routeName = 'ajax_wizard_modal_page';

        $uriArguments = [];
        $uriArguments['arguments'] = json_encode($this->config);
        $uriArguments['signature'] = GeneralUtility::hmac(
            $uriArguments['arguments'],
            $routeName
        );

        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);

        return (string)$uriBuilder->buildUriFromRoute($routeName, $uriArguments);
    }
}
