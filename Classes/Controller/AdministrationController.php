<?php

namespace Blueways\BwEmail\Controller;

use Blueways\BwEmail\Controller\Ajax\EmailWizardController;
use Blueways\BwEmail\Domain\Model\MailLog;
use Blueways\BwEmail\Domain\Model\WizardConf;
use Blueways\BwEmail\Domain\Repository\MailLogRepository;
use Psr\Http\Message\ResponseInterface;
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

    /**
     * AdministrationController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->objectManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
        $this->mailLogRepository = $this->objectManager->get(MailLogRepository::class);
    }

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
        $logs = $this->mailLogRepository->findByStatus(0);

        $this->view->assign('logs', $logs);
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
        $pageRenderer = $this->view->getModuleTemplate()->getPageRenderer();
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/BwEmail/EmailWizard');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/BwEmail/EmailModule');

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

    /**
     * @param int $logId
     * @return
     */
    public function previewAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $logId = $request->getQueryParams()['id'];
        /** @var \Blueways\BwEmail\Domain\Model\MailLog $log */
        $log = $this->mailLogRepository->findByUid($logId);

        $src = 'data:text/html;charset=utf-8,' . EmailWizardController::encodeURIComponent($log->getBody());

        $content = '<iframe frameborder="0" width="100%" height="97%" src="'.$src.'"></iframe>';

        $response->getBody()->write($content);

        return $response;
    }

    /**
     * @param \Blueways\BwEmail\Domain\Model\MailLog $log
     */
    public function showLogAction(MailLog $log)
    {
        $this->view->assign('log', $log);
    }
}
