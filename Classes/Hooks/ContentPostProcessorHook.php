<?php

namespace Blueways\BwEmail\Hooks;

use Hampe\Inky\Inky;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContentPostProcessorHook
{

    /**
     * @param $parameters
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function noCache(&$parameters)
    {
        /** @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $pobj */
        $pobj = $parameters['pObj'];
        $page = $pobj->page;
        if ($page['doktype'] !== 117) {
            return;
        }

        $this->inkyMarkup($pobj);
        $this->inlineCss($pobj);
        $this->cleanHeadTag($pobj);
        //\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($parameters, __LINE__ . ' in ' . __CLASS__);
    }

    /**
     * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $pobj
     */
    private function inkyMarkup($pobj)
    {
        $gridColumns = 12; //optional, default is 12
        $additionalComponentFactories = []; //optional
        $inky = new Inky($gridColumns, $additionalComponentFactories);

        try {
            $pobj->content = $inky->releaseTheKraken($pobj->content);
        } catch (\PHPHtmlParser\Exceptions\CircularException $e) {
        }
    }

    /**
     * @return array
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    private function loadAllTypoScriptSetup()
    {
        /** @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManager $configurationManager */
        $configurationManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
        $typoscript = $configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);

        return $typoscript['page.'];
    }

    /**
     * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $pobj
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    private function inlineCss($pobj)
    {
        $typoscript = $this->loadAllTypoScriptSetup();
        $cssFiles = $typoscript['includeCSS.'];
        $css = '';
        foreach ($cssFiles as $cssFile) {
            $cssFilePath = GeneralUtility::getFileAbsFileName($cssFile);
            $css .= file_get_contents($cssFilePath);
        }

        $cssToInlineStyles = new CssToInlineStyles();
        $pobj->content = $cssToInlineStyles->convert(
            $pobj->content,
            $css
        );
    }

    /**
     * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $pobj
     */
    protected function cleanHeadTag($pobj)
    {
        // remove stylesheet tags
        $pobj->content = preg_replace(
            '/<link (?="[^">]*rel=\s*[\'"]stylesheet[\'"])(?![^>]*href=\s*[\'"]http)[^>]*>/i',
            '',
            $pobj->content
        );
    }

    /**
     * @param $parameters
     */
    public function cache(&$parameters)
    {
        $pobj = $parameters['pObj'];
        $page = $pobj->page;
        if ($page['doktype'] !== 117) {
            return;
        }
    }
}
