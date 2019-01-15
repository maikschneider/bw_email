<?php

namespace Blueways\BwEmail\Service;

class ContactSourceContactProvider extends ContactProvider
{

    protected $name = 'LLL:EXT:bw_email/Resources/Private/Language/locallang.xlf:contactSourceProvider.name';

    protected $description = 'LLL:EXT:bw_email/Resources/Private/Language/locallang.xlf:contactSourceProvider.description';

    /**
     * @var \Blueways\BwEmail\Domain\Repository\ContactSourceRepository
     */
    protected $contactSourceRepository;

    /**
     * ContactSourceContactProvider constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
        $this->contactSourceRepository = $objectManager->get('Blueways\\BwEmail\\Domain\\Repository\\ContactSourceRepository');
    }

    /**
     * @return mixed
     */
    protected function createOptions()
    {
        $this->options[] = new \Blueways\BwEmail\Domain\Model\ContactProviderOption(
            'Contact source',
            'source',
            'select',
            $this->getSourceOptions()
        );
    }

    /**
     * return array
     */
    private function getSourceOptions()
    {
        /** @var @TODO: objectManager from constructor is empty, why? */
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
        $contactSourceRepository = $objectManager->get('Blueways\\BwEmail\\Domain\\Repository\\ContactSourceRepository');

        $sourceLabels = [];
        foreach ($contactSourceRepository->findAllDataSources() as $source) {
            $sourceLabels[] = $source->getName() . ' (' . sizeof($source->getContacts()) . ' contacts)';
        }
        return $sourceLabels;
    }

    /**
     * @param array $optionSelections
     * @return \Blueways\BwEmail\Domain\Model\Contact[]
     */
    public function getContacts(array $optionSelections)
    {
        // TODO: Implement getContracts() method.
        return [];
    }
}
