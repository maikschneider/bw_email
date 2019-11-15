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
                'recipientAddress' => 'FIELD:header',
                'senderAddress' => 'tca@bla.ex',
                'replytoAddress' => 'reply@tca.de',
                'typoscriptSelects.' => [
                    'tt_content.' => [
                        'pages.' => [
                            'table' => 'pages',
                            'select.' => [
                                'uidInList' => 'FIELD:pid'
                            ]
                        ]
                    ]
                ],
            ],
        ]
    ];

    ExtensionManagementUtility::addTCAcolumns('tt_content', $tempColumns);
    /*
    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        'mail_button',
        '',
        'before:CType'
    );
    */
});
