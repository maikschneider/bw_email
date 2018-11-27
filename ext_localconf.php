<?php
defined('TYPO3_MODE') || die('Access denied');

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = 'Blueways\\BwEmail\\Hooks\\ContentPostProcessor->render';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-cached'][] = 'Blueways\\BwEmail\\Hooks\\ContentPostProcessor->render';
