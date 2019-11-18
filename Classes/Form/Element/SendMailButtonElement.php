<?php

namespace Blueways\BwEmail\Form\Element;

use Blueways\BwEmail\Domain\Model\WizardConf;
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
     * @var array
     */
    protected $settings;

    /**
     * @return array|string
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function render()
    {
        $wizardConfig = GeneralUtility::makeInstance(
            WizardConf::class,
            $this->data['tableName'],
            $this->data['vanillaUid'],
            $this->data['effectivePid']
        );

        $this->settings = $wizardConfig->settings;
        $wizardUri = $wizardConfig->getWizardUri();

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
            data-modal-title="' . $this->settings['modalTitle'] . '" 
            data-modal-send-button-text="' . $this->settings['modalSendButton'] . '" 
            data-modal-cancel-button-text="' . $this->settings['modalCancelButton'] . '">
			  <span class="t3-icon fa fa-envelope-o"></span> ' . $this->settings['buttonText'] . '</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        $resultArray['html'] = $html;

        return $resultArray;
    }
}
