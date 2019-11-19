<?php

namespace Blueways\BwEmail\Domain\Model\MailLog;

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
