<?php

use Blueways\BwEmail\Controller\Ajax\EmailWizardController;
return [
    'wizard_modal_page' => [
        'path' => '/email/modal/page',
        'target' => EmailWizardController::class . '::modalAction'
    ],
    'wizard_modal_send' => [
        'path' => '/email/modal/send',
        'target' => EmailWizardController::class . '::sendAction'
    ],
    'wizard_modal_preview' => [
        'path' => '/email/modal/preview',
        'target' => EmailWizardController::class . '::previewAction'
    ],
];
