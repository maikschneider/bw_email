<?php

namespace Blueways\BwEmail\Controller;

use Blueways\BwEmail\Domain\Model\WizardConf;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ViewModuleController
 *
 * @package Blueways\BwEmail\Controller
 */
class ViewModuleController extends \TYPO3\CMS\Viewpage\Controller\ViewModuleController
{
    /**
     * @var array|null
     */
    protected $pageRecord;

    /**
     * @param int $pageId
     * @param int $languageId
     * @param string $targetUrl
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function registerDocHeader(int $pageId, int $languageId, string $targetUrl)
    {
        parent::registerDocHeader($pageId, $languageId, $targetUrl);

        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/BwEmail/EmailWizard');

        $config = GeneralUtility::makeInstance(WizardConf::class);
        $config->preparePageRendering($pageId);

        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $emailPageButton = $buttonBar->makeLinkButton()
            ->setClasses('viewmodule_email_button')
            ->setHref('#')
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:bw_email/Resources/Private/Language/locallang.xlf:sendPage'))
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-email', Icon::SIZE_SMALL))
            ->setDataAttributes($config->getDataAttributesForButton());
        $buttonBar->addButton($emailPageButton);
    }
}
