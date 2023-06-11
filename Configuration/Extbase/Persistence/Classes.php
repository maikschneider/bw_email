<?php

return [
    \Blueways\BwEmail\Domain\Model\ContactSource::class => [
        'subclasses' => ['Blueways\BwEmail\Domain\Model\FeUserContactSource' => 'Blueways\BwEmail\Domain\Model\FeUserContactSource'],
    ],
    \Blueways\BwEmail\Domain\Model\FeUserContactSource::class => [
        'recordType' => 'Blueways\BwEmail\Domain\Model\FeUserContactSource',
        'tableName' => 'tx_bwemail_domain_model_contactsource',
    ],
];
