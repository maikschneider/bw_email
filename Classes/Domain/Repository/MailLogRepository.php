<?php

namespace Blueways\BwEmail\Domain\Repository;

/**
 * Class MailLogRepository
 *
 * @package Blueways\BwEmail\Domain\Repository
 */
class MailLogRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    public function countByStatus(int $status)
    {
        $query = $this->createQuery();
        $query->matching(
            $query->equals('status', $status)
        );

        return $query->count();
    }

    public function findByStatus(int $status)
    {
        $query = $this->createQuery();
        $query->matching(
            $query->equals('status', $status)
        );
        $query->setOrderings([
            'sendDate' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
        ]);

        return $query->execute();
    }
}
