<?php

namespace Blueways\BwEmail\Domain\Repository;

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class ContactSourceRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findAllDataSources()
    {
        $query = $this->createQuery();

        $query->getQuerySettings()->setRespectStoragePage(false);

        $sources = $query->execute();

        /**
         * @TODO: remove debugging
         */
        $this->debugQuery($sources);

        return $sources;
    }

    public function getFeUsersByFeGroups($feGroups)
    {

    }

    public function debugQuery(\TYPO3\CMS\Extbase\Persistence\Generic\QueryResult $queryResult, $explainOutput = false)
    {
        $GLOBALS['TYPO3_DB']->debugOutput = 2;
        if ($explainOutput) {
            $GLOBALS['TYPO3_DB']->explainOutput = true;
        }
        $GLOBALS['TYPO3_DB']->store_lastBuiltQuery = true;
        $queryResult->toArray();
        //DebuggerUtility::var_dump($GLOBALS['TYPO3_DB']->debug_lastBuiltQuery);

        $GLOBALS['TYPO3_DB']->store_lastBuiltQuery = false;
        $GLOBALS['TYPO3_DB']->explainOutput = false;
        $GLOBALS['TYPO3_DB']->debugOutput = false;
    }
}
