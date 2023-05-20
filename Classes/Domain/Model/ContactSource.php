<?php

namespace Blueways\BwEmail\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
class ContactSource extends AbstractEntity
{

    /**
     * name of the data source
     *
     * @var string
     */
    protected $name;

    /**
     * @return Contact[]
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
