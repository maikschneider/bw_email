# blueways Email TYPO3 email extension

This is the offical documentation of the bw_email extension.

## About

You can use this extension to send whole pages or any other content entry that has a TCA definition.

The template is parsed with Zurb Foundation Inky.

## Installation

* require via composer ````composer require blueways/bw_email````
* install via Extension manager
* include typoscript setup and constants **after** your typoscript code:

constants:
```
[your typoscript]
<INCLUDE_TYPOSCRIPT: source="FILE:EXT:bw_email/Configuration/TypoScript/constants.typoscript">
``` 

setup:
```
[your typoscript]
<INCLUDE_TYPOSCRIPT: source="FILE:EXT:bw_email/Configuration/TypoScript/setup.typoscript">
``` 
* include the static pageTS in the root of your page tree

## Data sources

Right from the start you can send single Emails to an email address from inside the email wizard. If you like to send emails to multiple people, you can create a Email data source entry and select one of the following build-in connectors:

* fe_users (upcoming)
* CSV file (upcoming)

## Extend

You can use the DataSource-Provider or create your own ContactProvider.

### DataSource model

If you like to configure your source via Backend, you can use the existing DataSource model. Create your own Model by extending the ```ContactSource``` Model and implement the ```getContacts()``` method.

Don't forget to register the inheritance via typoscript:

```
config.tx_extbase.persistence.classes {
    Blueways\BwEmail\Domain\Model\ContactSource {
        subclasses {
            Vendor\Extension\YourModel = Vendor\Extension\YourModel
        }
    }
    Vendor\Extension\YourModel.mapping {
        recordType = Vendor\Extension\YourModel
        tableName = tx_bwemail_domain_model_contactsource
    }
}
```

### ContactProvider service

If you like to use external data, you can write your own ContactProvider. Just extend from ```Blueways\BwEmail\Service``` and register the class via Hook:

# Known issues

* RTE links are getting removed
