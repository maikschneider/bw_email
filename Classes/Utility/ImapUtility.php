<?php

namespace Blueways\BwEmail\Utility;

use Ddeboer\Imap\Connection;
use Ddeboer\Imap\MailboxInterface;
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

    public function getInboxMessages($offset = 0)
    {
        if (!$this->hasConnection()) {
            return [];
        }

        $messages = [];
        $cacheIdentifier = 'folder-' . $this->extConf['inbox'];

        if (($messageIds = $this->cache->get($cacheIdentifier)) === false) {
            $mailbox = $this->connection->getMailbox($this->extConf['inbox']);
            $messageIds = (array)$mailbox->getMessages();
            $this->cache->set($cacheIdentifier, $messageIds, [], 2700);
        }

        $step = 10;

        for ($i = count($messageIds) - 1; $i >= count($messageIds) - $step; $i--) {

            $mailbox = $mailbox ?? $this->connection->getMailbox($this->extConf['inbox']);
            $messages[] = $this->loadMailPreview($mailbox, $messageIds[$i]);
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

    private function loadMailPreview(MailboxInterface $mailbox, int $uid)
    {
        $cacheIdentifier = 'mail-' . (string)$uid;
        $cacheTags = [];

        if (($mail = $this->cache->get($cacheIdentifier)) === false) {
            $imapMail = $mailbox->getMessage($uid);
            $mail = self::serializeImapMail($imapMail, $mailbox->getName());
            $this->cache->set($cacheIdentifier, $mail, $cacheTags, 2592000);
        }

        return $mail;
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
        $mail['bodyHtml'] = '';
        $mail['to'] = [];
        foreach ($imapMail->getTo() as $to) {
            $mail['to'][] = [
                'name' => $to->getName(),
                'address' => $to->getAddress()
            ];
        }
        return $mail;
    }

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
        $message['bodyHtml'] = $imapMail->getBodyHtml();
        $this->cache->set($cacheIdentifier, $message, $cacheTags, 2592000);

        return $message;
    }

}
