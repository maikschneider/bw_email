<?php

namespace Blueways\BwEmail\Domain\Model;

class MailLog extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * @var int
     */
    protected $status;

    /**
     * @var \DateTime
     */
    protected $sendDate;

    /**
     * @var string
     */
    protected $recipientAddress;

    /**
     * @var string
     */
    protected $recipientName;

    /**
     * @var string
     */
    protected $subject;

    /**
     * @var string
     */
    protected $body;

    /**
     * @var string
     */
    protected $senderAddress;

    /**
     * @var string
     */
    protected $senderName;

    /**
     * @var string
     */
    protected $jobType;

    /**
     * @return string
     */
    public function getJobType(): string
    {
        return $this->jobType;
    }

    /**
     * @param string $jobType
     */
    public function setJobType(string $jobType): void
    {
        $this->jobType = $jobType;
    }

    /**
     * @return string
     */
    public function getConversationId(): string
    {
        return $this->conversationId;
    }

    /**
     * @param string $conversationId
     */
    public function setConversationId(string $conversationId): void
    {
        $this->conversationId = $conversationId;
    }

    /**
     * @var string
     */
    protected $conversationId;

    /**
     * @var string
     */
    protected $recordTable;

    /**
     * @return string
     */
    public function getRecordTable(): string
    {
        return $this->recordTable;
    }

    /**
     * @param string $recordTable
     */
    public function setRecordTable(string $recordTable): void
    {
        $this->recordTable = $recordTable;
    }

    /**
     * @return int
     */
    public function getRecordUid(): int
    {
        return $this->recordUid;
    }

    /**
     * @param int $recordUid
     */
    public function setRecordUid(int $recordUid): void
    {
        $this->recordUid = $recordUid;
    }

    /**
     * @var int
     */
    protected $recordUid;

    /**
     * @return string
     */
    public function getSenderAddress(): string
    {
        return $this->senderAddress;
    }

    /**
     * @param string $senderAddress
     */
    public function setSenderAddress(string $senderAddress): void
    {
        $this->senderAddress = $senderAddress;
    }

    /**
     * @return string
     */
    public function getSenderName(): string
    {
        return $this->senderName;
    }

    /**
     * @param string $senderName
     */
    public function setSenderName(string $senderName): void
    {
        $this->senderName = $senderName;
    }

    /**
     * @return string
     */
    public function getSenderReplyto(): string
    {
        return $this->senderReplyto;
    }

    /**
     * @param string $senderReplyto
     */
    public function setSenderReplyto(string $senderReplyto): void
    {
        $this->senderReplyto = $senderReplyto;
    }

    /**
     * @var string
     */
    protected $senderReplyto;

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * @return \DateTime
     */
    public function getSendDate(): \DateTime
    {
        return $this->sendDate;
    }

    /**
     * @param \DateTime $sendDate
     */
    public function setSendDate(\DateTime $sendDate): void
    {
        $this->sendDate = $sendDate;
    }

    /**
     * @return string
     */
    public function getRecipientAddress(): string
    {
        return $this->recipientAddress;
    }

    /**
     * @param string $recipientAddress
     */
    public function setRecipientAddress(string $recipientAddress): void
    {
        $this->recipientAddress = $recipientAddress;
    }

    /**
     * @return string
     */
    public function getRecipientName(): string
    {
        return $this->recipientName;
    }

    /**
     * @param string $recipientName
     */
    public function setRecipientName(string $recipientName): void
    {
        $this->recipientName = $recipientName;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @param string $body
     */
    public function setBody(string $body): void
    {
        $this->body = $body;
    }
}
