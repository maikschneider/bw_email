<?php

namespace Blueways\BwEmail\Controller;

use Blueways\BwEmail\Domain\Model\WizardConf;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class PageLayoutController
 *
 * @package Blueways\BwEmail\Controller
 */
class PageLayoutController extends \TYPO3\CMS\Backend\Controller\PageLayoutController
{

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function makeButtons(ServerRequestInterface $request): void
    {
        parent::makeButtons($request);

        if ($this->getBackendUser()->isAdmin() && $this->pageinfo['doktype'] === 117) {
            $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/BwEmail/EmailWizard');

            $config = GeneralUtility::makeInstance(
                WizardConf::class,
                'pages',
                $this->pageinfo['uid'],
                $this->pageinfo['pid']
            );

            $emailPageButton = $this->buttonBar->makeLinkButton()
                ->setClasses('viewmodule_email_button')
                ->setHref('#')
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:bw_email/Resources/Private/Language/locallang.xlf:sendPage'))
                ->setIcon($this->iconFactory->getIcon('actions-email', Icon::SIZE_SMALL))
                ->setDataAttributes($config->getDataAttributesForButton());
            $this->buttonBar->addButton($emailPageButton, ButtonBar::BUTTON_POSITION_LEFT, 4);
        }
    }
}
