<?php

namespace Blueways\BwEmail\Domain\Model;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class WizardConf
 *
 * @package Blueways\BwEmail\Domain\Model
 */
class WizardConf
{

    /**
     * @var array
     */
    public $settings = [];

    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
    protected $uid;

    /**
     * @var string
     */
    protected $pid;

    /**
     * WizardConf constructor.
     *
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function __construct($table, $uid, $pid)
    {
        $this->table = $table;
        $this->uid = $uid;
        $this->pid = $pid;
        $this->createFromTypoScript();
    }

    /**
     * Init $settings with values from TypoScript
     *
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function createFromTypoScript()
    {
        $objectManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
        /** @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManager $configurationManager */
        $configurationManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
        $typoScript = $configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );

        ArrayUtility::mergeRecursiveWithOverrule(
            $this->settings,
            $typoScript['plugin.']['tx_bwemail.']['settings.'],
            true,
            false
        );

        // override typoscript settings with overrides from table
        if (isset($this->settings['tableOverrides.'][$this->table . '.'])) {
            ArrayUtility::mergeRecursiveWithOverrule($this->settings,
                $this->settings['tableOverrides.'][$this->table . '.']);
        }

        // set fixed values (even if record was not saved before)
        $this->settings['table'] = $this->table;
        $this->settings['uid'] = $this->uid;
        $this->settings['pid'] = $this->pid;

        /**
         * Alter config fields
         *
         * @param string $item
         * @param $key
         * @param self $self
         */
        $editFields = function (&$item, $key, $self) {

            // check for FIELDS
            preg_match_all('/(FIELD:)(\w+)((?:\.)(\w+))?/', $item, $fieldStatements);

            if (sizeof($fieldStatements[0])) {

                $reflectionService = new \TYPO3\CMS\Extbase\Reflection\ReflectionService();

                $record = BackendUtility::getRecord(
                    $this->settings['table'],
                    $this->settings['uid']
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
                    $item = str_replace($fieldStatement, $replaceWith, $item);
                }
            }

            // check for LLLs
            preg_match_all('/(LLL:)(EXT\:)?([\w\-\/]+\.\w+\:[\.?\w]+)/', $item, $llStatements);

            foreach ($llStatements[0] as $key => $llStatement) {
                $translation = $self->getLanguageService()->sL($llStatement);
                $item = str_replace($llStatement, $translation, $item);
            }
        };
        array_walk_recursive($this->settings, $editFields, $this);
    }

    /**
     * @return mixed|\TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return array
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public function getDataAttributesForButton()
    {
        return [
            'wizard-uri' => $this->getWizardUri(),
            'modal-title' => $this->settings['modalTitle'],
            'modal-send-button-text' => $this->settings['modalSendButton'],
            'modal-cancel-button-text' => $this->settings['modalCancelButton']
        ];
    }

    /**
     * @param string $routeName
     * @return string
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public function getWizardUri($routeName = 'ajax_wizard_modal_page')
    {
        $uriArguments = [];
        $uriArguments['arguments'] = json_encode($this->settings);
        $uriArguments['signature'] = GeneralUtility::hmac(
            $uriArguments['arguments'],
            $routeName
        );

        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);

        return (string)$uriBuilder->buildUriFromRoute($routeName, $uriArguments);
    }

    /**
     * Used in custom FormElement
     *
     * @param array $tcaConfig
     */
    public function setTcaConfig(array $tcaConfig)
    {
        if (!$tcaConfig || !is_array($tcaConfig)) {
            return;
        }

        // merge with TCA (TCA can unset settings)
        ArrayUtility::mergeRecursiveWithOverrule($this->settings, $tcaConfig['parameterArray']['fieldConf']['config']);

        // set fixed values (even if record was not saved before)
        $this->settings['databaseTable'] = $tcaConfig['tableName'];
        $this->settings['databaseUid'] = $tcaConfig['vanillaUid'];
        $this->settings['databasePid'] = $tcaConfig['effectivePid'];

        /**
         * Alter config fields
         *
         * @param string $item
         * @param $key
         * @param $tcaConfig
         */
        $editFields = function (&$item, $key, $tcaConfig) {
            if (substr($item, 0, 6) === 'FIELD:' && $savedData = $tcaConfig['databaseRow'][substr($item, 6)]) {
                $item = $savedData;
            }
        };
        // insert data from record
        array_walk_recursive($this->settings, $editFields, $tcaConfig);

        $this->translateFields();
    }

    /**
     * @param string $jobType
     */
    public function setJobType(string $jobType)
    {
        $this->settings['jobType'] = $jobType;
    }

    /**
     * @param \Blueways\BwEmail\Domain\Model\MailLog $log
     */
    public function createFromMailLog(MailLog $log)
    {
        $this->settings = [];
        $this->settings['recipientAddress'] = $log->getRecipientAddress();
        $this->settings['recipientName'] = $log->getRecipientName();
        $this->settings['subject'] = $log->getSubject();
        $this->settings['senderName'] = $log->getSenderName();
        $this->settings['senderAddress'] = $log->getSenderAddress();
        $this->settings['senderReplyto'] = $log->getSenderReplyto();
        $this->settings['mailLog'] = $log->getUid();
    }
}
