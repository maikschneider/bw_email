<?php

namespace Blueways\BwEmail\Form\Element;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SendMailButtonElement
 *
 * @package Blueways\BwEmail\Form\Element
 */
class SendMailButtonElement extends AbstractFormElement
{

    /**
     * @var string
     */
    protected $databaseTable;

    /**
     * @var int
     */
    protected $databaseUid;

    /**
     * @var array
     */
    protected $config = [
        'modalTitle' => 'LLL:EXT:bw_email/Resources/Private/Language/locallang.xlf:modalTitle',
        'modalSendButton' => 'LLL:EXT:bw_email/Resources/Private/Language/locallang.xlf:modalSendButton',
        'modalCancelButton' => 'LLL:EXT:bw_email/Resources/Private/Language/locallang.xlf:modalCancelButton',
        'buttonText' => 'LLL:EXT:bw_email/Resources/Private/Language/locallang.xlf:buttonText',
    ];

    /**
     * @return array|string
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public function render()
    {
        $this->generateConfig();
        $wizardUri = $this->getWizardUri();

        $resultArray = $this->initializeResultArray();
        $resultArray['requireJsModules'][] = 'TYPO3/CMS/BwEmail/EmailWizard';

        \TYPO3\CMS\Core\Utility\DebugUtility::debug($this->data, 'Debug: ' . __FILE__ . ' in Line: ' . __LINE__);

        $html = '';
        // @TODO check why there is an inline style
        $html .= '<div class="formengine-field-item t3js-formengine-field-item">';
        $html .= '<div class="form-wizards-wrap">';
        $html .= '<div class="form-wizards-element">';
        $html .= '<div class="form-control-wrap">';
        $html .= '<button 
            class="btn btn-default t3js-sendmail-trigger sendMailButton"
            data-wizard-uri="' . $wizardUri . '" 
            data-modal-title="' . $this->config['modalTitle'] . '" 
            data-modal-send-button-text="' . $this->config['modalSendButton'] . '" 
            data-modal-cancel-button-text="' . $this->config['modalCancelButtonText'] . '">
			  <span class="t3-icon fa fa-envelope-o"></span> ' . $this->config['buttonText'] . '</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        $resultArray['html'] = $html;

        return $resultArray;
    }

    /**
     * Merge settings from TCA with default config
     * Set data needed for content injection
     *
     * @TODO Allow injection of foreign data (like extending some query)
     */
    private function generateConfig()
    {
        ArrayUtility::mergeRecursiveWithOverrule($this->config, $this->data['parameterArray']['fieldConf']['config']);
        $this->config['databaseTable'] = $this->data['tableName'];
        $this->config['databaseUid'] = $this->data['vanillaUid'];

        // translate labels
        foreach ($this->config as $key => $config) {
            if (substr($config, 0, 4) === 'LLL:') {
                $this->config[$key] = $this->getLanguageService()->sL($config);
            }
        }
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
