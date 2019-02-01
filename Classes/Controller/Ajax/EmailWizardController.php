<?php

namespace Blueways\BwEmail\Controller\Ajax;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class EmailWizardController
 *
 * @package Blueways\BwEmail\Controller\Ajax
 */
class EmailWizardController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    /**
     * @var array
     */
    protected $queryParams = null;

    /**
     * @var UriBuilder
     */
    protected $uriBuilder;

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
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public function modalAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->queryParams = json_decode($request->getQueryParams()['arguments'], true);

        $formActionUri = $this->getSendUri();
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
     * @return string
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function getSendUri()
    {
        $routeName = 'ajax_wizard_modal_send';
        $uriArguments['arguments'] = json_encode([

        ]);
        $uriArguments['signature'] = GeneralUtility::hmac(
            $uriArguments['arguments'],
            $routeName
        );
        return (string)$this->uriBuilder->buildUriFromRoute($routeName);
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

        $params = $request->getParsedBody();
        $this->senderUtility->mergeMailSettings($params);

        // check that all params are collected and valid
        // @TODO: return error if anything required is missing

        // init email template
        $this->emailView->setTemplate($params['template']);
        $this->emailView->setPid($params['page']);

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
     * @param $html
     * @return array
     * @deprecated
     */
    protected function getMarkerInHtml($html)
    {
        preg_match_all('/(<!--\s+###)([\w\d]\w+)(###\s+-->)/', $html, $foundMarker);

        // abort if no marker were found
        if (!sizeof($foundMarker[2])) {
            return [];
        }

        // ensure that two markers were found
        $markerOccurrences = array_count_values($foundMarker[2]);
        $markerOccurrences = array_filter($markerOccurrences, function ($occurrences) {
            return $occurrences === 2 ? true : false;
        });

        return array_keys($markerOccurrences);
    }

    /**
     * @param $html
     * @param $marker
     * @param $overrides
     * @return mixed
     * @deprecated
     */
    protected function overrideMarkerContentInHtml($html, $marker, $overrides)
    {
        // abbort if no overrides
        if (!$overrides || !sizeof($overrides)) {
            return $html;
        }

        // checks that there are no overrides for marker that dont exist
        $validOverrides = array_intersect($marker, array_keys($overrides));

        foreach ($validOverrides as $overrideName) {
            // abbort if no override content
            if (!$overrides[$overrideName]) {
                continue;
            }

            // replace everything from marker start to marker end with override content
            $regex = '/<!--\s+###' . $overrideName . '###\s+-->[\s\S]*<!--\s+###' . $overrideName . '###\s+-->/';
            $html = preg_replace($regex, $overrides[$overrideName], $html);
        }

        return $html;
    }

    /**
     * @param $html
     * @param $pageUid
     * @return string
     * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
     * @deprecated
     */
    protected function replaceInternalLinks($html, $pageUid)
    {
        //$links = $this->getInternalLinks($html);
        $links = $this->getLinks($html);

        foreach ($links as $rawLink) {

            // extract parameters
            $link = htmlspecialchars_decode(urldecode($rawLink));
            preg_match_all('/(tx_bwbookingmanager_pi1\[)([\w]+)(\]=)([\w]+)(&|$)/', $link, $linkArgs);

            // create new link
            $getArgs = [];
            for ($i = 0; $i < sizeof($linkArgs[0]); $i++) {
                $getArgs['tx_bwbookingmanager_pi1[' . $linkArgs[2][$i] . ']'] = $linkArgs[4][$i];
            }

            // initialize time tracker
            if (!is_object($GLOBALS['TT'])) {
                $GLOBALS['TT'] = new TimeTracker();
                $GLOBALS['TT']->start();
            }

            // initialize TSFE
            if (!is_object($GLOBALS['TSFE'])) {
                /** @var TypoScriptFrontendController */
                $GLOBALS['TSFE'] = GeneralUtility::makeInstance(
                    TypoScriptFrontendController::class,
                    $GLOBALS['TYPO3_CONF_VARS'],
                    $pageUid,
                    0
                );
                $GLOBALS['TSFE']->connectToDB();
                $GLOBALS['TSFE']->initFEuser();
                $GLOBALS['TSFE']->determineId();
                $GLOBALS['TSFE']->initTemplate();
                $GLOBALS['TSFE']->getConfigArray();
            }

            // make it work with realurl too
            if (ExtensionManagementUtility::isLoaded('realurl')) {
                $rootLine = BackendUtility::BEgetRootLine($pageUid);
                $host = BackendUtility::firstDomainRecord($rootLine);
                $_SERVER['HTTP_HOST'] = $host;
            }

            // create uri by typolink helper
            $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $uri = $cObj->typolink_URL([
                'parameter' => $pageUid,
                'linkAccessRestrictedPages' => 1,
                'forceAbsoluteUrl' => 1,
                'useCacheHash' => 1,
                'additionalParams' => GeneralUtility::implodeArrayForUrl(null, $getArgs),
            ]);

            // replace link with new absolute one
            $html = str_replace($rawLink, $uri, $html);
        }

        return $html;
    }

    /**
     * @param $html
     * @return array
     * @deprecated
     */
    protected function getLinks($html)
    {
        // find all links
        $regex = '/(<a[^>]href=")(.[^"]*)/';
        preg_match_all($regex, $html, $links);

        // abort if no links were found
        if (!sizeof($links)) {
            return [];
        }

        return $links;
    }

    /**
     * @TODO: implement and connect with hook to use wizard as dynamic TCA element
     * @return array
     */
    protected function getViewData()
    {
        return [];
    }

    /**
     * @param string $emailTemplate
     * @return string
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function getPreviewUri($template)
    {
        $routeName = 'ajax_wizard_modal_preview';

        $newQueryParams = $this->queryParams;
        $newQueryParams['template'] = $template;

        $uriArguments['arguments'] = json_encode($newQueryParams);
        $uriArguments['signature'] = GeneralUtility::hmac(
            $uriArguments['arguments'],
            $routeName
        );

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        return (string)$uriBuilder->buildUriFromRoute($routeName, $uriArguments);
    }

}
