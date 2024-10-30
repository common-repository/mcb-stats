=== MCB - Stats ===
Plugin Name: MCB - Stats
Plugin URI: http://www.creativecode.es/
Author: mariusromanus
Author URI: http://www.creativecode.es/
Contributors: mariusromanus
Tags: time in page, time, page counter, page visit, post counter, post visit, stats, statistics, analytics, users, wordpress post view, wordpress page view, page visit graph, post visit graph, chart, table grid, manage
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=66LXBEKX7DGK2
Requires at least: 4
Tested up to: 4.7
Stable tag: trunk
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

MCB Stats collects statistics of users who access to the front part of wordpress, MCB Stast is capable of collecting the total amount of time a user spends in the different pages, this requires an important server load since it does insertions in the data base every X seconds of every connected user and opened page. Luckily this charge can be configured, the more seconds the less it loads, but also it will be less precise. Even if this is the best plugin’s potential, it also collects other statistics, like links users click, buttons, when they send forms or enter to a page, the logins and the log outs.

== Description ==
MCB Stats collects statistics of users who access to the front part of wordpress, MCB Stast is capable of collecting the total amount of time a user spends in the different pages, this requires an important server load since it does insertions in the data base every X seconds of every connected user and opened page. Luckily this charge can be configured, the more seconds the less it loads, but also it will be less precise. Even if this is the best plugin’s potential, it also collects other statistics, like links users click, buttons, when they send forms or enter to a page, the logins and the log outs.


= SETINGS =
It activates or deactivate the time the user has been in every page and enters and configures the precision of the data in seconds. The most accurate is it to be between 1 and 60 where 1 would be the maximun precision but it might also overload, in 60 the data would be updated every minute without generating any overload for the server. It is recommended to make load tests with this chart activated before putting it in production.


= DATA AND SATATISTICS OF USERS ACTIVITY =
The collected data is shown in grid format, it generates a chart with all the saved data or in a rank of specific dates. The chart contains the following fields:

* ID => The register’s ID
* User’s ID => In the case the user is logged in, if not it will appear a 0
* Name => user’s name in case he’s logged in
* Email => User’s email in case he is logged in
* ID post => ID of the post or actual page
* Title post => Title of the post or the actual page
* Type post => type of post or actual page: post, page, revision….
* Actual URL => URL where the user is
* Action => For managing and creating statistics various actions have been created which define the different types of registry:
--Load page: loaded page by the user
--Href: When a user clicks on a link
--Button: When a user clicks on a button
--Submit: When a user clicks and sends a form
--Time: Recollect of the time in the Page
--Login: When a user enters to the platform
--Logout: When a user leaves the platform
* URL Action link => in the action Href it will appear a url in which the user clicks and will be redirected. In the action buttons it will appear the button’s name, and in the Action Submit it will appear the form delivery.
* Date => Date in which is registered the data
* Time in page => in the case it is activated, here it will be shown the time the user has been in the page with the seconds interval indicated previously.
* IP => The user’s IP
* Browser => Navegator and version
* Platform => Linux, Windows, Mac, Android…
* User-agent => complete user-agent

All columns can be shown or hide. Once the chart has been generated it will be able to be exported to CSV, Excel, and PDF, it may also be printed and copied to the clipboard.
WARNING: The button Reset All will delete ALL the saved data for the moment without the possibility of recovery. It is recommended that before resetting the data, you save them in any of the exportable formats.


= GRAPHICS AND CHARDS =
Graphics where you will be able to easily see the top 10 of some of the the collected values.
Top 10 posts with the most accumulated time
Top 10 most logged in users
Top 10 most viewed posts
Top 10 browsers

== Installation ==
Automatic Plugin Installation

To add a WordPress Plugin using the built-in plugin installer:
Go to Plugins > Add New.
Type in the name of the WordPress Plugin or descriptive keyword, author, or tag in Search Plugins box or click a tag link below the screen.
Find the WordPress Plugin you wish to install.
Click Details for more information about the Plugin and instructions you may wish to print or save to help setup the Plugin.
Click Install Now to install the WordPress Plugin.
The resulting installation screen will list the installation as successful or note any problems during the install.
If successful, click Activate Plugin to activate it, or Return to Plugin Installer for further actions.
Some WordPress Plugins require more steps to customize them. The Details ReadMe file should contain step-by-step instructions. After installation, this information is available on the Plugins Screen for each Plugin. If you are having problems with a WordPress Plugin, see the Troubleshooting section.

Manual Plugin Installation
There are a few cases when manually installing a WordPress Plugin is appropriate.

If you wish to control the placement and the process of installing a WordPress Plugin.
If your server does not permit automatic installation of a WordPress Plugin.
The WordPress Plugin is not in the WordPress Plugins Directory.
Installation of a WordPress Plugin manually requires FTP familiarity and the awareness that you may put your site at risk if you install a WordPress Plugin incompatible with the current version or from an unreliable source.

Backup your site completely before proceeding.

To install a WordPress Plugin manually:

Download your WordPress Plugin to your desktop.
If downloaded as a zip archive, extract the Plugin folder to your desktop.
Read through the \"readme\" file thoroughly to ensure you follow the installation instructions.
With your FTP program, upload the Plugin folder to the wp-content/plugins folder in your WordPress directory online.
Go to Plugins screen and find the newly uploaded Plugin in the list.
Click Activate to activate it.

== Screenshots ==
1. Settings
2. Data and statistics of users activity
3. Graphics and chards

== Changelog ==
= 1.0 - 12.05.2017 =
Release