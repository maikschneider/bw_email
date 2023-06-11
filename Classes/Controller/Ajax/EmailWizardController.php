<?php

namespace Blueways\BwEmail\Controller\Ajax;

use Blueways\BwEmail\Domain\Model\Dto\WizardSettings;
use Blueways\BwEmail\Service\ContactProvider;
use Blueways\BwEmail\Utility\SenderUtility;
use Blueways\BwEmail\View\EmailView;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Error\Http\ServiceUnavailableException;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\Repository;
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

    /**
     * @throws ServiceUnavailableException
     */
    public function previewAction(ServerRequest $request): ResponseInterface
    {
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

        // inject records from typoscript (or tca override)
        foreach ($wizardSettings->typoscriptSelects ?? [] as $markerName => $typoscript) {
            $emailView->addTyposcriptSelect(substr($markerName, 0, -1), $typoscript);
        }

        // apply marker overrides
        $emailView->renderWithMarkerOverrides(null, $wizardSettings->markerOverrides);

        // @TODO: check for provider settings in post data
        if (isset($params['provider']) && count($params['provider']) && (int)$params['provider']['use'] === 1) {
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

        // check for internal links
        $content['hasInternalLinks'] = (bool)count($emailView->getInternalLinks());
        $content['marker'] = $emailView->getMarker();
        $html = $emailView->getHtml();
        $content['iframeSrc'] = 'data:text/html;charset=utf-8,' . self::encodeURIComponent($html);

        return new JsonResponse($content);
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
        if (isset($record['record_type']) && $record['record_type'] !== '') {
            // load record from repository (to make use of fluid getter/setter functions
            $recordTypeParts = explode('\\', $record['record_type']);
            $recordTypeParts[3] = 'Repository';
            $recordTypeParts[4] .= 'Repository';

            // use custom query to ignore hidden and pid field
            /** @var Typo3QuerySettings $querySettings */
            $querySettings = $this->objectManager->get(Typo3QuerySettings::class);
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
        $revert = ['%21' => '!', '%2A' => '*', '%27' => "'", '%28' => '(', '%29' => ')'];
        return strtr(rawurlencode($str), $revert);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws ServiceUnavailableException
     */
    public function sendAction(ServerRequestInterface $request): ResponseInterface
    {
        $response = new Response();

        // reconstruct wizard settings
        $body = $request->getParsedBody();
        $wizardSettings = WizardSettings::createFromPostData($body['wizardSettings'], $this->typoscript['settings']);

        /** @var SenderUtility $senderUtility */
        $senderUtility = GeneralUtility::makeInstance(SenderUtility::class);
        $senderUtility->setSettings($wizardSettings);

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

        // check for provider settings and possible list of recipients
        if (isset($params['provider']) && count($params['provider']) && (int)$params['provider']['use'] === 1) {
            $providerSettings = $params['provider'];
            /** @var ContactProvider $provider */
            $provider = GeneralUtility::makeInstance($providerSettings['id']);
            $provider->applyConfiguration($providerSettings[$providerSettings['id']]['optionsConfiguration']);
            $contacts = $provider->getContacts();

            $senderUtility->setRecipients($contacts);
        }

        $status = $senderUtility->sendEmailView($emailView);

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
