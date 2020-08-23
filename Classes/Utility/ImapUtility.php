<?php

namespace Blueways\BwEmail\Utility;

use Ddeboer\Imap\Connection;
use Ddeboer\Imap\MessageInterface;
use Ddeboer\Imap\Server;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ImapUtility
 *
 * @package Blueways\BwEmail\Utility
 */
class ImapUtility
{

    /**
     * @var \Ddeboer\Imap\Connection
     */
    protected $connection;

    /**
     * @var
     */
    protected $cache;

    /**
     * @var array
     */
    protected $extConf;

    /**
     * ImapUtility constructor.
     */
    public function __construct()
    {
        $this->setupServer();

        $this->cache = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache('bwemail_mail');
    }

    private function setupServer()
    {
        $this->extConf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('bw_email');

        $credentials = [];
        $credentials['port'] = false;
        $credentials['hostname'] = $this->extConf['server'] ?? false;
        $credentials['username'] = $this->extConf['username'] ?? false;
        $credentials['password'] = $this->extConf['password'] ?? false;

        $credentials = array_filter($credentials);

        if (count($credentials) !== 3) {
            return false;
        }

        $server = GeneralUtility::trimExplode(':', $credentials['hostname']);
        $credentials['hostname'] = $server[0];
        $credentials['port'] = $server[1];

        $server = new Server(
            $credentials['hostname'],
            $credentials['port']
        );

        try {
            $this->connection = $server->authenticate($credentials['username'], $credentials['password']);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getMailboxMessages($mailboxName, $messagesToIgnore)
    {
        if (!$mailboxName || !$this->hasConnection()) {
            return [];
        }

        $messages = [];
        $cacheIdentifier = 'folder-' . $mailboxName;
        $mailbox = $this->connection->getMailbox($mailboxName);

        if (($messageIds = $this->cache->get($cacheIdentifier)) === false) {
            $messageIds = (array)$mailbox->getMessages();
            $messageIds = array_reverse($messageIds);
            $this->cache->set($cacheIdentifier, $messageIds, [], 2700);
        }

        $pos = 0;

        while (count($messages) !== 10 || $pos === count($messageIds) - 1) {

            $messageId = $messageIds[$pos];
            $pos++;

            if (in_array($messageId, $messagesToIgnore, true)) {
                continue;
            }

            $messages[] = $this->loadMail($mailboxName, $messageId, false);
        }

        return $messages;
    }

    /**
     * @return bool
     */
    public function hasConnection(): bool
    {
        return $this->connection instanceof Connection;
    }

    /**
     * @param string $mailboxName
     * @param int $messageNumber
     * @param bool $markAsSeen
     * @return array|mixed
     */
    public function loadMail(string $mailboxName, int $messageNumber, $markAsSeen = false)
    {

        $cacheIdentifier = 'mail-' . (string)$messageNumber;
        $cacheTags = [];

        if (($message = $this->cache->get($cacheIdentifier)) && ($message['bodyHtml'] !== '' || $message['bodyText'] !== '')) {
            return $message;
        }

        $mailbox = $this->connection->getMailbox($mailboxName);
        $imapMail = $mailbox->getMessage($messageNumber);

        if ($markAsSeen) {
            $imapMail->markAsSeen();
        }

        $message = self::serializeImapMail($imapMail, $mailboxName);

        $attachments = $imapMail->getAttachments();
        foreach ($attachments as $attachment) {
            $isEmbeddedMessage = $attachment->isEmbeddedMessage();
        }

        $this->cache->set($cacheIdentifier, $message, $cacheTags, 2592000);

        return $message;
    }

    public static function serializeImapMail(MessageInterface $imapMail, string $mailboxName)
    {
        $mail = [];
        $mail['date'] = $imapMail->getDate() ? $imapMail->getDate()->getTimestamp() : '';
        $mail['from'] = [];
        $mail['from']['name'] = $imapMail->getFrom() ? $imapMail->getFrom()->getName() : '';
        $mail['from']['address'] = $imapMail->getFrom() ? $imapMail->getFrom()->getAddress() : '';
        $mail['subject'] = $imapMail->getSubject();
        $mail['bodyText'] = $imapMail->getBodyText();
        $mail['isSeen'] = $imapMail->isSeen();
        $mail['number'] = $imapMail->getNumber();
        $mail['mailbox'] = $mailboxName;
        $mail['bodyHtml'] = $imapMail->getBodyHtml();
        $mail['to'] = [];
        foreach ($imapMail->getTo() as $to) {
            $mail['to'][] = [
                'name' => $to->getName(),
                'address' => $to->getAddress()
            ];
        }
        return $mail;
    }

}
