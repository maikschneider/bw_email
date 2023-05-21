<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Bw Email',
    'description' => 'Send responsive emails',
    'category' => 'templates',
    'constraints' => [
        'depends' => [
            'typo3' => '11.0.0-11.99.99',
        ],
        'conflicts' => [
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'Blueways\\BwEmail\\' => 'Classes',
        ],
    ],
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'author' => 'Maik Schneider',
    'author_email' => 'maik.schneider@xima.de',
    'author_company' => 'XIMA Media',
    'version' => '3.0.0',
];
