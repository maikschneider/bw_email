<?php

use Blueways\BwEmail\Controller\Ajax\EmailWizardController;
return [
    'wizard_email_modal' => [
        'path' => '/email/wizard',
        'target' => EmailWizardController::class . '::modalAction'
    ],
    'email_send' => [
        'path' => '/email/send',
        'target' => EmailWizardController::class . '::sendAction'
    ],
    'email_preview' => [
        'path' => '/email/preview',
        'target' => EmailWizardController::class . '::previewAction'
    ],
];
