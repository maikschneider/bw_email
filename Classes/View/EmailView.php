<?php

namespace Blueways\BwEmail\View;

use Blueways\BwEmail\Domain\Model\Dto\EmailSettings;
use Blueways\BwEmail\Utility\SenderUtility;
use Blueways\BwEmail\Utility\TemplateParserUtility;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Class EmailView
 *
 * @package Blueways\BwEmails\View
 */
class EmailView extends \TYPO3\CMS\Fluid\View\StandaloneView
{

    /**
     * @var integer|null
     */
    protected $pid;

    /**
     * @var TemplateParserUtility;
     */
    protected $templateParser;

    public function __construct(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject = null)
    {
        parent::__construct($contentObject);

        $configurationManager = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
        $typoscript = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);

        $this->setLayoutRootPaths($typoscript['plugin.']['tx_bwemail.']['view.']['layoutRootPaths.']);
        $this->setPartialRootPaths($typoscript['plugin.']['tx_bwemail.']['view.']['partialRootPaths.']);
        $this->setTemplateRootPaths($typoscript['plugin.']['tx_bwemail.']['view.']['templateRootPaths.']);
        $this->setTemplate($typoscript['plugin.']['tx_bwemail.']['settings.']['template']);

        $this->templateParser = $this->objectManager->get('Blueways\\BwEmail\\Utility\\TemplateParserUtility');
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
            $rootline = \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($this->pid);
            $host = \TYPO3\CMS\Backend\Utility\BackendUtility::firstDomainRecord($rootline);
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
    public function overrideMarker($markerOverrides)
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
     * @param \Blueways\BwEmail\Domain\Model\Contact $contact
     */
    public function insertContact($contact)
    {
        $this->templateParser->insertContact($contact);
    }

    /**
     * @param $pid
     * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
    }

    /**
     * @param string $markerName
     * @param array $typoscript
     * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
     */
    public function injectTyposcriptSelect(string $markerName, array $typoscript)
    {
        if (!$pid = $this->pid) {
            return;
        }

        // check pid if FE Context can be created (not possible if sys_folder) or go page level upwards
        $rootline = \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($pid);
        for ($i = sizeof($rootline); $i > 0; $i--) {
            $pid = $rootline[$i]['doktype'];
            if ($pid === 1) {
                break;
            }
        }

        $this->initTSFE($pid);
        $cObjRenderer = $this->objectManager->get('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');

        if (isset($typoscript['render']) && $typoscript['render'] === '1') {
            $tsService = $this->objectManager->get(TypoScriptService::class);
            $ts = $tsService->convertPlainArrayToTypoScriptArray($typoscript);
            $contentElements = $cObjRenderer->getContentObject('CONTENT')->render($ts);
        } else {
            $tableName = $cObjRenderer->stdWrapValue('table', $typoscript);
            // fix: to make typoScript query work, we need to enter a pid
            if (!isset($typoscript['select']['pidInList'])) {
                $typoscript['select.']['pidInList'] = 'root,-1';
                $typoscript['select.']['recursive'] = '9';
            }
            $contentElements = $cObjRenderer->getRecords($tableName, $typoscript['select']);
        }

        $this->assign($markerName, $contentElements);
    }

    /**
     * @param int $id
     * @param int $typeNum
     * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
     */
    protected function initTSFE($id = 1, $typeNum = 0)
    {
        \TYPO3\CMS\Frontend\Utility\EidUtility::initTCA();
        if (!is_object($GLOBALS['TT'])) {
            $GLOBALS['TT'] = new \TYPO3\CMS\Core\TimeTracker\TimeTracker(false);
            $GLOBALS['TT']->start();
        }

        $GLOBALS['TSFE'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController',
            $GLOBALS['TYPO3_CONF_VARS'],
            $id,
            $typeNum
        );
        $GLOBALS['TSFE']->sys_page = $this->objectManager->get('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
        $GLOBALS['TSFE']->sys_page->init(true);
        $GLOBALS['TSFE']->connectToDB();
        $GLOBALS['TSFE']->initFEuser();
        $GLOBALS['TSFE']->determineId();
        $GLOBALS['TSFE']->initTemplate();
        $GLOBALS['TSFE']->rootLine = $GLOBALS['TSFE']->sys_page->getRootLine($id, '');
        $GLOBALS['TSFE']->getConfigArray();

        // @TODO: check new condition for TYPO3 v9 url handling
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('realurl')) {
            $rootline = \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($id);
            $host = \TYPO3\CMS\Backend\Utility\BackendUtility::firstDomainRecord($rootline);
            $_SERVER['HTTP_HOST'] = $host;
        }
    }

}
