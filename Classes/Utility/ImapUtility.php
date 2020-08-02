<?php

namespace Blueways\BwEmail\Utility;

use Ddeboer\Imap\Connection;
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
     * @var array
     */
    protected $extConf;

    /**
     * ImapUtility constructor.
     */
    public function __construct()
    {
        $this->setupServer();
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

        $stepCount = 10;
        $messages = [];
        $mailbox = $this->connection->getMailbox($this->extConf['inbox']);
        $messageIds = (array)$mailbox->getMessages();

        for ($i = $offset; $i < $stepCount; $i++) {
            $messages[] = $mailbox->getMessage($messageIds[$i]);
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

}
