<?php

namespace Blueways\BwEmail\Form\Element;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
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
     * @return array|string
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public function render()
    {
        $resultArray = $this->initializeResultArray();
        $resultArray['requireJsModules'][] = 'TYPO3/CMS/BwEmail/EmailWizard';

        $wizardUri = $this->getWizardUri([]);

        // @TODO implement, get additional configuration from TCA conf
        $this->databaseTable = '';
        $this->databaseUid = 1;

        // @TODO get the title from TCA configuration to make use of localization
        $buttonLabel = 'Send new Email';
        $buttonText = 'New Email';
        $modalTitle = 'Send new Email';
        $modalSendButtonText = 'Send';
        $modalCancelButtonText = 'Cancel';

        // @TODO possible variables to configure
        /*
         * $buttonText
         * $buttonHelp
         * $modalConfiguration
         *
         * DATAHANDELING
         * [$keys] => $value
         */
        $html = '';
        // @TODO check why there is an inline style
        $html .= '<div class="formengine-field-item t3js-formengine-field-item">';
        $html .= '<div class="form-wizards-wrap">';
        $html .= '<div class="form-wizards-element">';
        $html .= '<div class="form-control-wrap">';
        $html .= '<button 
            class="btn btn-default t3js-sendmail-trigger sendMailButton"
            data-wizard-uri="' . $wizardUri . '" 
            data-modal-title="' . $modalTitle . '" 
            data-modal-send-button-text="' . $modalSendButtonText . '" 
            data-modal-cancel-button-text="' . $modalCancelButtonText . '">
			  <span class="t3-icon fa fa-envelope-o"></span> ' . $buttonText . '</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        $resultArray['html'] = $html;

        return $resultArray;
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
        $uriArguments['arguments'] = json_encode([
            'databaseTable' => $this->databaseTable,
            'databaseUid' => $this->databaseUid
        ]);
        $uriArguments['signature'] = GeneralUtility::hmac(
            $uriArguments['arguments'],
            $routeName
        );

        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);

        return (string)$uriBuilder->buildUriFromRoute($routeName, $uriArguments);
    }
}
