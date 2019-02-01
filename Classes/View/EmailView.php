<?php

namespace Blueways\BwEmail\View;

use Blueways\BwEmail\Utility\TemplateParserUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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

        $this->setLayoutRootPaths($typoscript['page.']['10.']['layoutRootPaths.']);
        $this->setPartialRootPaths($typoscript['page.']['10.']['partialRootPaths.']);
        $this->setTemplateRootPaths($typoscript['page.']['10.']['templateRootPaths.']);

        $this->templateParser = $this->objectManager->get('Blueways\\BwEmail\\Utility\\TemplateParserUtility');
    }

    /**
     * @param null $actionName
     * @return string
     */
    public function render($actionName = null)
    {
        if ($this->templateParser->getHtml() === null) {
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

        if ($marker === null) {
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
        $this->injectPageContentElements();
    }

    /**
     * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
     */
    protected function injectPageContentElements()
    {
        if (!$this->pid) {
            return;
        }

        $this->initTSFE($this->pid);
        $cObjRenderer = $this->objectManager->get('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
        $colPositions = [0 => 'defaultColumn', 1 => 'leftColumn', 2 => 'rightColumn'];
        foreach ($colPositions as $colPos => $colName) {
            $typoscriptSelect = [
                'table' => 'tt_content',
                'select.' => [
                    'pidInList' => $this->pid,
                    'where' => 'colPos=' . $colPos,
                    'orderBy' => 'sorting'
                ]
            ];
            $contentElements = $cObjRenderer->getContentObject('CONTENT')->render($typoscriptSelect);
            $this->assign($colName, $contentElements);
        }
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
