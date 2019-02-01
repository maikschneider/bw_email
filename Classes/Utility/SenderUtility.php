<?php

namespace Blueways\BwEmail\Utility;

use Blueways\BwEmail\Domain\Model\Contact;
use Blueways\BwEmail\View\EmailView;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Class SenderUtility
 *
 * @package Blueways\BwEmail\Utility
 */
class SenderUtility
{

    /**
     * @var array
     */
    protected $mailSettings;

    /**
     * SenderUtility constructor.
     *
     * @param array $typoscript
     */
    protected $typoscript;

    /**
     * @var \Blueways\BwEmail\Domain\Model\Contact[]
     */
    protected $recipients;

    public function __construct($typoscript)
    {
        $this->typoscript = $typoscript;

        $this->mailSettings = array(
            'senderAddress' => $this->typoscript['plugin.']['tx_bwemail_pi1.']['settings.']['senderAddress'],
            'senderName' => $this->typoscript['plugin.']['tx_bwemail_pi1.']['settings.']['senderName'],
            'replytoAddress' => $this->typoscript['plugin.']['tx_bwemail_pi1.']['settings.']['replytoAddress'],
            'subject' => $this->typoscript['plugin.']['tx_bwemail_pi1.']['settings.']['subject'],
            'emailTemplate' => $this->typoscript['plugin.']['tx_bwemail_pi1.']['settings.']['template'],
            'showUid' => $this->typoscript['plugin.']['tx_bwemail_pi1.']['settings.']['showUid'] ?? null,
            'recipientAddress' => '',
            'recipientName' => '',
            'provider' => [
                'use' => '0'
            ]
        );
    }

    /**
     * @return array
     */
    public function getMailSettings(): array
    {
        return $this->mailSettings;
    }

    /**
     * @param \Blueways\BwEmail\Domain\Model\Contact[] $recipients
     */
    public function setRecipients(array $recipients): void
    {
        $this->recipients = $recipients;
    }

    /**
     * @param $settings
     */
    public function mergeMailSettings($settings)
    {
        ArrayUtility::mergeRecursiveWithOverrule($this->mailSettings, $settings, false, false);
    }

    /**
     * @param \Blueways\BwEmail\View\EmailView $emailView
     * @return array
     */
    public function sendEmailView(EmailView $emailView)
    {
        if (((int)$this->mailSettings['provider']['use'] === 1 && !$this->recipients) || ((int)$this->mailSettings['provider']['use'] === 0 && empty($this->mailSettings['recipientAddress']))) {
            return [
                'status' => 'WARNING',
                'message' => [
                    'headline' => 'No recipients',
                    'text' => 'Please select an option with one or more recipients.'
                ]
            ];
        }

        // @TODO: create log
        $mailsSend = 0;

        if ((int)$this->mailSettings['provider']['use'] === 1) {
            foreach ($this->recipients as $recipient) {
                $success = $this->sendEmailViewToContact($emailView, $recipient);
                if ($success) {
                    $mailsSend = $mailsSend + 1;
                }
            }
        }

        if ((int)$this->mailSettings['provider']['use'] === 0) {
            $contact = new Contact($this->mailSettings['recipientAddress']);
            $contact->setName($this->mailSettings['recipientName']);
            $success = $this->sendEmailViewToContact($emailView, $contact);
            if ($success) {
                $mailsSend = $mailsSend + 1;
            }
        }

        if ($mailsSend) {
            return [
                'status' => 'OK',
                'message' => [
                    'headline' => 'Success',
                    'text' => $mailsSend === 1 ? 'Mail successfully send.' : $mailsSend . ' mails have been successfully send.'
                ]
            ];
        }

        return [
            'status' => 'ERROR',
            'message' => [
                'headline' => 'Unknown error',
                'text' => 'No mails have been send.'
            ]
        ];
    }

    /**
     * @param \Blueways\BwEmail\View\EmailView $emailView
     * @param \Blueways\BwEmail\Domain\Model\Contact $contact
     * @return boolean
     */
    public function sendEmailViewToContact(EmailView $emailView, Contact $contact)
    {
        return true;
    }

}
