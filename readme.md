# Wp Disable Inactive Users

A WordPress plugin that disables inactive users automatically.

## Description

This is a plugin that will disable the users that haven't logged in for more than a specific number of days (90 by default).
By default, the plugin blocks and disables the users when they try to log in and more than 90 days have passed since their last login. 

If the plugin hasn't logged their last login (for example if they haven't logged in yet since the plugin was activated), it will take into consideration the plugin activation date to check if they haven't logged in for more than 90 days.

## Features:
*   After the specified days of inactivity, the users will not be able to log back to their account.
*   Their content (pages, posts, comments, etc) will still be accessible.
*   The plugin will disable the users when they try to log in again.
*   An option can be enabled to check the users daily and bulk disable them automatically.
*   Can specify which roles shouldn't be disbled (`Administrator` and `Editor` by default.
*   Disable email notifications can be sent to the customer and the site administrator.
*   The limit of days to wait until deactivating a user can be changed.
*   The plugin activation date (used for deactivating users that haven't logged in since the plugin was activated) can be changed as well.
*   A new `Disabled` column is added to the Users page to show the status of each user.
*   A new `Last login` column is added to the Users page to show the last login date and the date that they got disa
## Screenshots

### Settings (`Users > Disable Inactive Users`)
![https://d.pr/i/1RiXxM](https://d.pr/i/1RiXxM+) 
Full Size: https://d.pr/i/1RiXxM

### Users columns (`Users`)
![https://d.pr/i/F9lvCR](https://d.pr/i/F9lvCR+) 
Full Size: https://d.pr/i/F9lvCR

## Installation
A build step is required when directly using the files in this repository as a plugin.
1. Install prerequisites: composer, git, svn, wget or curl, mysqladmin
2. Clone this repository into the plugins directory.
3. Run `composer install`
4. Activate the plugin from the Plugins page.

Once the plugin is active, it will start working automatically with the default settings.
If wanted, it can be configured from `Users > Disable Inactive Users`.

## Unit tests
Assuming that you've already clonned the plugin repository and installed the required dependencies using composer, you can now start running the PHP unit tests in your local dev environment.
1. Run `bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]` e.g. `bin/install-wp-tests.sh wordpress_tests root root localhost latest` to install the unit tests.
2. Run `vendor/bin/phpunit` to run all unit tests.

For more info see: [WordPress.org > Plugin Unit Tests](https://make.wordpress.org/cli/handbook/misc/plugin-unit-tests/#running-tests-locally).
