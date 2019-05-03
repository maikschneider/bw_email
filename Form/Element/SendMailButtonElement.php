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

        // @TODO implement, get additional configuration from TCA conf
        $this->databaseTable = '';
        $this->databaseUid = 1;

        // @TODO possible variables to configure
        /*
         * $buttonText
         * $buttonHeadline
         * $buttonHelp
         *
         * DATAHANDELING
         * [$keys] => $value
         */

        $wizardUri = $this->getWizardUri([]);

        return '';
    }

    /**
     * @param array $uriArguments
     * @return string
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    private function getWizardUri()
    {
        $routeName = 'wizard_modal_page';

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
