# 5.2.3

**Added features**

* More price formatting options in the asprice content plugin

**Bug fixes**

* gh-281 Paypal IPN issues
* Joomla! 3.7 added a fixed width to specific button classes in the toolbar, breaking the page layout
* Joomla! 3.7.0 broke the JDate package, effectively ignoring timezones, causing grave errors in date / time calculations and display
* Joomla! 3.7.0 has a broken System - Page Cache plugin leading to white pages and wrong redirections

# 5.2.2

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

# 5.2.1

(Ignore that version)

# 5.2.0

**Important changes**

* NO SUPPORT – INTERNAL PROJECT ONLY
* Invoices could be downloaded by anyone who knows the invoice ID without being logged in

**Removed features**

* Removed options: use email as username (no longer allowed)
* Removed options: show business fields, show regular fields, show discount field, show coupon field, show state (all fields are shown, use template overrides to hide them)
* Removed options: show/hide countries from the subscription page (all countries are displayed)
* Removed options: allow login (not logged in users always see the login area, use view template overrides to hide that)
* Removed JS comments working around third party Javascript bugs. If you get a JS bug in the front-end FIX YOUR SITE.
* Removed admin module showing latest subscriptions
* Removed AcyMailing integration plugin
* Removed Akeeba Ticket System 1.x credits integration plugin
* Removed automatic country and city fill plugin
* Removed Google Analytics for Commerce integration plugin
* Removed custom fields feature
* Removed intellectual property integration plugin
* Removed Joomla user profile integration plugin
* Removed Kunena integration plugin
* Removed reCaptcha integration plugin
* Removed Slave Subscriptions feature
* Removed custom SQL plugin
* Removed debug subscriptions email plugin
* Removed obsolete languages
* Removing CLI scripts, they can't work reliably
* No more PostgreSQL support

**Miscellaneous changes**

* Show a validation error if a user doesn't enter a username when submitting the form
* The subscription form is now implemented with a Blade template and ONLY supports Bootstrap 3 markup
* Updated PayPal payments plugin with forced TLSv1.2. You MUST use a compatible server!
* Working around Joomla! 3.5 and later mail sending backwards incompatible behaviour change
* Much better solution to remembering form information: Fields will contain saved information from your last subscription when you access the Level page *UNLESS* you have EXPLICITLY ended up there by submitting an invalid form.
* Updated VAT rates
* Adjustment for Joomla! 3.6 log dir change
* Update TCPDF to the newest available v6 release
* Do not delete New / Cancelled subscriptions, lets the collation plugins do their job correctly

**Added features**

* Edit the pre-discount amount in the back-end subscription edit form
* My Subscriptions module
* Credit notes (inverse of invoices), created against already issued invoices
* Invoicing information shown in the backend subscription edit page
* You can disable the Do Not Track warning
* PayPal collation plugin

**Bug fixes**

* Recurring subscriptions did not issue invoices
* ATS credits and the reseller plugin were referencing the obsolete FOF 2 instead of the correct FOF 3 classes
* The converted currency price didn't include VAT and signup fees even when the uncoverted price did
* Do not remove akeebabackup.com from the emails. It's breaking the emails we're sending from our own site...
* If someone blanked out their VAT number after using a VIES-registered VAT number no VAT was charged
* After submitting an invalid form the fields were filled with the saved user state parameters instead of the submitted user's data
* Cannot download invoice from the front-end
* "Run Integrations" didn't work
* An empty username is NOT acceptable
* Do not use recurring amount for the first initial payment
* Spooky error with the logout plugin and Joomla! 3.5+ session management
* Unhandled unsuspended reccuring payment
* Content plugins would fail to execute because they were using the wrong container
* downloadid was missing on the invoice due to changed paths & namespaces in ARS
* When an outdated plugin was used the wrong path was reported. Thanks @Radek-Suski
