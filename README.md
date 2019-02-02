# Responsive email extension for TYPO3

This is the offical documentation of the bw_email extension.

## About

You can use this extension to send whole pages or any other content entry that has a TCA definition.

The templates are parsed with [Zurb Foundation Inky](https://foundation.zurb.com/emails.html). You can use the Inky markup to generate the table markup needed for most mail clients.

## Installation

* require via composer ````composer require blueways/bw-email````
* install via Extension manager
* include typoscript setup and constants **after** your typoscript code via ```static template``` or by manuel include:

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
* include the static pageTS to the root of your page tree

## Templates

The extensions offers some default templates located in ``ÈXT:bw_email/Resources/Private/Templates``. You can add your own templates via constants:

```
plugin.tx_bwemail {
	view {
		templateRootPath =
		partialRootPath =
		layoutRootPath =
    }
}		
```

Keep in mind that the templates are added to ````page.10.templatRootPaths.118```` - naming a template like one of your default page templates will cause an override in your frontend!

## Default mail settings

````
plugin.tx_bwemail {
    settings {
            css = EXT:bw_email/Resources/Public/Css/app.css
            senderAddress = noreply@example.com
            senderName = Example sender name
            replytoAddress =
            subject = Example subject
            template = Default
            showUid = 1
            recipientAddress =
    }
}
````

## Data sources

Right from the start you can send single Emails to an email address from inside the email wizard. If you like to send emails to multiple people, you can create a Email data source entry and select one of the following build-in connectors:

* fe_users
* CSV file (upcoming)

## Usage in other extensions

You can use the ``Blueways\View\EmailView`` in your own extension to render responsive email HTML with Inky syntax. It works just like the ````StandaloneView````.

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

If you like to use external data, you can write your own ContactProvider. Just extend from ```Blueways\BwEmail\Service\ContactProvider``` and register the class via Hook:

# Known issues

* CSS files need to be hard coded in email template (see Default.html)
* Inline RTE links are wrapped with an additional ````<p></p>```` that causes line breaks
* Internal links may be broken

# Improvement ideas

* embed images in message
* separate backend module
* send mail log
