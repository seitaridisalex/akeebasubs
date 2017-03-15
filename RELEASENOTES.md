## Joomla and PHP Compatibility

We are developing, testing and using Akeeba Subscriptions using the latest version of Joomla! and a popular and actively maintained branch of PHP 7. At the time of this writing this is:
* Joomla! 3.6
* PHP 7.0

Akeeba Release System should be compatible with:
* Joomla! 3.4, 3.5, 3.6, 3.7
* PHP 5.4, 5.5, 5.6, 7.0, 7.1.

## Changelog

**Removed features**

* Removed translations

**Added features**

* akeeba/internal#6 Added "Missing Invoice" report
* gh-276 Added method getFieldValue() to UserInfo\Html
* New update server

**Miscellaneous changes**

* Added local debugging option for the PayPal plugin
* Updated VAT rates

**Bug fixes**

* gh-275 VAT rate does not show on subscribe page
* Recurring subscriptions would result in a PHP exception
* Wrong VAT calculation in recurring subscriptions
* No invoice generated when a subscription is upgraded using a subscription relation rule
* gh-280 Expiration notification and expiration control plugins don't respect the scheduling option