<?php
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
defined('TYPO3') || die();

call_user_func(function () {

    /**
     * TypoScript Tempalte
     */
    ExtensionManagementUtility::addStaticFile(
        'bw_email',
        'Configuration/TypoScript',
        'blueways Email: Typoscript templates'
    );
});
