# Responsive email extension for TYPO3

This is the offical documentation of the bw_email extension.

## About

You can use this extension to send whole pages or any other element that has a TCA definition.

The templates are parsed with [Zurb Foundation Inky](https://foundation.zurb.com/emails.html). You can use the Inky
markup in your fluid templates to generate the table markup needed for most mail clients.

## Installation

* Require via composer ````composer require blueways/bw-email````
* Activate in Extension Manager
* Include static TypoScript template

## Templates

The extensions offers some default templates located in ``ÈXT:bw_email/Resources/Private/Templates``. You can override
the templates by setting the paths via constants:

```typo3_typoscript
plugin.tx_bwemail {
    view {
        templateRootPath =
        partialRootPath =
        layoutRootPath =
    }
}
```

## Default mail settings

To register new templates, use TypoScript setup:

```typo3_typoscript
plugin.tx_bwemail {
    settings {
        # Remove all default templates
        templates >
        templates {
            TemplateFileName = Title of Template
        }

        # Default setup
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
```

## Data sources

Right from the start you can send single Emails to an email address from inside the email wizard. If you like to send
emails to multiple people, you can create a Email data source entry and select one of the following build-in connectors:

* fe_users
* CSV file (upcoming)

## Usage in other extensions

You can use the ``Blueways\View\EmailView`` in your own extension to render responsive email HTML with Inky syntax. It
works just like the ```StandaloneView```.

## Extend

You can use the DataSource-Provider or create your own ContactProvider.

### DataSource model

If you like to configure your source via Backend, you can use the existing DataSource model. Create your own Model by
extending the ```ContactSource``` Model and implement the ```getContacts()``` method.

Don't forget to register the inheritance via typoscript:

```typo3_typoscript
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

If you like to use external data, you can write your own ContactProvider. Just extend
from ```Blueways\BwEmail\Service\ContactProvider``` and register the class via Hook:

# Known issues

* CSS files need to be hard coded in email template (see Default.html)
* Inline RTE links are wrapped with an additional ````<p></p>```` that causes line breaks
* Internal links may be broken

# Usage in other content elements

It is possible to use the email wizard in other content elements like Textmedia or News: Just add an element with the
TCA-RenderType "sendMailButton". Here is an example of how to add a Send Mail button to tt_content elements:

```php
// TCA/Overrides/tt_content.php
<?php

$tempColumns = [
    'mail_button' => [
        'label' => 'Send this tt_content',
        'config' => [
            'type' => 'passthrough',
            'renderType' => 'sendMailButton',
        ],
    ]
];

ExtensionManagementUtility::addTCAcolumns('tt_content', $tempColumns);
ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    'mail_button',
    '',
    'before:CType'
);
```

The current record will be available in the Fluid Template as ```{record}```.

To inject additional elements or override default settings you can use the ```tableOverrides``` TypoScript setting.

```typo3_typoscript
plugin.tx_bwemail {
    settings {
        subject = Default subject in all places
        tableOverrides {
            tt_content {
                subject = New subject
                recipientName = FIELD:header
                typoscriptSelects {
                    latestNews {
                        table = tx_news
                        select {
                            pidInList = 3
                            orderBy = sorting
                            max = 3
                        }
                    }
                }
            }
        }
    }
}
```

With the ```typoscriptSelects``` setting you can insert records to the email template. In the example above, you can
display the latest news records with ```<f:for each="{latestNews}" as="news">{news.title}</f:for>```.

# Todo

* Better translations
* Better default template organisation
* Absolute URL handling for new v9
* Move provider settings to TypoScript

# Improvement ideas

* embed images in message
* separate backend module
* send mail log
* sass compiler for Foundation for Email
