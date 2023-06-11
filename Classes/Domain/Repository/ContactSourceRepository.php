<?php

namespace Blueways\BwEmail\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class ContactSourceRepository
 */
class ContactSourceRepository extends Repository
{
    /**
     * @return array
     */
    public function findAllDataSources()
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        return $query->execute()->toArray();
    }
}
