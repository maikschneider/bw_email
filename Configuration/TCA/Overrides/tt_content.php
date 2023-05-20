<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die();

call_user_func(function () {

    // @TODO remove before feature branch merge
    /**
     * Register new fields
     */
    $tempColumns = [
        'mail_button' => [
            'exclude' => false,
            'label' => 'Insert label here',
            'config' => [
                'type' => 'passthrough',
                'renderType' => 'sendMailButton'
            ],
        ]
    ];

    /*
    ExtensionManagementUtility::addTCAcolumns('tt_content', $tempColumns);
    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        'mail_button',
        '',
        'before:CType'
    );
    */
});
