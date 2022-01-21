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
![https://d.pr/i/xaDJ7u](https://d.pr/i/xaDJ7u+) 
Full Size: https://d.pr/i/xaDJ7u

### Users columns (`Users`)
![https://d.pr/i/3NrOVg](https://d.pr/i/3NrOVg+) 
Full Size: https://d.pr/i/3NrOVg

## Installation

By deafult, this plugin doesn't require any configuration, as it will start working with the default settings once it is installed and active.
If wanted, it can be configured from `Users > Disable Inactive Users`.
 
Steps to install the plugin:

1. Upload the `wp-disable-inactive-users` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
