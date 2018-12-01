<?php

namespace Blueways\BwEmail\Hooks;

use Hampe\Inky\Inky;

class ContentPostProcessorHook
{
    public function noCache(&$parameters)
    {

        $pobj = $parameters['pObj'];
        $page = $pobj->page;
        if($page['doktype'] !== 117) {
           return;
        }

        $gridColumns = 12; //optional, default is 12
        $additionalComponentFactories = []; //optional
        $inky = new Inky($gridColumns, $additionalComponentFactories);

        $pobj->content = $inky->releaseTheKraken($pobj->content);

        //\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($pobj, __LINE__ . ' in ' . __CLASS__);
    }

    public function cache(&$parameters)
    {
        $pobj = $parameters['pObj'];
        $page = $pobj->page;
        if ($page['doktype'] !== 117) {
            return;
        }

        // \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($parameters, __LINE__ . ' in ' . __CLASS__ );
    }
}
