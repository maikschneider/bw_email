<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3_MODE') || die();

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
                'type' => 'sendMailButton',
                'size' => 30,
                'eval' => 'int',
                'recipientAddress' => 'FIELD:header',
                'senderAddress' => 'tca@bla.ex',
                'replytoAddress' => 'reply@tca.de'
            ],
        ]
    ];

    ExtensionManagementUtility::addTCAcolumns('tt_content', $tempColumns);
    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        'mail_button',
        '',
        'before:CType'
    );
});
