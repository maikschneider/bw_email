<?php
return [
    'wizard_modal_page' => [
        'path' => '/email/modal/page',
        'target' => \Blueways\BwEmail\Controller\Ajax\EmailWizardController::class . '::modalAction'
    ],
    'wizard_modal_send' => [
        'path' => '/email/modal/send',
        'target' => \Blueways\BwEmail\Controller\Ajax\EmailWizardController::class . '::sendAction'
    ],
];
