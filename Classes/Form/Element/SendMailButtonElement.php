<?php

namespace Blueways\BwEmail\Form\Element;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;

/**
 * Class SendMailButtonElement
 */
class SendMailButtonElement extends AbstractFormElement
{
    protected ConfigurationManager $configurationManager;

    public function injectConfigurationManager(ConfigurationManager $configurationManager): void
    {
        $this->configurationManager = $configurationManager;
    }

    public function render()
    {
        $resultArray = $this->initializeResultArray();
        $resultArray['additionalInlineLanguageLabelFiles'][] = 'EXT:bw_email/Resources/Private/Language/locallang_js.xlf';

        $version = VersionNumberUtility::convertVersionStringToArray(VersionNumberUtility::getNumericTypo3Version());
        $tableName = $this->data['tableName'];
        $uid = $this->data['vanillaUid'];

        if ($version['version_main'] < 12) {
            $resultArray['requireJsModules'][] = [
                'TYPO3/CMS/BwEmail/EmailWizard' => 'function(EmailWizard){ new EmailWizard(' . $version['version_main'] . ', "' . $tableName . '", ' . $uid . '); }',
            ];
        } else {
            $resultArray['javaScriptModules'][] = \TYPO3\CMS\Core\Page\JavaScriptModuleInstruction::create('@blueways/bw-focuspoint-images/EmailWizard.js')
                ->instance($version['version_main'], $tableName, $uid);
        }

        $buttonLabel = $this->data['parameterArray']['fieldConf']['label'] ?? '';

        $html = '';
        $html .= '<div class="formengine-field-item t3js-formengine-field-item">';
        $html .= '<div class="form-wizards-wrap">';
        $html .= '<div class="form-wizards-element">';
        $html .= '<div class="form-control-wrap">';
        $html .= '<button
                id="sendMailButton"
            class="btn btn-default t3js-sendmail-trigger viewmodule_email_button"
            data-modal-title="' . $this->settings['modalTitle'] . '"
            data-modal-send-button-text="' . $this->settings['modalSendButton'] . '"
            data-modal-cancel-button-text="' . $this->settings['modalCancelButton'] . '">
			  <span class="t3-icon fa fa-envelope-o"></span> ' . $buttonLabel . '</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        $resultArray['html'] = $html;

        return $resultArray;
    }
}
