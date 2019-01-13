<?php

namespace Blueways\BwEmail\Controller\Ajax;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;

class PageEmailWizardController extends EmailWizardController
{

    /**
     * @var array|null;
     */
    protected $pageRecord;

    public function __construct(?\TYPO3\CMS\Fluid\View\StandaloneView $templateView = null)
    {
        parent::__construct($templateView);

        $pageIdToShow = (int)GeneralUtility::_GP('id');
        $this->pageRecord = BackendUtility::getRecord('pages', $pageIdToShow);
    }

    /**
     * @return array
     */
    protected function getViewData()
    {
        return [
            'page' => $this->pageRecord
        ];
    }
}
