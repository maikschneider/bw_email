<?php

namespace Blueways\BwEmail\Controller\Ajax;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

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
        $defaults = $this->getDefaultEmailSettings();
        $templates = $this->getTemplates();

        $this->templateView->assignMultiple([
            'formActionUri' => $formActionUri,
            'defaults' => $defaults,
            'templates' => $templates
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
        $uriArguments['signature'] = \TYPO3\CMS\Core\Utility\GeneralUtility::hmac(
            $uriArguments['arguments'],
            $routeName
        );
        $uriBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute($routeName);
    }

    /**
     * read typoscript for email settings
     *
     * @TODO: move to email helper utility
     * @return array
     */
    protected function getDefaultEmailSettings()
    {
        $defaults = array(
            'senderAddress' => $this->typoscript['plugin.']['tx_bwemail_pi1.']['settings.']['senderAddress'],
            'senderName' => $this->typoscript['plugin.']['tx_bwemail_pi1.']['settings.']['senderName'],
            'replytoAddress' => $this->typoscript['plugin.']['tx_bwemail_pi1.']['settings.']['replytoAddress'] ? $this->typoscript['plugin.']['tx_bwemail_pi1.']['settings.']['replytoAddress'] : $this->typoscript['plugin.']['tx_bwemail_pi1.']['settings.']['senderAddress'],
            'subject' => $this->typoscript['plugin.']['tx_bwemail_pi1.']['settings.']['subject'],
            'emailTemplate' => $this->typoscript['plugin.']['tx_bwemail_pi1.']['settings.']['template'],
            'showUid' => $this->typoscript['plugin.']['tx_bwemail_pi1.']['settings.']['showUid'] ?? null,
            'recipientAddress' => '',
            'recipientName' => '',
        );
        return $defaults;
    }

    /**
     * @return array
     */
    protected function getTemplates()
    {
        return [];
    }

    /**
     * @param \TYPO3\CMS\Core\Http\ServerRequest $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function previewAction(\TYPO3\CMS\Core\Http\ServerRequest $request, ResponseInterface $response)
    {
        $queryParams = json_decode($request->getQueryParams()['arguments'], true);

        // build the default template
        $this->templateView->setTemplate($queryParams['template']);
        $html = $this->templateView->render();

        // extract marker and replace html with overrides from params
        $templateParser = GeneralUtility::makeInstance(\Blueways\BwEmail\Utility\TemplateParserUtility::class, $html);
        $templateParser->parseMarker();

        // check for incoming marker overrides
        if ($request->getMethod() === 'POST') {
            $params = $request->getParsedBody();
            if (isset($params['markerOverrides']) && sizeof($params['markerOverrides'])) {
                $templateParser->overrideMarker($params['markerOverrides']);
            }
        }

        // check for internal links
        $hasInternalLinks = sizeof($templateParser->getInternalLinks()) ? true : false;

        // encode for display inside <iframe src="...">
        function encodeURIComponent($str)
        {
            $revert = array('%21' => '!', '%2A' => '*', '%27' => "'", '%28' => '(', '%29' => ')');
            return strtr(rawurlencode($str), $revert);
        }

        $src = 'data:text/html;charset=utf-8,' . encodeURIComponent($templateParser->getHtml());
        $marker = $templateParser->getMarker();

        // build and encode response
        $content = json_encode(array(
            'src' => $src,
            'marker' => $marker['name'],
            'markerContent' => $marker['content'],
            'hasInternalLinks' => $hasInternalLinks
        ));

        $response->getBody()->write($content);

        return $response;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function sendMailAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($request->getMethod() !== 'POST') {
            return $response->withStatus(405, 'Method not allowed');
        }

        $params = $request->getParsedBody();
        $entryUid = $params['entryUid'] ?? false;

        if (!$entryUid) {
            return $response->withStatus(400, 'Form error');
        }

        $mailSettings = $this->getDefaultEmailSettings();

        // override defaults with POST parameter
        array_walk($mailSettings, function (&$value, $key, $params) {
            if (isset($params[$key]) && $params[$key] && $params[$key] !== "") {
                $value = $params[$key];
            }
        }, $params);

        // check that all params are collected and valid
        // @TODO: implement check

        // get html template
        $entry = $this->entryRepository->findByUid($entryUid);
        $this->templateView->getRenderingContext()->setControllerName('Email');
        $this->templateView->setTemplate($mailSettings['emailTemplate']);
        $this->templateView->assign('entry', $entry);
        $this->templateView->assign('showUid', $mailSettings['showUid']);
        $html = $this->templateView->render();

        // check for overrides in POST and override html
        if (isset($params['markerOverrides']) && sizeof($params['markerOverrides'])) {
            $marker = $this->getMarkerInHtml($html);
            $html = $this->overrideMarkerContentInHtml($html, $marker, $params['markerOverrides']);
        }

        // hijack links and replace frontend links
        $html = $this->replaceInternalLinks($html, $mailSettings['showUid']);

        // actual send
        $message = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Mail\\MailMessage');
        $message->setTo($mailSettings['recipientAddress'], $mailSettings['recipientName'] ?? null)
            ->setFrom($mailSettings['senderAddress'], $mailSettings['senderName'] ?? null)
            ->setSubject($mailSettings['subject'])
            ->setBody($html, 'text/html');

        if ($mailSettings['senderAddress'] !== $mailSettings['replytoAddress']) {
            $message->setReplyTo($mailSettings['replytoAddress']);
        }

        $sendSuccess = $message->send();

        // sending successfull?
        if (!$sendSuccess) {
            // @TODO: return error
        }

        $content = array(
            'status' => 'OK',
            'message' => [
                'headline' => 'E-Mail send',
                'text' => 'Mail successfully send.'
            ]
        );
        $response->getBody()->write(json_encode($content));
        return $response;
    }

    /**
     * @param $html
     * @return array
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
     */
    protected function replaceInternalLinks($html, $pageUid)
    {
        $links = $this->getInternalLinks($html);

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
     */
    protected function getInternalLinks($html)
    {
        // find all links
        $regex = '/(<a[^>]href=")(.[^"]*)/';
        preg_match_all($regex, $html, $links);

        // abbort if no links were found
        if (!sizeof($links)) {
            return [];
        }

        // remove links that are not internal
        $links = array_filter($links[2], function ($uri) {
            return strpos($uri, '/typo3/index.php?M=');
        });

        return $links;
    }

    /**
     * @return array
     */
    protected function getViewData()
    {
        return [];
    }

    /**
     * @param $html
     * @param $marker
     * @return array
     */
    protected function getMarkerContentInHtml($html, $marker)
    {
        $content = [];
        foreach ($marker as $m) {
            preg_match('/(<!--\s+###' . $m . '###\s+-->)([\s\S]*)(<!--\s+###' . $m . '###\s+-->)/', $html, $result);
            $content[] = array(
                'name' => $m,
                'content' => $result[2]
            );
        }
        return $content;
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

    /**
     * @return array
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function getEmailTemplateSelection()
    {
        $pageTsConfig = BackendUtility::getPagesTSconfig(0);
        $emailTemplates = $pageTsConfig['TCEFORM.']['tt_content.']['pi_flexform.']['bwbookingmanager_pi1.']['email.']['settings.mail.template.']['addItems.'];
        $selection = [];
        foreach ($emailTemplates as $key => $emailTemplate) {
            $selection[] = array(
                'file' => $key,
                'name' => $this->getLanguageService()->sL($emailTemplate),
                'previewUri' => $this->getEmailPreviewUri($key)
            );
        }
        return $selection;
    }

    /**
     * Returns the LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @param string $emailTemplate
     * @return string
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function getEmailPreviewUri($emailTemplate)
    {
        $routeName = 'ajax_emailpreview';

        $newQueryParams = $this->queryParams;
        $newQueryParams['emailTemplate'] = $emailTemplate;

        $uriArguments['arguments'] = json_encode($newQueryParams);
        $uriArguments['signature'] = \TYPO3\CMS\Core\Utility\GeneralUtility::hmac(
            $uriArguments['arguments'],
            $routeName
        );

        $uriBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);

        return (string)$uriBuilder->buildUriFromRoute($routeName, $uriArguments);
    }
}
