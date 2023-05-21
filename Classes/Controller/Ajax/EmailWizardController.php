<?php

namespace Blueways\BwEmail\Controller\Ajax;

use Blueways\BwEmail\Domain\Model\Dto\WizardSettings;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Blueways\BwEmail\View\EmailView;
use Blueways\BwEmail\Service\ContactProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Error\Http\ServiceUnavailableException;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
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

class EmailWizardController extends ActionController
{

    protected ?array $queryParams = null;

    protected array $typoscript = [];

    protected EmailView $emailView;

    protected SenderUtility $senderUtility;

    public function __construct(TypoScriptService $typoScriptService, ConfigurationManager $configurationManager)
    {
        $typoscript = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        $this->typoscript = $typoScriptService->convertTypoScriptArrayToPlainArray($typoscript['plugin.']['tx_bwemail.']);
    }

    public function modalAction(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();

        $wizardSettings = new WizardSettings(
            $params['tableName'],
            $params['uid'],
            $this->typoscript['settings']
        );

        $wizardView = GeneralUtility::makeInstance(StandaloneView::class);
        $wizardView->assign('wizardSettings', $wizardSettings);
        $wizardView->setTemplatePathAndFilename('EXT:bw_email/Resources/Private/Templates/Email/Administration/EmailWizard.html');

        $response = new Response();
        $content = $wizardView->render();
        $response->getBody()->write($content);

        return $response;
    }

    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    public function previewAction(ServerRequest $request): ResponseInterface
    {
        $response = new Response();

        // reconstruct wizard settings
        $body = $request->getParsedBody();
        $wizardSettings = WizardSettings::createFromPostData($body['wizardSettings'], $this->typoscript['settings']);

        // init email template
        $emailView = GeneralUtility::makeInstance(EmailView::class);
        $emailView->setLayoutRootPaths($this->typoscript['view']['layoutRootPaths']);
        $emailView->setPartialRootPaths($this->typoscript['view']['partialRootPaths']);
        $emailView->setTemplateRootPaths($this->typoscript['view']['templateRootPaths']);
        $emailView->setTemplate($wizardSettings->template);

        // inject current record
        $record = $this->getRecord($wizardSettings->uid, $wizardSettings->tableName);
        $emailView->assign('record', $record);

        // inject records from typoscript (or tca override
        foreach ($wizardSettings->typoscriptSelects ?? [] as $markerName => $typoscript) {
            $emailView->addTyposcriptSelect(substr($markerName, 0, -1), $typoscript);
        }

        if ($request->getMethod() === 'POST') {
            $params = $request->getParsedBody();

            // check for incoming marker overrides
            if (isset($params['markerOverrides']) && sizeof($params['markerOverrides'])) {
                $emailView->overrideMarker($params['markerOverrides']);
            }

            // check for provider settings in post data
            if (isset($params['provider']) && sizeof($params['provider']) && (int)$params['provider']['use'] === 1) {
                $providerSettings = $params['provider'];
                /** @var ContactProvider $provider */
                $provider = GeneralUtility::makeInstance($providerSettings['id']);
                $provider->applyConfiguration($providerSettings[$providerSettings['id']]['optionsConfiguration']);
                $contacts = $provider->getContacts();

                $selectedContactIndex = 0;
                if (isset($providerSettings[$providerSettings['id']]['selectedContact'])) {
                    $selectedContactIndex = $providerSettings[$providerSettings['id']]['selectedContact'];
                }
                $contact = $contacts[$selectedContactIndex];
                $emailView->insertContact($contact);
            }
        }

        // check for internal links
        $hasInternalLinks = count($emailView->getInternalLinks()) ? true : false;
        $marker = $emailView->getMarker();
        $html = $emailView->render();
        $content = 'data:text/html;charset=utf-8,' . self::encodeURIComponent($html);

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
            /** @var Typo3QuerySettings $querySettings */
            $querySettings = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings');
            $querySettings->setIgnoreEnableFields(true);
            $querySettings->setRespectStoragePage(false);
            $querySettings->setIncludeDeleted(true);
            /** @var Repository $repository */
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
                    ObjectAccess::setProperty(
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
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws ServiceUnavailableException
     * @throws StopActionException
     * @throws UnsupportedRequestTypeException
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
        $emailView->setTemplate($params['template']);
        $this->emailView->setPid($queryParams['pid']);

        // inject current record
        $record = $this->getRecord($queryParams['uid'], $queryParams['table']);
        $this->emailView->assign('record', $record);

        // inject records from typoscript (or tca override
        if (is_array($queryParams['typoscriptSelects.'])) {
            foreach ($queryParams['typoscriptSelects.'] as $markerName => $typoscript) {
                $this->emailView->addTyposcriptSelect(substr($markerName, 0, -1), $typoscript);
            }
        }

        // check for overrides
        if (isset($params['markerOverrides']) && sizeof($params['markerOverrides'])) {
            $this->emailView->overrideMarker($params['markerOverrides']);
        }

        // check for provider settings and possible list of recipients
        if (isset($params['provider']) && sizeof($params['provider']) && (int)$params['provider']['use'] === 1) {
            $providerSettings = $params['provider'];
            /** @var ContactProvider $provider */
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
