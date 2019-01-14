<?php

namespace Blueways\BwEmail\Controller\Ajax;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;

class PageEmailWizardController extends EmailWizardController
{

    public function __construct(?\TYPO3\CMS\Fluid\View\StandaloneView $templateView = null)
    {
        parent::__construct($templateView);
    }

    /**
     * @TODO: works only for pages, not tt_content
     * @return array
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function getTemplates()
    {
        $pageUid = $this->queryParams['page'] ?? 0;
        $pageTsConfig = BackendUtility::getPagesTSconfig($pageUid);
        $templates = $pageTsConfig['mod.']['web_layout.']['BackendLayouts.'];
        $selection = [];
        foreach ($templates as $template) {
            $selection[] = array(
                'file' => $template['title'],
                'name' => $template['title'],
                'previewUri' => $this->getPreviewUri($template['title'])
            );
        }
        return $selection;
    }
}
