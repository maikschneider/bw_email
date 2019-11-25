<?php

namespace Blueways\BwEmail\Controller\Ajax;

use Blueways\BwEmail\Utility\SenderUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
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
            $templateView->setLayoutRootPaths($this->typoscript['plugin.']['tx_bwemail.']['view.']['layoutRootPaths.']);
            $templateView->setPartialRootPaths($this->typoscript['plugin.']['tx_bwemail.']['view.']['partialRootPaths.']);
            $templateView->setTemplateRootPaths($this->typoscript['plugin.']['tx_bwemail.']['view.']['templateRootPaths.']);
        }

        $this->templateView = $templateView;
        $this->uriBuilder = $this->objectManager->get('TYPO3\\CMS\\Backend\\Routing\\UriBuilder');
        $this->emailView = $this->objectManager->get('Blueways\\BwEmail\\View\\EmailView');
        $this->senderUtility = GeneralUtility::makeInstance(
            'Blueways\BwEmail\Utility\SenderUtility',
            $this->typoscript
        );
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

        $formActionUri = $this->getAjaxUri('ajax_wizard_modal_send');

        $defaults = $this->queryParams;

        $templates = $this->getTemplates();

        $providers = [];
        if (isset($defaults['provider.'])) {
            foreach ($defaults['provider.'] as $providerClass => $providerSettings) {
                /** @var \Blueways\BwEmail\Service\ContactProvider $provider */
                $provider = GeneralUtility::makeInstance(substr($providerClass, 0, -1));
                $provider->applySettings($providerSettings);
                $providers[] = $provider->getModalConfiguration();
            }
        }

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
                    $this->queryParams
                )
            );
        }
        return $selection;
    }

    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
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

        // init email template
        $this->emailView->setTemplate($queryParams['template']);
        $this->emailView->setPid($queryParams['pid']);

        // inject current record
        $record = $this->getRecord($queryParams['uid'], $queryParams['table']);
        $this->emailView->assign('record', $record);

        // inject records from typoscript (or tca override
        if (is_array($queryParams['typoscriptSelects.'])) {
            foreach ($queryParams['typoscriptSelects.'] as $markerName => $typoscript) {
                $this->emailView->injectTyposcriptSelect(substr($markerName, 0, -1), $typoscript);
            }
        }

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
     * @param $uid
     * @param $table
     * @return array|mixed
     */
    private function getRecord($uid, $table)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        $record = $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq('uid', $uid)
            )
            ->execute()
            ->fetch();

        // the record is just an array, we need to query the repository to access all properties with fluid
        if (isset($record['record_type']) && $record['record_type'] !== "") {

            // load record from repository (to make use of fluid getter/setter functions
            $recordTypeParts = explode("\\", $record['record_type']);
            $recordTypeParts[3] = 'Repository';
            $recordTypeParts[4] .= 'Repository';

            // use custom query to ignore hidden and pid field
            /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings $querySettings */
            $querySettings = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings');
            $querySettings->setIgnoreEnableFields(true);
            $querySettings->setRespectStoragePage(false);
            $querySettings->setIncludeDeleted(true);
            /** @var \TYPO3\CMS\Extbase\Persistence\Repository $repository */
            $repository = $this->objectManager->get(implode('\\', $recordTypeParts));
            $repository->setDefaultQuerySettings($querySettings);
            $query = $repository->createQuery();
            $query->matching($query->equals('uid', $uid));
            $record = $query->execute()->toArray();
            $record = $record[0];

            // manually load lazy related properties since fluid template is not able to in standalone view
            $properties = ObjectAccess::getGettableProperties($record);
            foreach ($properties as $propertyName => $property) {
                if ($property instanceof LazyLoadingProxy) {
                    \TYPO3\CMS\Extbase\Reflection\ObjectAccess::setProperty(
                        $record,
                        $propertyName,
                        $property->_loadRealInstance()
                    );
                }
            }
        }

        return $record;
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

        // security: check signature
        if (!$this->isSignatureValid($request, 'ajax_wizard_modal_send')) {
            return $response->withStatus(403);
        }

        $queryParams = json_decode($request->getQueryParams()['arguments'], true);

        $params = $request->getParsedBody();

        /** @var SenderUtility $senderUtility */
        $senderUtility = GeneralUtility::makeInstance(SenderUtility::class);
        $senderUtility->setSettings($queryParams);
        $senderUtility->mergeMailSettings($params);

        // check that all params are collected and valid
        // @TODO: return error if any required data is missing

        // init email template
        $this->emailView->setTemplate($params['template']);
        $this->emailView->setPid($queryParams['pid']);

        // inject current record
        $record = $this->getRecord($queryParams['uid'], $queryParams['table']);
        $this->emailView->assign('record', $record);

        // inject records from typoscript (or tca override
        if (is_array($queryParams['typoscriptSelects.'])) {
            foreach ($queryParams['typoscriptSelects.'] as $markerName => $typoscript) {
                $this->emailView->injectTyposcriptSelect(substr($markerName, 0, -1), $typoscript);
            }
        }

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

            $senderUtility->setRecipients($contacts);
        }

        $status = $senderUtility->sendEmailView($this->emailView);

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
