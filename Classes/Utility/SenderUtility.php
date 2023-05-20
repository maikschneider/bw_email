<?php

namespace Blueways\BwEmail\Utility;

use Blueways\BwEmail\Domain\Model\Contact;
use Blueways\BwEmail\View\EmailView;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
    protected $settings;

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
     * @param $settings
     */
    public function mergeMailSettings($settings)
    {
        ArrayUtility::mergeRecursiveWithOverrule($this->settings, $settings, true, false);
    }

    /**
     * @param EmailView $emailView
     * @return array
     * @TODO: use language service for translations
     */
    public function sendEmailView(EmailView $emailView)
    {
        if (((int)$this->settings['provider']['use'] === 1 && !$this->recipients) || ((int)$this->settings['provider']['use'] === 0 && empty($this->settings['recipientAddress']))) {
            return [
                'status' => 'WARNING',
                'message' => [
                    'headline' => 'No recipients',
                    'text' => 'Please select an option with one or more recipients.'
                ]
            ];
        }

        // @TODO: create persistence log
        $mailsSend = 0;

        if ((int)$this->settings['provider']['use'] === 1) {
            foreach ($this->recipients as $recipient) {
                $success = $this->sendEmailViewToContact($emailView, $recipient);
                if ($success) {
                    $mailsSend = $mailsSend + $success;
                }
            }
        }

        if ((int)$this->settings['provider']['use'] === 0) {
            $contact = new Contact($this->settings['recipientAddress']);
            $contact->setName($this->settings['recipientName']);
            $success = $this->sendEmailViewToContact($emailView, $contact);
            if ($success) {
                $mailsSend = $mailsSend + $success;
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
            $this->settings['subject'],
            $html,
            $this->settings['replytoAddress'],
            $this->settings['bccAddress']
        );
    }

    /**
     * @param $from
     * @param $to
     * @param $subject
     * @param $body
     * @param $replyTo
     * @return int
     */
    private function sendMail($from, $to, $subject, $body, $replyTo, $bcc)
    {
        $mailMessage = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Mail\\MailMessage');
        $mailMessage->setTo($to)
            ->setFrom($from)
            ->setSubject($subject)
            ->setBody($body, 'text/html');

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
        if ($this->settings['senderName']) {
            return [$this->settings['senderAddress'] => $this->settings['senderName']];
        }

        return [$this->settings['senderAddress']];
    }

    /**
     * @param $settings
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;
    }

    protected function validateSettings()
    {

    }

}
