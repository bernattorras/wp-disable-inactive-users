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
*   Can specify which roles shouldn't be disabled (`Administrator` and `Editor` by default).
*   Disable email notifications can be sent to the customer and the site administrator.
*   The limit of days to wait until deactivating a user can be changed.
*   The plugin activation date (used for deactivating users that haven't logged in since the plugin was activated) can be changed as well.
*   A button to reactivate all the disabled users.
*   A new `Disabled` column is added to the Users page to show the status of each user.
*   A new `Last login` column is added to the Users page to show the last login date and the date that they got disabled.
*   A link to reactivate each disabled user individually.

## Screenshots

### Settings (`Users > Disable Inactive Users`)
![https://d.pr/i/uqblZq](https://d.pr/i/uqblZq+) 
Full Size: https://d.pr/i/uqblZq

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

## Sending email notifications
This plugin has an option to send email notifications to the users that are disabled or reminder notifications before they are disabled. All these notifications are scheduled as background tasks to avoid a negative impact in the performance of the site. 

The notifications are sent using the built-in [wp_mail](https://developer.wordpress.org/reference/functions/wp_mail/) functionality, so it is highly recommended to use an SMTP server to get a better mailing experience and avoid these notifications to land in the SPAM folder.

You can find some third-party SMTP plugins [here](https://wordpress.org/plugins/search/smtp). 

**Important**: If you have a large number of users, disabling the notification functionality is recommended to avoid massive notifications and a negative impact in your site's performance.

## Checking the scheduled events
This plugin make use of the Wordpress WP Cron Events to schedule the user notifications in the background and some other tasks like the ones that send reminder notifications or disable the users automatically.

If you want to check the scheduled events, you can install a plugin like [WP Crontrol](https://wordpress.org/plugins/wp-crontrol/) and search for all the plugin events (they all start with the `wpdiu_` prefix) in the `Tools > Cron Events` page.

You can learn more about this WordPress functionality [here](https://developer.wordpress.org/plugins/cron/scheduling-wp-cron-events/).

Here's an example of the scheduled events that the plugin uses:
![https://d.pr/i/91dkeM](https://d.pr/i/91dkeM+) 
Full Size: https://d.pr/i/91dkeM

## Unit tests
Assuming that you've already cloned the plugin repository and installed the required dependencies using composer, you can now start running the PHP unit tests in your local dev environment.
1. Run `bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]` e.g. `bin/install-wp-tests.sh wordpress_tests root root localhost latest` to install the unit tests.
2. Run `vendor/bin/phpunit` to run all unit tests.

For more info see: [WordPress.org > Plugin Unit Tests](https://make.wordpress.org/cli/handbook/misc/plugin-unit-tests/#running-tests-locally). 
