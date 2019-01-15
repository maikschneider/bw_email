<?php

namespace Blueways\BwEmail\Domain\Model;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class FeUserContactSource extends \Blueways\BwEmail\Domain\Model\ContactSource
{

    const RECIPIENT_TYPE_FOLDER = 0;
    const RECIPIENT_TYPE_USERS = 1;
    const RECIPIENT_TYPE_GROUPS = 2;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FrontendUser>
     */
    protected $feUsers;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup>
     */
    protected $feUserGroups;

    /**
     * PID of the storage folder
     *
     * @var integer
     */
    protected $fePid;

    /**
     * @var integer
     */
    protected $feRecipientType = self::RECIPIENT_TYPE_FOLDER;

    /**
     * @return \Blueways\BwEmail\Domain\Model\Contact[]
     */
    public function getContacts()
    {
        $contacts = [];
        $objectManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
        $query = $objectManager->get('TYPO3\\CMS\\Extbase\\Domain\\Repository\\FrontendUserRepository');
        $query->createQuery()->getQuerySettings()->setRespectStoragePage(false);
        $users = $query->findAll();
        return $users;
    }

    /*
    public function getAllFeUsers()
    {
        switch ($this->feRecipientType):
            case self::RECIPIENT_TYPE_USERS:
                return $this->feUsers;
                break;

            case self::RECIPIENT_TYPE_GROUPS:
                return $this->feUserGroups

        endswitch;

        if($this->feRecipientType)
    }
    */
}
