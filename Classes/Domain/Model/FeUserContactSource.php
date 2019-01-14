<?php

namespace Blueways\BwEmail\Domain\Model;

class FeUserContactSource extends ContactSource
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
}
