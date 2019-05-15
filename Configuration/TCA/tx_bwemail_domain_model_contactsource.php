<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:bw_email/Resources/Private/Language/locallang_db.xlf:tx_bwemail_domain_model_contactsource',
        'label' => 'name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'type' => 'record_type',
        'searchFields' => 'name',
        'iconfile' => 'EXT:bw_email/Resources/Public/Icons/tx_bwemail_domain_model_contactsource.svg',
    ],
    'interface' => [
        'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, record_type, name',
    ],
    'types' => [
        'Blueways\BwEmail\Domain\Model\ContactSource' => ['showitem' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, record_type, name'],
        'Blueways\BwEmail\Domain\Model\FeUserContactSource' => ['showitem' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, record_type, name, fe_recipient_type, fe_users, fe_user_groups, fe_pid'],
    ],
    'columns' => [
        'record_type' => [
            'label' => 'LLL:EXT:bw_email/Resources/Private/Language/locallang_db.xlf:tx_bwemail_domain_model_contactsource.record_type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:bw_email/Resources/Private/Language/locallang_db.xlf:tx_bwemail_domain_model_contactsource.record_type.none',
                        'Blueways\BwEmail\Domain\Model\ContactSource'
                    ],
                    [
                        'LLL:EXT:bw_email/Resources/Private/Language/locallang_db.xlf:tx_bwemail_domain_model_contactsource.record_type.feuser',
                        'Blueways\BwEmail\Domain\Model\FeUserContactSource'
                    ],
                ],
                'default' => 'Blueways\BwEmail\Domain\Model\ContactSource',
            ],
        ],
        'sys_language_uid' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'special' => 'languages',
                'items' => [
                    [
                        'LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages',
                        -1,
                        'flags-multiple',
                    ],
                ],
                'default' => 0,
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_bwemail_domain_model_contactsource',
                'foreign_table_where' => 'AND tx_bwemail_domain_model_contactsource.pid=###CURRENT_PID### AND tx_bwemail_domain_model_contactsource.sys_language_uid IN (-1,0)',
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'hidden' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:lang/locallang_core.xlf:labels.enabled',
                    ],
                ],
            ],
        ],

        'name' => [
            'label' => 'LLL:EXT:bw_email/Resources/Private/Language/locallang_db.xlf:tx_bwemail_domain_model_contactsource.name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'fe_recipient_type' => [
            'label' => 'LLL:EXT:bw_email/Resources/Private/Language/locallang_db.xlf:tx_bwemail_domain_model_contactsource.fe_recipient_type',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:bw_email/Resources/Private/Language/locallang_db.xlf:tx_bwemail_domain_model_contactsource.fe_recipient_type.0',
                        0
                    ],
                    [
                        'LLL:EXT:bw_email/Resources/Private/Language/locallang_db.xlf:tx_bwemail_domain_model_contactsource.fe_recipient_type.1',
                        1
                    ],
                    [
                        'LLL:EXT:bw_email/Resources/Private/Language/locallang_db.xlf:tx_bwemail_domain_model_contactsource.fe_recipient_type.2',
                        2
                    ]
                ]
            ],
        ],
        'fe_users' => [
            'exclude' => true,
            'label' => 'LLL:EXT:bw_email/Resources/Private/Language/locallang_db.xlf:tx_bwemail_domain_model_contactsource.fe_users',
            'displayCond' => 'FIELD:fe_recipient_type:=:1',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'internal_type' => 'db',
                'allowed' => 'fe_users',
                'foreign_table' => 'fe_users',
                'MM' => 'tx_bwemail_domain_model_contactsource_fe_users_mm',
                'size' => 10,
                'maxitems' => 9999,
            ],
        ],
        'fe_user_groups' => [
            'exclude' => true,
            'label' => 'LLL:EXT:bw_email/Resources/Private/Language/locallang_db.xlf:tx_bwemail_domain_model_contactsource.fe_user_groups',
            'displayCond' => 'FIELD:fe_recipient_type:=:2',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'internal_type' => 'db',
                'allowed' => 'fe_groups',
                'foreign_table' => 'fe_groups',
                'MM' => 'tx_bwemail_domain_model_contactsource_fe_groups_mm',
                'size' => 10,
                'maxitems' => 9999,
            ],
        ],
        'fe_pid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:bw_email/Resources/Private/Language/locallang_db.xlf:tx_bwemail_domain_model_contactsource.fe_pid',
            'displayCond' => 'FIELD:fe_recipient_type:=:0',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'pages',
                'foreign_table_where' => 'ORDER BY pages.sorting',
                'size' => 20,
                'maxitems' => 1,
                'treeConfig' => [
                    'parentField' => 'pid',
                    'appearance' => [
                        'showHeader' => true,
                    ],
                ],
            ],
        ],
    ]
];

