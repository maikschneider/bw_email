<?php

namespace Blueways\BwEmail\Controller\Ajax;

use Blueways\BwEmail\Utility\ImapUtility;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class EmailWizardController
 *
 * @package Blueways\BwEmail\Controller\Ajax
 */
class ImapController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * @var \Blueways\BwEmail\Utility\ImapUtility
     */
    protected $imapUtil;

    /**
     * @var array
     */
    protected $typoscript;

    /**
     * @var StandaloneView
     */
    private $templateView;

    /**
     * SendmailWizard constructor.
     *
     * @param \TYPO3\CMS\Fluid\View\StandaloneView|null $templateView
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function __construct(StandaloneView $templateView = null)
    {
        parent::__construct();

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $configurationManager = $this->objectManager->get(ConfigurationManager::class);
        $this->imapUtil = $this->objectManager->get(ImapUtility::class);
        $this->typoscript = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);

        if (!$templateView) {
            /** @var StandaloneView $templateView */
            $templateView = $this->objectManager->get(StandaloneView::class);
            $l = $this->typoscript['plugin.']['tx_bwemail.']['view.']['layoutRootPaths.'];
            $templateView->setLayoutRootPaths(['EXT:bw_email/Resources/Private/Layouts']);
            $templateView->setPartialRootPaths(['EXT:bw_email/Resources/Private/Partials']);
            $templateView->setTemplateRootPaths(['EXT:bw_email/Resources/Private/Templates']);
        }

        $this->templateView = $templateView;
        $this->uriBuilder = $this->objectManager->get(UriBuilder::class);
    }

    /**
     * @TODO: rename to getMailboxAction
     *
     * @param \TYPO3\CMS\Core\Http\ServerRequest $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function inboxAction(
        \TYPO3\CMS\Core\Http\ServerRequest $request,
        ResponseInterface $response
    ): \Psr\Http\Message\ResponseInterface {

        $postData = $request->getParsedBody();
        $mailboxName = $postData['mailboxName'];
        // $loadingTypes: 'onload', 'refresh', 'more'

        $messages = $this->imapUtil->getMailboxMessages($mailboxName);

        $this->templateView->assign('messages', $messages);
        $this->templateView->setTemplate('Administration/InboxList');
        $html = $this->templateView->render();

        $content = json_encode(array(
            'html' => $html
        ));

        $response->getBody()->write($content);

        return $response;
    }

    public function showMailAction(\TYPO3\CMS\Core\Http\ServerRequest $request, ResponseInterface $response)
    {

        $postData = $request->getParsedBody();

        $mail = $this->imapUtil->loadMail($postData['messageMailbox'], $postData['messageNumber'], true);

        $this->templateView->assign('mail', $mail);
        $this->templateView->setTemplate('Administration/FullEmail');
        $html = $this->templateView->render();

        $content = json_encode(array(
            'html' => $html
        ));

        $response->getBody()->write($content);

        return $response;
    }
}
