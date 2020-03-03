<?php

namespace Blueways\BwEmail\Controller\Ajax;

use Blueways\BwEmail\Domain\Model\Dto\EmailSettings;
use Blueways\BwEmail\Utility\SenderUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class EmailWizardController
 *
 * @package Blueways\BwEmail\Controller\Ajax
 */
class EmailWizardController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * @var array
     */
    protected $queryParams = null;

    /**
     * @var array
     */
    protected $typoscript;

    /**
     * @var \Blueways\BwEmail\View\EmailView
     */
    protected $emailView;

    /**
     * @var StandaloneView
     */
    private $templateView;

    /**
     * SendmailWizard constructor.
     *
     * @param \TYPO3\CMS\Fluid\View\StandaloneView|null $templateView
     */
    public function __construct(StandaloneView $templateView = null)
    {
        parent::__construct();

        $this->objectManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
        $configurationManager = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
        $this->typoscript = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);

        if (!$templateView) {
            $templateView = GeneralUtility::makeInstance(StandaloneView::class);
            $templateView->setLayoutRootPaths($this->typoscript['plugin.']['tx_bwemail.']['view.']['layoutRootPaths.']);
            $templateView->setPartialRootPaths($this->typoscript['plugin.']['tx_bwemail.']['view.']['partialRootPaths.']);
            $templateView->setTemplateRootPaths($this->typoscript['plugin.']['tx_bwemail.']['view.']['templateRootPaths.']);
        }

        $this->templateView = $templateView;
        $this->uriBuilder = $this->objectManager->get('TYPO3\\CMS\\Backend\\Routing\\UriBuilder');
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public function modalAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        // security: check signature
        if (!$this->isSignatureValid($request, 'ajax_wizard_modal_page')) {
            return $response->withStatus(403);
        }

        $this->queryParams = json_decode($request->getQueryParams()['arguments'], true);

        $emailSettings = GeneralUtility::makeInstance(EmailSettings::class);
        $emailSettings->override($this->queryParams);

        $providers = $emailSettings->getProviderConfiguration();
        $formActionUri = $this->getAjaxUri('ajax_wizard_modal_send');
        $templates = $this->getTemplates();

        $this->templateView->assignMultiple([
            'formActionUri' => $formActionUri,
            'defaults' => $emailSettings,
            'templates' => $templates,
            'providers' => $providers,
        ]);

        $this->templateView->setTemplate('Administration/EmailWizard');
        $content = $this->templateView->render();
        $response->getBody()->write($content);

        return $response;
    }

    /**
     * Check if hmac signature is correct
     *
     * @param ServerRequestInterface $request the request with the GET parameters
     * @param string $route
     * @return bool
     */
    protected function isSignatureValid(ServerRequestInterface $request, string $route)
    {
        $token = GeneralUtility::hmac($request->getQueryParams()['arguments'], $route);
        return $token === $request->getQueryParams()['signature'];
    }

    /**
     * @param $routeName
     * @param array $params
     * @return string
     */
    protected function getAjaxUri($routeName, $params = [])
    {
        $queryParams = $this->queryParams;
        foreach ($params as $paramName => $paramValue) {
            $queryParams[$paramName] = $paramValue;
        }

        $uriArguments['arguments'] = json_encode($queryParams);
        $uriArguments['signature'] = GeneralUtility::hmac(
            $uriArguments['arguments'],
            $routeName
        );

        return (string)$this->uriBuilder->buildUriFromRoute($routeName, $uriArguments);
    }

    /**
     * @return array
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function getTemplates()
    {
        $pageUid = $this->queryParams['databasePid'] ?? 0;
        $pageTsConfig = BackendUtility::getPagesTSconfig($pageUid);
        $templates = $pageTsConfig['mod.']['web_layout.']['EmailLayouts.'];
        $selection = [];
        foreach ($templates as $template) {
            $selection[] = array(
                'file' => $template['title'],
                'name' => $this->getLanguageService()->sL($template['title']),
                'previewUri' => $this->getAjaxUri(
                    'ajax_wizard_modal_preview',
                    array_merge($this->queryParams, ['template' => $template['title']])
                )
            );
        }
        return $selection;
    }

    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    public function modalResendAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        if (!$this->isSignatureValid($request, 'ajax_wizard_modal_resend')) {
            return $response->withStatus(403);
        }

        $this->queryParams = json_decode($request->getQueryParams()['arguments'], true);

        $defaults = $this->queryParams;

        $routeName = 'ajax_email_preview';
        $uriArguments['id'] = $this->queryParams['mailLog'];
        $uriArguments['signature'] = GeneralUtility::hmac(
            $uriArguments['id'],
            $routeName
        );

        $previewUri = (string)$this->uriBuilder->buildUriFromRoute($routeName, $uriArguments);

        $templates = [
            0 => [
                'file' => '',
                'name' => 'Saved HTML',
                'previewUri' => $previewUri
            ]
        ];

        $formActionUri = $this->getAjaxUri('ajax_email_resend');

        $this->templateView->assignMultiple([
            'formActionUri' => $formActionUri,
            'defaults' => $defaults,
            'templates' => $templates,
        ]);

        $this->templateView->setTemplate('Administration/EmailWizard');
        $content = $this->templateView->render();
        $response->getBody()->write($content);

        return $response;
    }

    /**
     * This action is currently limited to preview emails by requests that send a page uid.
     * This needs to be shifted to the child class PageEmailWizard
     *
     * @param \TYPO3\CMS\Core\Http\ServerRequest $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
     */
    public function previewAction(\TYPO3\CMS\Core\Http\ServerRequest $request, ResponseInterface $response)
    {
        // security: check signature
        if (!$this->isSignatureValid($request, 'ajax_wizard_modal_preview')) {
            return $response->withStatus(403);
        }

        $queryParams = json_decode($request->getQueryParams()['arguments'], true);

        /** @var EmailSettings $emailSettings */
        $emailSettings = GeneralUtility::makeInstance(EmailSettings::class);
        $emailSettings->override($queryParams);

        if ($request->getMethod() === 'POST') {

            $params = $request->getParsedBody();
            $emailSettings->override($params);

            // check for provider settings in post data
            /*
            if (isset($params['provider']) && sizeof($params['provider']) && (int)$params['provider']['use'] === 1) {
                $providerSettings = $params['provider'];
                $provider = GeneralUtility::makeInstance($providerSettings['id']);
                $provider->applyConfiguration($providerSettings[$providerSettings['id']]['optionsConfiguration']);
                $contacts = $provider->getContacts();

                $selectedContactIndex = 0;
                if (isset($providerSettings[$providerSettings['id']]['selectedContact'])) {
                    $selectedContactIndex = $providerSettings[$providerSettings['id']]['selectedContact'];
                }
                $contact = $contacts[$selectedContactIndex];
                $this->emailView->insertContact($contact);
            }
            */
        }

        $senderUtility = GeneralUtility::makeInstance(SenderUtility::class, $emailSettings);

        $contacts = $emailSettings->getContacts();
        $selectedContactIndex = $emailSettings->selectedContact;
        $marker = $senderUtility->emailView->getMarker();

        // check for internal links
        $hasInternalLinks = count($senderUtility->emailView->getInternalLinks()) ? true : false;
        $html = $senderUtility->emailView->render();
        $src = 'data:text/html;charset=utf-8,' . self::encodeURIComponent($html);

        // build and encode response
        $content = json_encode(array(
            'src' => $src,
            'marker' => $marker,
            'hasInternalLinks' => $hasInternalLinks,
            'contacts' => $contacts,
            'selectedContact' => $selectedContactIndex ?? 0,
        ));

        $response->getBody()->write($content);

        return $response;
    }

    /**
     * @param string $str
     * @return string
     */
    public static function encodeURIComponent($str)
    {
        $revert = array('%21' => '!', '%2A' => '*', '%27' => "'", '%28' => '(', '%29' => ')');
        return strtr(rawurlencode($str), $revert);
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function sendAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($request->getMethod() !== 'POST') {
            $this->throwStatus(405, 'Method not allowed');
        }

        // security: check signature
        if (!$this->isSignatureValid($request, 'ajax_wizard_modal_send')) {
            return $response->withStatus(403);
        }

        // create email settings from GET and POST params
        $queryParams = json_decode($request->getQueryParams()['arguments'], true);
        $postParams = $request->getParsedBody();
        $emailSettings = GeneralUtility::makeInstance(EmailSettings::class);
        $emailSettings->override($queryParams);
        $emailSettings->override($postParams);

        /** @var SenderUtility $senderUtility */
        $senderUtility = GeneralUtility::makeInstance(SenderUtility::class, $emailSettings);


        if (isset($postParams['markerOverrides']) && count($postParams['markerOverrides'])) {
            $senderUtility->emailView->overrideMarker($postParams['markerOverrides']);
        }

        // @TODO: is this line needed?
        $senderUtility->emailView->setPid($queryParams['pid']);

        // check for provider settings and possible list of recipients
        $mailsSend = $senderUtility->send();

        $status = [
            'status' => 'ERROR',
            'message' => [
                'headline' => 'Unknown error',
                'text' => 'No mails have been send.'
            ]
        ];

        if ($mailsSend) {
            $status = [
                'status' => 'OK',
                'message' => [
                    'headline' => 'Success',
                    'text' => ($mailsSend === 1 ? 'Mail' : $mailsSend . ' mails') . 'successfully send.'
                ]
            ];
        }

        $response->getBody()->write(json_encode($status));
        return $response;
    }

}
