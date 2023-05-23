<?php

namespace Blueways\BwEmail\Utility;

use Blueways\BwEmail\Domain\Model\Contact;
use Blueways\BwEmail\Domain\Model\Dto\WizardSettings;
use Blueways\BwEmail\View\EmailView;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Mail\MailMessage;

/**
 * Class SenderUtility
 *
 * @package Blueways\BwEmail\Utility
 */
class SenderUtility
{

    protected WizardSettings $settings;

    /**
     * @var Contact[]
     */
    protected $recipients;

    /**
     * @param Contact[] $recipients
     */
    public function setRecipients(array $recipients): void
    {
        $this->recipients = $recipients;
    }

    /**
     * @param EmailView $emailView
     * @return array
     * @TODO: use language service for translations
     */
    public function sendEmailView(EmailView $emailView)
    {
        //if (((int)$this->settings['provider']['use'] === 1 && !$this->recipients) || ((int)$this->settings['provider']['use'] === 0 && empty($this->settings['recipientAddress']))) {
        //    return [
        //        'status' => 'WARNING',
        //        'message' => [
        //            'headline' => 'No recipients',
        //            'text' => 'Please select an option with one or more recipients.'
        //        ]
        //    ];
        //}

        $mailsSend = 0;

        //if ((int)$this->settings['provider']['use'] === 1) {
        //    foreach ($this->recipients as $recipient) {
        //        $success = $this->sendEmailViewToContact($emailView, $recipient);
        //        if ($success) {
        //            $mailsSend = $mailsSend + $success;
        //        }
        //    }
        //}


        $contact = new Contact($this->settings->recipientAddress);
        $contact->setName($this->settings->recipientName);
        $success = $this->sendEmailViewToContact($emailView, $contact);
        if ($success) {
            $mailsSend = $mailsSend + $success;
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
     * @param EmailView $emailView
     * @param Contact $contact
     * @return int
     */
    protected function sendEmailViewToContact(EmailView $emailView, Contact $contact)
    {
        $emailView->insertContact($contact);
        $html = $emailView->render();

        return $this->sendMail(
            $this->getSenderArray(),
            $contact->getRecipientArray(),
            $this->settings->subject,
            $html,
            $this->settings->replytoAddress,
            $this->settings->bccAddress
        );
    }

    private function sendMail($from, $to, $subject, string $body, $replyTo, $bcc)
    {
        $mailMessage = GeneralUtility::makeInstance(MailMessage::class);
        $mailMessage->setTo($to)
            ->setFrom($from)
            ->setSubject($subject)
            ->html($body);

        if (!empty($replyTo)) {
            $mailMessage->setReplyTo($replyTo);
        }

        if (!empty($bcc)) {
            $mailMessage->setBcc($bcc);
        }

        return $mailMessage->send();
    }

    /**
     * Returns array in form array(senderMail => 'Sender Name')
     *
     * @return array
     */
    private function getSenderArray()
    {
        if ($this->settings->senderName) {
            return [$this->settings->senderAddress => $this->settings->senderName];
        }

        return [$this->settings->senderAddress];
    }

    public function setSettings(WizardSettings $settings)
    {
        $this->settings = $settings;
    }

    protected function validateSettings()
    {

    }

}
