<?php

namespace Blueways\BwEmail\Hooks;

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use Blueways\BwEmail\Utility\TemplateParserUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ContentPostProcessorHook
 *
 * @package Blueways\BwEmail\Hooks
 */
class ContentPostProcessorHook
{

    /**
     * @param $parameters
     */
    public function noCache(&$parameters)
    {
        /** @var TypoScriptFrontendController $pobj */
        $pobj = $parameters['pObj'];
        $page = $pobj->page;
        if ($page['doktype'] !== 117) {
            return;
        }

        $templateParser = GeneralUtility::makeInstance(TemplateParserUtility::class, $pobj->content);
        $templateParser->inkyHtml();
        $templateParser->inlineCss();
        $templateParser->cleanHeadTag();

        $parameters['pObj']->content = $templateParser->getHtml();
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
