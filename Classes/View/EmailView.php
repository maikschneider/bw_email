<?php

namespace Blueways\BwEmail\View;

use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use Blueways\BwEmail\Domain\Model\Contact;
use TYPO3\CMS\Core\Error\Http\ServiceUnavailableException;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Blueways\BwEmail\Utility\TemplateParserUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Class EmailView
 *
 * @package Blueways\BwEmails\View
 */
class EmailView extends StandaloneView
{

    /**
     * @var integer|null
     */
    protected $pid;

    /**
     * @var TemplateParserUtility;
     */
    protected $templateParser;

    protected ContentObjectRenderer $contentObjectRenderer;

    protected PageRepository $pageRepository;

    public function __construct(PageRepository $pageRepository, ContentObjectRenderer $contentObjectRenderer = null)
    {
        parent::__construct($contentObjectRenderer);
        $this->contentObjectRenderer = $contentObjectRenderer;
        $this->pageRepository = $pageRepository;

        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        $typoscript = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);

        $this->setLayoutRootPaths($typoscript['plugin.']['tx_bwemail.']['view.']['layoutRootPaths.'] ?? []);
        $this->setPartialRootPaths($typoscript['plugin.']['tx_bwemail.']['view.']['partialRootPaths.'] ?? []);
        $this->setTemplateRootPaths($typoscript['plugin.']['tx_bwemail.']['view.']['templateRootPaths.'] ?? []);

        $this->templateParser = GeneralUtility::makeInstance(TemplateParserUtility::class);
    }

    /**
     * @param null $actionName
     * @return string
     */
    public function render($actionName = null)
    {
        if (empty($this->templateParser->getHtml())) {
            $this->templateParser->setHtml(parent::render($actionName));
        }

        $this->templateParser->inkyHtml();

        if ($this->pid) {
            $rootline = BackendUtility::BEgetRootLine($this->pid);
            $host = BackendUtility::firstDomainRecord($rootline);
        }
        $host = isset($host) ? $host : GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
        $this->templateParser->makeAbsoluteUrls($host);

        $this->templateParser->inlineCss();
        $this->templateParser->cleanHeadTag();

        return $this->templateParser->getHtml();
    }

    /**
     * @return array
     */
    public function getMarker(): array
    {
        $marker = $this->templateParser->getMarker();

        if (empty($marker)) {
            $html = parent::render();
            $this->templateParser->setHtml($html);
            $this->templateParser->parseMarker();
            $marker = $this->templateParser->getMarker();
        }

        return $marker ?? [];
    }

    /**
     * @param array $markerOverrides
     */
    public function overrideMarker(array $markerOverrides)
    {
        $marker = $this->templateParser->getMarker();

        if ($marker === null) {
            $html = parent::render();
            $this->templateParser->setHtml($html);
            $this->templateParser->parseMarker();
        }

        $this->templateParser->overrideMarker($markerOverrides);
    }

    /**
     * @return array
     */
    public function getInternalLinks()
    {
        return $this->templateParser->getInternalLinks();
    }

    /**
     * @param Contact $contact
     */
    public function insertContact($contact)
    {
        $this->templateParser->insertContact($contact);
    }

    /**
     * @param $pid
     * @throws ServiceUnavailableException
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
    }

    /**
     * @param string $markerName
     * @param array $typoscript
     * @throws ServiceUnavailableException
     */
    public function addTyposcriptSelect(string $markerName, array $typoscript)
    {
        if (!$pid = $this->pid) {
            return;
        }

        // check pid if FE Context can be created (not possible if sys_folder) or go page level upwards
        $rootline = BackendUtility::BEgetRootLine($pid);
        for ($i = sizeof($rootline); $i > 0; $i--) {
            $pid = $rootline[$i]['doktype'];
            if ($pid === 1) {
                break;
            }
        }

        $this->initTSFE($pid);

        if (isset($typoscript['render']) && $typoscript['render'] === '1') {
            $contentElements = $this->contentObjectRenderer->getContentObject('CONTENT')->render($typoscript);
        } else {
            $tableName = $this->contentObjectRenderer->stdWrapValue('table', $typoscript);
            // fix: to make typoScript query work, we need to enter a pid
            if (!isset($typoscript['select.']['pidInList'])) {
                $typoscript['select.']['pidInList'] = 'root,-1';
                $typoscript['select.']['recursive'] = '9';
            }
            $contentElements = $this->contentObjectRenderer->getRecords($tableName, $typoscript['select.']);
        }

        $this->assign($markerName, $contentElements);
    }

    /**
     * @param int $id
     * @param int $typeNum
     * @throws ServiceUnavailableException
     */
    protected function initTSFE($id = 1, $typeNum = 0)
    {
        if (!is_object($GLOBALS['TT'])) {
            $GLOBALS['TT'] = new TimeTracker(false);
            GeneralUtility::makeInstance(TimeTracker::class)->start();
        }

        $GLOBALS['TSFE'] = GeneralUtility::makeInstance(
            'TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController',
            $GLOBALS['TYPO3_CONF_VARS'],
            $id,
            $typeNum
        );
        $GLOBALS['TSFE']->sys_page = $this->objectManager->get(PageRepository::class);
        $GLOBALS['TSFE']->sys_page->init(true);
        $GLOBALS['TSFE']->connectToDB();
        $GLOBALS['TSFE']->determineId();
        $GLOBALS['TSFE']->rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $id, '')->get();
        $GLOBALS['TSFE']->getConfigArray();

        // @TODO: check new condition for TYPO3 v9 url handling
        if (ExtensionManagementUtility::isLoaded('realurl')) {
            $rootline = BackendUtility::BEgetRootLine($id);
            $host = BackendUtility::firstDomainRecord($rootline);
            $_SERVER['HTTP_HOST'] = $host;
        }
    }

}
