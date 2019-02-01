<?php
defined('TYPO3_MODE') || die('Access denied');

call_user_func(
    function ($extKey) {

        // register post content renderer hook
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = \Blueways\BwEmail\Hooks\ContentPostProcessorHook::class . '->noCache';
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'][] = \Blueways\BwEmail\Hooks\ContentPostProcessorHook::class . '->cache';

        // register Xclasses
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Backend\\Controller\\PageLayoutController'] = array(
            'className' => 'Blueways\\BwEmail\\Controller\\PageLayoutController'
        );
    },
    'bw_email'
);
