<?php

namespace Blueways\BwEmail\Domain\Model\Dto;

use Blueways\BwEmail\Domain\Model\Contact;
use Blueways\BwEmail\Service\ContactProvider;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

class WizardSettings
{

    public string $senderAddress = '';

    public string $senderName = '';

    public string $replytoAddress = '';

    public string $subject = '';

    public string $template = '';

    public int $showUid;

    /** @var ContactProvider[] */
    public array $contactProviders = [];

    public bool $useContactProvider = false;

    public ContactProvider $contactProvider;

    /** @var Contact[] */
    public array $contacts = [];

    public int $uid;

    public string $tableName = '';

    public array $typoscriptSelects;

    public string $recipientAddress = '';

    public string $bccAddress = '';

    public string $recipientName = '';

    public string $jobType = '';

    public array $markerOverrides = [];

    public $selectedContact;

    /** @var array<string, string> */
    public array $templates = [];

    public function __construct(string $tableName, int $uid, array $typoScriptSettings)
    {
        $this->tableName = $tableName;
        $this->uid = $uid;

        $this->senderAddress = $typoScriptSettings['senderAddress'];
        $this->senderName = $typoScriptSettings['senderName'];
        $this->replytoAddress = $typoScriptSettings['replytoAddress'];
        $this->bccAddress = $typoScriptSettings['bccAddress'];
        $this->subject = $typoScriptSettings['subject'];
        $this->template = $typoScriptSettings['template'];
        $this->templates = $typoScriptSettings['templates'];
        $this->typoscriptSelects = $typoScriptSettings['typoscriptSelects'];
        $this->showUid = (int)$typoScriptSettings['showUid'];
        $this->contactProviders = [];
        $this->useContactProvider = false;
        $this->jobType = 'UNKNOWN';
        $this->markerOverrides = [];
        foreach ($typoScriptSettings['provider'] as $className => $options) {
            /** @var ContactProvider $provider */
            $provider = GeneralUtility::makeInstance($className);
            $provider->applySettings($options);
            $this->contactProviders[] = $provider;
        }

        // table overrides
        if ($this->tableName && is_array($typoScriptSettings['tableOverrides'][$this->tableName])) {
            $this->override($typoScriptSettings['tableOverrides'][$this->tableName]);
        }

        $this->populateConfig();
    }

    public static function createFromPostData(array $data, array $typoScriptSettings)
    {
        $settings = new self($data['tableName'], $data['uid'], $typoScriptSettings);
        $settings->override($data);

        return $settings;
    }

    public function override($settings)
    {
        foreach ($settings as $settingName => $settingValue) {
            if (property_exists(self::class, $settingName) && $settingValue) {
                $this->$settingName = $settingValue;
            }

            if ($settingName === 'markerOverrides' && count($settingValue)) {
                $this->markerOverrides = $settingValue;
            }

            if ($settingName === 'provider' && isset($settingValue['use'], $settingValue['id'])) {
                $provider = GeneralUtility::makeInstance($settingValue['id']);
                $provider->applyConfiguration($settingValue[$settingValue['id']]['optionsConfiguration']);
                $this->contactProvider = $provider;
                $this->selectedContact = $settingValue[$settingValue['id']]['selectedContact'];
                $this->useContactProvider = true;
            }
        }

        // refresh config fields
        $this->populateConfig();
    }

    private function populateConfig()
    {
        $fields = ['senderAddress', 'senderName', 'replytoAdress', 'subject', 'template', 'showUid'];
        $languageService = $this->getLanguageService();

        foreach ($fields as $field) {
            self::alterConfigurationString($this->$field, null, [$this->tableName, $this->uid, $languageService]);
        }

        array_walk_recursive(
            $this->typoscriptSelects,
            'self::alterConfigurationString',
            [$this->tableName, $this->uid, $languageService]
        );
    }

    /**
     * @return array
     */
    public function getProviderConfiguration()
    {
        $providers = [];
        foreach ($this->contactProviders as $contactProvider) {
            $providers[] = $contactProvider->getModalConfiguration();
        }

        return $providers;
    }

    private function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    private static function alterConfigurationString(&$property, $key, $userData)
    {
        $table = $userData[0];
        $uid = $userData[1];
        $languageService = $userData[2];

        preg_match_all('/(FIELD:)(\w+)((?:\.)(\w+))?/', $property, $fieldStatements);

        if (count($fieldStatements[0])) {
            $reflectionService = new \TYPO3\CMS\Extbase\Reflection\ReflectionService();

            $record = BackendUtility::getRecord(
                $table,
                $uid
            );

            foreach ($fieldStatements[0] as $key => $fieldStatement) {
                $propertyName = $fieldStatements[2][$key];
                $replaceWith = '';

                if (isset($record[$propertyName])) {
                    $propertyValue = $record[$propertyName];

                    if ($propertyValue) {
                        $replaceWith = $propertyValue;
                    }

                    // in case of 1:n select the first foreign element
                    if (is_array($propertyValue)) {
                        $replaceWith = (int)$propertyValue[0];
                    }

                    // check if foreign property should be accessed FIELD:calendar.name
                    if ($replaceWith && isset($record['record_type']) && isset($fieldStatements[4]) && isset($fieldStatements[4][$key]) && $fieldStatements[4][$key] !== "") {
                        $schema = $reflectionService->getClassSchema($record['record_type']);
                        $properties = $schema->getProperties();
                        $foreignPropertyType = $properties[$propertyName]['type'];

                        $dataMapper = GeneralUtility::makeInstance(DataMapper::class);
                        $tableName = $dataMapper->getDataMap($foreignPropertyType)->getTableName();

                        // query foreign record
                        $foreignRecord = BackendUtility::getRecord(
                            $tableName,
                            $replaceWith
                        );

                        if ($foreignRecord && isset($foreignRecord[$fieldStatements[4][$key]])) {
                            $replaceWith = $foreignRecord[$fieldStatements[4][$key]];
                        }
                    }
                }
                $property = str_replace($fieldStatement, $replaceWith, $property);
            }
        }

        // check for LLLs
        preg_match_all('/(LLL:)(EXT\:)?([\w\-\/]+\.\w+\:[\.?\w]+)/', $property, $llStatements);

        foreach ($llStatements[0] as $llStatement) {
            $translation = $languageService()->sL($llStatement);
            $property = str_replace($llStatement, $translation, $property);
        }
    }

    /**
     * @return Contact[]
     */
    public function getContacts(): array
    {
        $contacts = [];

        if ($this->useContactProvider) {
            return $this->contactProvider->getContacts();
        }

        $contact = new Contact($this->recipientAddress);
        $contact->setName($this->recipientName);
        $contacts[] = $contact;

        return $contacts;
    }

}
