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
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Viewpage\\Controller\\ViewModuleController'] = array(
            'className' => 'Blueways\\BwEmail\\Controller\\ViewModuleController'
        );
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Form\\Domain\\Finishers\\EmailFinisher'] = array(
            'className' => 'Blueways\\BwEmail\\Domain\\Finishers\\EmailFinisher'
        );

        // register custom TCA node field
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1533721568] = [
            'nodeName' => 'sendMailButton',
            'priority' => '70',
            'class' => \Blueways\BwEmail\Form\Element\SendMailButtonElement::class,
        ];
    },
    'bw_email'
);
