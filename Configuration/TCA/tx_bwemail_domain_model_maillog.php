<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:bw_email/Resources/Private/Language/locallang_db.xlf:tx_bwemail_domain_model_maillog',
        'label' => 'recipient_address',
        'crdate' => 'crdate',
        'searchFields' => 'recipient_address,recipient_name,subject,status,send_date,sender_name,sender_address,sender_replyto',
        'iconfile' => 'EXT:bw_email/Resources/Public/Icons/tx_bwemail_domain_model_maillog.svg',
    ],
    'interface' => [
        'showRecordFieldList' => 'recipient_address,recipient_name,subject,status,send_date,sender_name,sender_address,sender_replyto',
    ],
    'types' => [
        0 => ['showitem' => 'recipient_address,recipient_name,subject,status,send_date,sender_name,sender_address,sender_replyto']
    ],
    'columns' => [

        'recipient_address' => [
            'label' => 'LLL:EXT:bw_email/Resources/Private/Language/locallang_db.xlf:tx_bwemail_domain_model_maillog.recipient_address',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'recipient_name' => [
            'label' => 'LLL:EXT:bw_email/Resources/Private/Language/locallang_db.xlf:tx_bwemail_domain_model_maillog.recipient_name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'subject' => [
            'label' => 'LLL:EXT:bw_email/Resources/Private/Language/locallang_db.xlf:tx_bwemail_domain_model_maillog.subject',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'sender_name' => [
            'label' => 'LLL:EXT:bw_email/Resources/Private/Language/locallang_db.xlf:tx_bwemail_domain_model_maillog.sender_name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'sender_address' => [
            'label' => 'LLL:EXT:bw_email/Resources/Private/Language/locallang_db.xlf:tx_bwemail_domain_model_maillog.sender_address',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'sender_replyto' => [
            'label' => 'LLL:EXT:bw_email/Resources/Private/Language/locallang_db.xlf:tx_bwemail_domain_model_maillog.sender_replyto',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],

        'status' => [
            'label' => 'LLL:EXT:bw_email/Resources/Private/Language/locallang_db.xlf:tx_bwemail_domain_model_maillog.status',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:bw_email/Resources/Private/Language/locallang_db.xlf:tx_bwemail_domain_model_maillog.status.0',
                        0
                    ],
                    [
                        'LLL:EXT:bw_email/Resources/Private/Language/locallang_db.xlf:tx_bwemail_domain_model_maillog.status.1',
                        1
                    ],
                    [
                        'LLL:EXT:bw_email/Resources/Private/Language/locallang_db.xlf:tx_bwemail_domain_model_maillog.status.2',
                        2
                    ]
                ]
            ],
        ],
        'start_date' => [
            'exclude' => true,
            'label' => 'LLL:EXT:bw_email/Resources/Private/Language/locallang_db.xlf:tx_bwemail_domain_model_maillog.send_date',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime,int',
            ],
        ],
    ]
];

