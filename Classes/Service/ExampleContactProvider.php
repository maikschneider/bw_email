<?php

namespace Blueways\BwEmail\Service;

use Blueways\BwEmail\Domain\Model\Contact;
use Blueways\BwEmail\Domain\Model\ContactProviderOption;
/**
 * Class ExampleContactProvider
 *
 * @package Blueways\BwEmail\Service
 */
class ExampleContactProvider extends ContactProvider
{
    protected $name = 'Example Provider';

    protected $description = 'Description';

    /**
     * @return Contact[]
     */
    public function getContacts()
    {
        return [];
    }

    /**
     * @return mixed
     */
    protected function createOptions()
    {
        $this->options[] = new ContactProviderOption(
            'Example select',
            'examplename1',
            'select',
            ['first option', 'second option', 'third option']
        );
        $this->options[] = new ContactProviderOption(
            'Example input',
            'examplename2',
            'input',
            ['default value']
        );
    }
}
