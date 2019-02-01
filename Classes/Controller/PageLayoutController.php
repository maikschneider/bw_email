<?php

namespace Blueways\BwEmail\Controller;

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

            $lang = $this->getLanguageService();

            $emailPageButton = $this->buttonBar->makeLinkButton()
                ->setClasses('viewmodule_email_button')
                ->setHref('#')
                ->setTitle($lang->getLL('editPageProperties'))
                ->setIcon($this->iconFactory->getIcon('actions-email', Icon::SIZE_SMALL))
                ->setDataAttributes([
                    'wizard-uri' => $this->getPageWizardUri(),
                    'modal-title' => $lang->sL('LLL:EXT:bw_email/Resources/Private/Language/locallang.xlf:sendPage'),
                    'modal-cancel-button-text' => $lang->sL('LLL:EXT:bw_email/Resources/Private/Language/locallang.xlf:modalCancelButton'),
                    'modal-send-button-text' => $lang->sL('LLL:EXT:bw_email/Resources/Private/Language/locallang.xlf:modalSendButton'),
                ]);
            $this->buttonBar->addButton($emailPageButton, ButtonBar::BUTTON_POSITION_LEFT, 4);
        }
    }

    /**
     * @return string
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    private
    function getPageWizardUri()
    {
        $routeName = 'ajax_wizard_modal_page';
        $uriArguments['arguments'] = json_encode([
            'page' => $this->pageinfo['uid']
        ]);
        $uriArguments['signature'] = \TYPO3\CMS\Core\Utility\GeneralUtility::hmac(
            $uriArguments['arguments'],
            $routeName
        );
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute($routeName, $uriArguments);
    }

}
