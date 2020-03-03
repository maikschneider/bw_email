<?php

namespace Blueways\BwEmail\Utility;

use Blueways\BwEmail\Domain\Model\Contact;
use Blueways\BwEmail\Domain\Model\Dto\EmailSettings;
use Blueways\BwEmail\Domain\Model\MailLog;
use Blueways\BwEmail\Domain\Repository\MailLogRepository;
use Blueways\BwEmail\View\EmailView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Core\Mail\MailMessage;

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
     * @var object|\TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Blueways\BwEmail\Domain\Model\Dto\EmailSettings
     */
    protected $emailSettings;

    /**
     * @var \Blueways\BwEmail\Domain\Model\Contact[]
     */
    protected $recipients;

    /**
     * @var \Blueways\BwEmail\Domain\Repository\MailLogRepository
     */
    protected $mailLogRepository;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     */
    protected $persistenceManager;

    /**
     * @var \Blueways\BwEmail\View\EmailView|object
     */
    public $emailView;

    /**
     * SenderUtility constructor.
     *
     * @param null $emailSettings
     * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
     */
    public function __construct($emailSettings = null)
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->mailLogRepository = $this->objectManager->get(MailLogRepository::class);
        $this->persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $this->emailSettings = $emailSettings ?? $this->objectManager->get(EmailSettings::class);
        $this->emailView = $this->objectManager->get(EmailView::class);
        $this->emailView->setPid($this->emailSettings->pid);
        $this->emailView->setTemplate($this->emailSettings->template);

        if ($this->emailSettings->table && $this->emailSettings->uid && $this->emailSettings->pid) {
            $this->injectRecord();
        }

        // inject records from typoscript (or tca override
        if (is_array($this->emailSettings->typoscriptSelects)) {
            $this->injectTypoScriptSelects();
        }
    }

    /**
     * @param \Blueways\BwEmail\Domain\Model\Contact[] $recipients
     */
    public function setRecipients(array $recipients): void
    {
        $this->emailSettings->contacts = $recipients;
    }

    /**
     * @param $settings
     */
    public function mergeMailSettings($settings): void
    {
        ArrayUtility::mergeRecursiveWithOverrule($this->settings, $settings, true, false);
    }

    /**
     * @return int
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function send(): int
    {
        $mailsSend = 0;

        if (!$this->hasValidSettings()) {
            return $mailsSend;
        }

        $contacts = $this->emailSettings->getContacts();

        foreach ($contacts as $contact) {
            $mailsSend += $this->sendEmailToContact($contact);
        }

        return $mailsSend;
    }

    public function hasValidSettings(): bool
    {
        return true;
    }

    /**
     * @param \Blueways\BwEmail\View\EmailView $emailView
     * @return array
     * @deprecated
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

        $mailsSend = 0;

        if ((int)$this->settings['provider']['use'] === 1) {
            foreach ($this->recipients as $recipient) {
                $success = $this->sendEmailViewToContact($emailView, $recipient);
                if ($success) {
                    $mailsSend += $success;
                }
            }
        }

        if ((int)$this->settings['provider']['use'] === 0) {
            $contact = new Contact($this->settings['recipientAddress']);
            $contact->setName($this->settings['recipientName']);
            $success = $this->sendEmailViewToContact($emailView, $contact);
            if ($success) {
                $mailsSend += $success;
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
     * @param \Blueways\BwEmail\Domain\Model\Contact $contact
     * @return int
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    protected function sendEmailToContact(Contact $contact): int
    {
        $this->emailView->insertContact($contact);
        $html = $this->emailView->render();

        return $this->sendMail(
            $contact->getRecipientArray(),
            $html
        );
    }

    /**
     * Send html mail to recipient, mail gets logged
     *
     * @param array $from
     * @param $to
     * @param $subject
     * @param $body
     * @param $replyTo
     * @return int
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    private function sendMail($to, $body): int
    {
        $from = [$this->emailSettings->senderAddress];

        if ($this->emailSettings->senderName) {
            $from = [$this->emailSettings->senderAddress => $this->emailSettings->senderName];
        }

        $subject = $this->emailSettings->subject;
        $replyTo = $this->emailSettings->replytoAddress;

        $log = new MailLog();
        $log->setSenderAddress(\array_keys($from)[0]);
        if (isset($from[\array_keys($from)[0]])) {
            $log->setSenderName($from[\array_keys($from)[0]]);
        }
        $log->setRecipientAddress(\array_keys($to)[0]);
        if (isset($to[\array_keys($to)[0]])) {
            $log->setRecipientName($to[\array_keys($to)[0]]);
        }
        $log->setSubject($subject);
        $log->setBody($body);
        if ($replyTo) {
            $log->setSenderReplyto($replyTo);
        }
        $log->setSendDate(new \DateTime());
        $log->setJobType($this->emailSettings->jobType);
        $log->setRecordTable($this->emailSettings->table ?? '');
        $log->setRecordUid($this->emailSettings->uid ?? 0);

        /** @var \TYPO3\CMS\Core\Mail\MailMessage $mailMessage */
        $mailMessage = GeneralUtility::makeInstance(MailMessage::class);
        $mailMessage->setTo($to)
            ->setFrom($from)
            ->setSubject($subject)
            ->setBody($body, 'text/html');

        if (!empty($replyTo)) {
            $mailMessage->setReplyTo($replyTo);
        }

        $status = $mailMessage->send();

        // @TODO: check for rejected

        if ($status === 1) {
            $log->setStatus(1);
        }
        $this->mailLogRepository->add($log);
        $this->persistenceManager->persistAll();

        return $status;
    }

    /**
     * @param \Blueways\BwEmail\Domain\Model\MailLog $log
     * @return int
     */
    public function sendEmailFromLog(MailLog $log): int
    {
        $html = $log->getBody();
        $contact = new Contact($this->settings['recipientAddress']);
        $contact->setName($this->settings['recipientName']);

        $this->sendEmailToContact($contact);
    }

    /**
     * @param $settings
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;
    }

    private function injectRecord()
    {
        // inject current record
        $record = BackendUtility::getRecord(
            $this->emailSettings->table,
            $this->emailSettings->uid
        );
        // the record is just an array, we need to query the repository to access all properties with fluid
        if (isset($record['record_type'])) {
            $recordTypeParts = explode("\\", $record['record_type']);
            $recordTypeParts[3] = 'Repository';
            $recordTypeParts[4] .= 'Repository';
            $repository = $this->objectManager->get(implode('\\', $recordTypeParts));
            $record = $repository->findByUid($this->emailSettings->uid);
        }
        $this->emailView->assign('record', $record);
    }

    private function injectTypoScriptSelects()
    {
        foreach ($this->emailSettings->typoscriptSelects as $markerName => $typoscript) {
            $this->emailView->injectTyposcriptSelect($markerName, $typoscript);
        }
    }
}
