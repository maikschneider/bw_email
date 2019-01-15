<?php

namespace Blueways\BwEmail\Domain\Model;

class ContactSource extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * name of the data source
     *
     * @var string
     */
    protected $name;

    /**
     * @return \Blueways\BwEmail\Domain\Model\Contact[]
     */
    public function getContacts()
    {
        return [];
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
