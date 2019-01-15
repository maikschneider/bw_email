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

        foreach ($this->getSelectedFeUsers() as $feUser) {
            // abort if user has no email
            if (!$feUser->getEmail()) {
                continue;
            }

            $contact = new Contact($feUser->getEmail());
            $contact->setName($feUser->getName());
            $contact->setPrename($feUser->getFirstName());
            $contact->setLastname($feUser->getLastName());

            $contacts[] = $contact;
        }

        return $contacts;
    }

    public function getSelectedFeUsers()
    {

        if ($this->feRecipientType === self::RECIPIENT_TYPE_USERS) {
            return $this->feUsers;
        }

        if ($this->feRecipientType === self::RECIPIENT_TYPE_GROUPS) {
            // query fe_user repo for users that are in any of these groups
            $objectManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
            $feRepo = $objectManager->get('TYPO3\\CMS\\Extbase\\Domain\\Repository\\FrontendUserRepository');
            $query = $feRepo->createQuery();
            $query->getQuerySettings()->setRespectStoragePage(false);

            foreach ($this->feUserGroups as $group) {
                $constraint[] = $query->equals('usergroup', $group);
            }

            $users = $query->matching($query->logicalOr($constraint))->execute()->toArray();

            return $users;
        }

        if ($this->feRecipientType === self::RECIPIENT_TYPE_FOLDER) {
            $objectManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
            $feRepo = $objectManager->get('TYPO3\\CMS\\Extbase\\Domain\\Repository\\FrontendUserRepository');
            $query = $feRepo->createQuery();
            $query->getQuerySettings()->setRespectStoragePage(false);
            $users = $query->matching($query->equals('pid', $this->fePid))->execute()->toArray();

            return $users;
        }

        return [];
    }

}
