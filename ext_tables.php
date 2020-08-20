<?php
defined('TYPO3_MODE') || die('Access denied');

call_user_func(
    function ($extKey) {
        $emailDoktype = 117;

        // Add new page type:
        $GLOBALS['PAGES_TYPES'][$emailDoktype] = [
            'type' => 'email',
            'allowedTables' => '*',
        ];

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
            '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:bw_email/Configuration/PageTS/All.typoscript">'
        );

        // Provide icon for page tree, list view, ... :
        $iconNames = [
            'actions-see',
            'actions-unsee',
            'actions-replyall',
            'actions-mail',
            'actions-forward',
            'actions-reply',
            'actions-compose',
            'apps-pagetree-page-email',
            'actions-email'
        ];
        $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
        foreach ($iconNames as $iconName) {
            $iconRegistry->registerIcon(
                $iconName,
                TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
                [
                    'source' => 'EXT:' . $extKey . '/Resources/Public/Icons/' . $iconName . '.svg',
                ]
            );
        }

        // Allow backend users to drag and drop the new page type:
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig(
            'options.pageTree.doktypesToShowInNewPageDragArea := addToList(' . $emailDoktype . ')'
        );

        // Register backend module
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            'Blueways.BwEmail',
            'tools',
            'tx_email',
            'top',
            array(
                'Administration' => 'inbox, index, errorLog, contactList, preview, showLog',
                'Imap' => 'inbox, showMail'
            ),
            array(
                'access' => 'admin',
                'icon' => 'EXT:bw_email/Resources/Public/Icons/module-email.svg',
                'labels' => 'LLL:EXT:bw_email/Resources/Private/Language/locallang.xlf:module.name',
            )
        );
    },
    'bw_email'
);
