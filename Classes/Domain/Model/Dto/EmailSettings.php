<?php

namespace Blueways\BwEmail\Domain\Model\Dto;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class EmailSettings
 *
 * @package Blueways\BwEmail\Domain\Model\Dto
 */
class EmailSettings
{

    /**
     * @var string
     */
    public $senderAddress;

    /**
     * @var string
     */
    public $senderName;

    /**
     * @var string
     */
    public $replytoAddress;

    /**
     * @var string
     */
    public $subject;

    /**
     * @var string
     */
    public $template;

    /**
     * @var int
     */
    public $showUid;

    /**
     * @var \Blueways\BwEmail\Service\ContactProvider[]
     */
    public $contactProvider;

    /**
     * @var \Blueways\BwEmail\Domain\Model\Contact[]
     */
    public $contacts;

    /**
     * @var int
     */
    public $uid;

    /**
     * @var int
     */
    public $pid;

    /**
     * @var string
     */
    public $table;

    /**
     * @var array
     */
    public $typoscriptSelects;

    /**
     * @var array
     */
    protected $typoScriptSettings;

    public function __construct($typoscript = null)
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        if (!$typoscript) {
            $configurationManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
            $typoscript = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        }
        $tsService = $objectManager->get(TypoScriptService::class);
        $this->typoScriptSettings = $tsService->convertTypoScriptArrayToPlainArray($typoscript['plugin.']['tx_bwemail.']['settings.']);

        $this->senderAddress = $this->typoScriptSettings['senderAddress'];
        $this->senderName = $this->typoScriptSettings['senderName'];
        $this->replytoAddress = $this->typoScriptSettings['replytoAddress'];
        $this->subject = $this->typoScriptSettings['subject'];
        $this->template = $this->typoScriptSettings['template'];
        $this->typoscriptSelects = $this->typoScriptSettings['typoscriptSelects'];
        $this->showUid = (int)$this->typoScriptSettings['template'];
        $this->contactProvider = [];
        foreach ($this->typoScriptSettings['provider'] as $className => $options) {
            /** @var \Blueways\BwEmail\Service\ContactProvider $provider */
            $provider = GeneralUtility::makeInstance($className);
            $provider->applySettings($options);
        }

        $this->setTableOverrides();

        $this->populateConfig();
    }

    private function setTableOverrides()
    {
        if (!$this->table && !is_array($this->typoScriptSettings['tableOverrides'][$this->table])) {
            return;
        }

        $this->override($this->typoScriptSettings['tableOverrides'][$this->table]);
    }

    public function override($settings)
    {
        foreach ($settings as $settingName => $settingValue) {
            if (property_exists(self::class, $settingName)) {
                $this->$settingName = $settingValue;
            }

            if ($settingName === 'table') {
                $this->setTableOverrides();
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
            self::alterConfigurationString($this->$field, null, [$this->table, $this->uid, $languageService]);
        }

        array_walk_recursive(
            $this->typoscriptSelects,
            'self::alterConfigurationString',
            [$this->table, $this->uid, $languageService]
        );
    }

    /**
     * @return array
     */
    public function getProviderConfiguration()
    {
        $providers = [];
        if (isset($this->contactProvider)) {
            foreach ($this->contactProvider as $contactProvider) {
                $providers[] = $contactProvider->getModalConfiguration();
            }
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

                        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
                        $dataMapper = $objectManager->get(
                            \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class
                        );
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

        foreach ($llStatements[0] as $key => $llStatement) {
            $translation = $languageService()->sL($llStatement);
            $property = str_replace($llStatement, $translation, $property);
        }
    }
}
