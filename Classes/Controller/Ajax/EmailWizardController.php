<?php

namespace Blueways\BwEmail\Controller\Ajax;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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
     * @var \Blueways\BwEmail\Utility\SenderUtility
     */
    protected $senderUtility;

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
            $templateView->setLayoutRootPaths($this->typoscript['page.']['10.']['layoutRootPaths.']);
            $templateView->setPartialRootPaths($this->typoscript['page.']['10.']['partialRootPaths.']);
            $templateView->setTemplateRootPaths($this->typoscript['page.']['10.']['templateRootPaths.']);
        }

        $this->templateView = $templateView;
        $this->uriBuilder = $this->objectManager->get('TYPO3\\CMS\\Backend\\Routing\\UriBuilder');
        $this->emailView = $this->objectManager->get('Blueways\\BwEmail\\View\\EmailView');
        $this->senderUtility = GeneralUtility::makeInstance('Blueways\BwEmail\Utility\SenderUtility',
            $this->typoscript);
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function modalAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->queryParams = json_decode($request->getQueryParams()['arguments'], true);

        $formActionUri = $this->getAjaxUri('ajax_wizard_modal_send');
        $defaults = $this->senderUtility->getMailSettings();
        $templates = $this->getTemplates();

        // @TODO: use hook to call all contact provider
        $providers = [];
        $contactProvider = GeneralUtility::makeInstance('Blueways\BwEmail\Service\ContactSourceContactProvider');
        $providers[] = $contactProvider->getModalConfiguration();
        $exampleProvider = GeneralUtility::makeInstance('Blueways\BwEmail\Service\ExampleContactProvider');
        $providers[] = $exampleProvider->getModalConfiguration();

        $this->templateView->assignMultiple([
            'formActionUri' => $formActionUri,
            'defaults' => $defaults,
            'templates' => $templates,
            'providers' => $providers,
        ]);

        $this->templateView->setTemplate('Administration/EmailWizard');
        $content = $this->templateView->render();
        $response->getBody()->write($content);

        return $response;
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
     */
    protected function getTemplates()
    {
        return [];
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
        $queryParams = json_decode($request->getQueryParams()['arguments'], true);

        // init email template
        $this->emailView->setTemplate($queryParams['template']);
        $this->emailView->setPid($queryParams['page']);

        if ($request->getMethod() === 'POST') {
            $params = $request->getParsedBody();

            // check for incoming marker overrides
            if (isset($params['markerOverrides']) && sizeof($params['markerOverrides'])) {
                $this->emailView->overrideMarker($params['markerOverrides']);
            }

            // check for provider settings in post data
            if (isset($params['provider']) && sizeof($params['provider']) && (int)$params['provider']['use'] === 1) {
                $providerSettings = $params['provider'];
                /** @var \Blueways\BwEmail\Service\ContactProvider $provider */
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
        }

        // check for internal links
        $hasInternalLinks = sizeof($this->emailView->getInternalLinks()) ? true : false;
        $marker = $this->emailView->getMarker();
        $html = $this->emailView->render();
        $src = 'data:text/html;charset=utf-8,' . self::encodeURIComponent($html);

        // build and encode response
        $content = json_encode(array(
            'src' => $src,
            'marker' => $marker,
            'hasInternalLinks' => $hasInternalLinks,
            'contacts' => $contacts ?? [],
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
     */
    public function sendAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($request->getMethod() !== 'POST') {
            $this->throwStatus(405, 'Method not allowed');
        }

        $queryParams = json_decode($request->getQueryParams()['arguments'], true);

        $params = $request->getParsedBody();
        $this->senderUtility->mergeMailSettings($params);

        // check that all params are collected and valid
        // @TODO: return error if any required data is missing

        // init email template
        $this->emailView->setTemplate($params['template']);
        $this->emailView->setPid($queryParams['page']);

        // check for overrides
        if (isset($params['markerOverrides']) && sizeof($params['markerOverrides'])) {
            $this->emailView->overrideMarker($params['markerOverrides']);
        }

        // check for provider settings and possible list of recipients
        if (isset($params['provider']) && sizeof($params['provider']) && (int)$params['provider']['use'] === 1) {
            $providerSettings = $params['provider'];
            /** @var \Blueways\BwEmail\Service\ContactProvider $provider */
            $provider = GeneralUtility::makeInstance($providerSettings['id']);
            $provider->applyConfiguration($providerSettings[$providerSettings['id']]['optionsConfiguration']);
            $contacts = $provider->getContacts();

            $this->senderUtility->setRecipients($contacts);
        }

        $status = $this->senderUtility->sendEmailView($this->emailView);

        $response->getBody()->write(json_encode($status));
        return $response;
    }

    /**
     * @TODO: implement and connect with hook to use wizard as dynamic TCA element
     * @return array
     */
    protected function getViewData()
    {
        return [];
    }

}
