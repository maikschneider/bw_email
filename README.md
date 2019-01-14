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

If you like to create your own data connector, you just just need to create your own ````Blueways\BwEmail\Domain\Model\Datasource```` type by extending the class. 
