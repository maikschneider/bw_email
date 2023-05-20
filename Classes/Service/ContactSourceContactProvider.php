<?php

namespace Blueways\BwEmail\Service;

use Blueways\BwEmail\Domain\Repository\ContactSourceRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Blueways\BwEmail\Domain\Model\ContactProviderOption;
use Blueways\BwEmail\Domain\Model\Contact;
/**
 * Class ContactSourceContactProvider
 *
 * @package Blueways\BwEmail\Service
 */
class ContactSourceContactProvider extends ContactProvider
{

    protected $name = 'LLL:EXT:bw_email/Resources/Private/Language/locallang.xlf:contactSourceProvider.name';

    protected $description = 'LLL:EXT:bw_email/Resources/Private/Language/locallang.xlf:contactSourceProvider.description';

    /**
     * @var ContactSourceRepository
     */
    protected $contactSourceRepository;

    /**
     * ContactSourceContactProvider constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $objectManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
        $this->contactSourceRepository = $objectManager->get('Blueways\\BwEmail\\Domain\\Repository\\ContactSourceRepository');
    }

    /**
     * @return mixed
     */
    protected function createOptions()
    {
        $this->options[] = new ContactProviderOption(
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
        // TODO: objectManager from constructor is empty, why?
        $objectManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
        $contactSourceRepository = $objectManager->get('Blueways\\BwEmail\\Domain\\Repository\\ContactSourceRepository');

        $sourceLabels = [];
        foreach ($contactSourceRepository->findAllDataSources() as $source) {
            $sourceLabels[] = $source->getName() . ' (' . sizeof($source->getContacts()) . ' contacts)';
        }
        return $sourceLabels;
    }

    /**
     * @return Contact[]
     */
    public function getContacts()
    {
        $objectManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
        $contactSourceRepository = $objectManager->get('Blueways\\BwEmail\\Domain\\Repository\\ContactSourceRepository');

        $sources = $contactSourceRepository->findAllDataSources();

        if (!sizeof($sources)) {
            return [];
        }

        $selectedSource = $this->options[0]->value;
        return $sources[$selectedSource]->getContacts();
    }
}
