<?php

namespace Blueways\BwEmail\Service;

class ExampleContactProvider extends ContactProvider
{
    protected $name = 'Example Provider';

    protected $description = 'Description';

    /**
     * @return \Blueways\BwEmail\Domain\Model\Contact[]
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
        $this->options[] = new \Blueways\BwEmail\Domain\Model\ContactProviderOption(
            'Example select',
            'examplename1',
            'select',
            ['first option', 'second option', 'third option']
        );
        $this->options[] = new \Blueways\BwEmail\Domain\Model\ContactProviderOption(
            'Example input',
            'examplename2',
            'input',
            ['default value']
        );
    }
}
