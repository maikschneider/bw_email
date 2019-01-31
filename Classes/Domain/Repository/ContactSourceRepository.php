<?php

namespace Blueways\BwEmail\Domain\Repository;

/**
 * Class ContactSourceRepository
 *
 * @package Blueways\BwEmail\Domain\Repository
 */
class ContactSourceRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
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
