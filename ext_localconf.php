<?php

defined('TYPO3') || die('Access denied');

// register Xclasses
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Form\\Domain\\Finishers\\EmailFinisher'] = [
    'className' => 'Blueways\\BwEmail\\Domain\\Finishers\\EmailFinisher',
];

// register custom TCA node field
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1533721568] = [
    'nodeName' => 'sendMailButton',
    'priority' => '70',
    'class' => \Blueways\BwEmail\Form\Element\SendMailButtonElement::class,
];
