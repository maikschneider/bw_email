<?php
defined('TYPO3_MODE') || die('Access denied');

call_user_func(
    function ($extKey, $table) {
        $emailDoktype = 117;

        // Add new page type as possible select item:
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
            $table,
            'doktype',
            [
                'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang.xlf:email_page_type',
                $emailDoktype,
                'EXT:' . $extKey . '/Resources/Public/Icons/apps-pagetree-page-email.svg'
            ],
            '1',
            'after'
        );
    },
    'bw_email',
    'pages_language_overlay'
);
