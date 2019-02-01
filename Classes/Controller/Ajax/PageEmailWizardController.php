<?php

namespace Blueways\BwEmail\Controller\Ajax;

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Class PageEmailWizardController
 *
 * @package Blueways\BwEmail\Controller\Ajax
 */
class PageEmailWizardController extends EmailWizardController
{

    /**
     * PageEmailWizardController constructor.
     *
     * @param \TYPO3\CMS\Fluid\View\StandaloneView|null $templateView
     */
    public function __construct(?\TYPO3\CMS\Fluid\View\StandaloneView $templateView = null)
    {
        parent::__construct($templateView);
    }

    /**
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
