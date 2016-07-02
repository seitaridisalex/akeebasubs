# Testing Akeeba Subscriptions

You need to prepare a guinea pig site for use with Unit Tests. *Do not use an existing site*. Akeeba Subscriptions
data and user data *will* be removed during testing.

Steps to follow:

1. Create a new Joomla! 3 site, e.g. in `~/Sites/guinea_pig`

1. Build and install Akeeba Subscriptions on it

1. Symlink Akeeba Subs to the site, e.g. `cd build; phing relink -Dsite=~/Sites/guinea_pig`

1. Copy `Tests/config.dist.php` to `Tests/config.php` and set the `site_root` to the absolute filesystem path to your guinea pig site

1. Run the Unit Tests
