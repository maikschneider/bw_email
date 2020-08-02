<?php

use Blueways\BwEmail\Controller\AdministrationController;
use Blueways\BwEmail\Controller\Ajax\ImapController;

return [
    'wizard_modal_page' => [
        'path' => '/email/modal/page',
        'target' => \Blueways\BwEmail\Controller\Ajax\EmailWizardController::class . '::modalAction'
    ],
    'wizard_modal_resend' => [
        'path' => '/email/modal/resend',
        'target' => \Blueways\BwEmail\Controller\Ajax\EmailWizardController::class . '::modalResendAction'
    ],
    'wizard_modal_send' => [
        'path' => '/email/modal/send',
        'target' => \Blueways\BwEmail\Controller\Ajax\EmailWizardController::class . '::sendAction'
    ],
    'wizard_modal_preview' => [
        'path' => '/email/modal/preview',
        'target' => \Blueways\BwEmail\Controller\Ajax\EmailWizardController::class . '::previewAction'
    ],
    'email_preview' => [
        'path' => '/email/preview',
        'target' => AdministrationController::class . '::previewAction'
    ],
    'email_resend' => [
        'path' => '/email/resend',
        'target' => AdministrationController::class . '::resendAction'
    ],
    'email_inbox' => [
        'path' => '/email/inbox',
        'target' => ImapController::class . '::inboxAction'
    ]
];
