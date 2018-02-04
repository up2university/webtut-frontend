# Webtut frontend

## Objective
Basic Slim Micro PHP Framework with Twig, Idiorm and SAML2 tutoring application

## Install

### Depencies: 
1. Validate that PHP, all the needed PHP extensions and essential build packages are installed 
  * Debian: `apt-get install php5 build-essential git curl php5-curl php5-gmp php5-mcrypt mysql-server mysql-client mbstring`
  * Ubuntu: `apt-get install got curl php5-gmp`

### Clone this Repository
1. Clone this repository Install on a directory at your choice:
`git clone git@gitlab.fccn.pt:sa8-webrtc/webtut-frontend.git`

### Install and init PHP package manager **composer **
1. Download composer.phar following the instructions on https://getcomposer.org/download/
1. execute `./composer.phar update`


### Install/Configure NodeJs and npm
* **Debian:** Don't use the nodejs package from standard repo (uninstall if installed `apt-get remove nodejs`) but instead use these instructions for install: https://github.com/nodesource/distributions
* For Node.js v4.x: 
 * `curl -sL https://deb.nodesource.com/setup_4.x | bash -`
 * `apt-get install -y nodejs`
* **CentOS:** `yum install npm`
* Install node depencies for this project: `npm install`

### Produce Grunt Files
* `grunt bootstrap` (really needed?)
* `grunt dist`

### Install Database Schema ###
* If you want to define a database, please edit models/Database.mysql and change "webtut" database name to what you require.
* Import sql-database:`mysql -u root -p < models/Database.mysql`

### Setup configuration ###
* `cp app/config-dist.php app/config.php` and edit your config.php (db password, usernames, paths, ..)

### Validate paths ####
# Validate that the /cache and the /logs directory exist and are on the same user:group as the webserver
# mkdir cache
# mkdir logs
# `chown apache:apache cache`
# `chown apache:apache logs`

### Produce the translation files
# `cd locale`
# `make`
# Make sure there are no errors on the top related to database access

## Review The .htaccess file

On /html/.htaccess-dist there are references to a rewrite rule. Validate that does reflect your instalation. Copy it as .htaccess. Enable Apache to use .htaccess configuration directives.

On /httpd there is also an examples of a configuration file that may be used if you preffer to configure Apache directly.

## Misc
1. If you need debug info, set the environment variable "deploy_mode" to "development"

## PHP Extentions
1. ext/mcrypt
1. ext/openssl
1. ext/mbstring

