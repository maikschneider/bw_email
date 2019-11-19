<?php

namespace Blueways\BwEmail\Controller;

use Blueways\BwEmail\Domain\Model\WizardConf;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Lang\LanguageService;

class AdministrationController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * BackendTemplateContainer
     *
     * @var BackendTemplateView
     */
    protected $view;

    /**
     * @var \Blueways\BwEmail\Domain\Repository\MailLogRepository
     */
    protected $mailLogRepository;

    /**
     * Backend Template Container
     *
     * @var BackendTemplateView
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    public function indexAction()
    {
        $logs = $this->mailLogRepository->findByStatus(1);

        $this->view->assign('logs', $logs);
    }

    public function injectMailLogRepository(\Blueways\BwEmail\Domain\Repository\MailLogRepository $mailLogRepository)
    {
        $this->mailLogRepository = $mailLogRepository;
    }

    public function errorLogAction()
    {

    }

    public function contactListAction()
    {

    }

    /**
     * Set up the doc header properly here
     *
     * @param ViewInterface $view
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
        /** @var BackendTemplateView $view */
        parent::initializeView($view);

        $this->makeButtons();

        $this->view->assign('successfullMails', $this->mailLogRepository->countByStatus(1));
        $this->view->assign('errorMails', $this->mailLogRepository->countByStatus(0));
        $this->view->assign('action', $this->request->getControllerActionName());
    }

    /**
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function makeButtons(): void
    {
        $this->view->getModuleTemplate()->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/BwEmail/EmailWizard');

        $config = GeneralUtility::makeInstance(
            WizardConf::class,
            '',
            $this->pageinfo['uid'],
            $this->pageinfo['pid']
        );

        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        $emailPageButton = $buttonBar->makeLinkButton()
            ->setClasses('viewmodule_email_button')
            ->setHref('#')
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:bw_email/Resources/Private/Language/locallang.xlf:sendPage'))
            ->setIcon($iconFactory->getIcon('actions-email', Icon::SIZE_SMALL))
            ->setDataAttributes($config->getDataAttributesForButton());
        $buttonBar->addButton($emailPageButton, ButtonBar::BUTTON_POSITION_LEFT, 4);
    }

    /**
     * Returns the LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Creates the URI for a backend action
     *
     * @param string $controller
     * @param string $action
     * @param array $parameters
     * @return string
     */
    protected function getHref($controller, $action, $parameters = [])
    {
        $uriBuilder = $this->objectManager->get(UriBuilder::class);
        $uriBuilder->setRequest($this->request);
        return $uriBuilder->reset()->uriFor($action, $parameters, $controller);
    }
}
