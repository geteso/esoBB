<div align="center">

<img src="https://geteso.org/assets/img/logo.svg" alt="esoBB logo" width="225"/><br>

**Forum software that's lightweight and extensible.**

[![Code Size](https://img.shields.io/github/languages/code-size/geteso/esoBB?style=plastic)]()
[![Issues](https://img.shields.io/github/issues/geteso/esoBB?style=plastic)]()
[![License](https://img.shields.io/github/license/geteso/esoBB?style=plastic)]()
[![Version](https://img.shields.io/github/v/release/geteso/esoBB?include_prereleases&style=plastic)]()
[![PHP Version Support](https://img.shields.io/badge/php-%5E8.2.5-blue?style=plastic)]()

</div>

## About the project
The esoBB forum software is an open-source project which attempts to strive towards being super-fast, lightweight and extensible.  esoBB is based upon an earlier incarnation of [esoTalk](https://github.com/esotalk/esoTalk), a project which is no longer being maintained.  This fork started off as the codebase of a small forum by the name of *esotalk.ga* more than five years ago.  Since then, we have greatly expanded the software's functionality and refined it into an easy-to-use 'platform of choice', both for those looking to incorporate a forum into their pre-existing community as well as those setting about to create an entirely new community of their own.

Changes are always being made to the software as our users continue to make suggestions on the [esoBB support forum](https://forum.geteso.org/) and report any bugs through the [issues](https://github.com/geteso/esoBB/issues) channel, which is something that we openly encourage!

## Requirements
To run the esoBB forum software, you will need:

1. A web server, preferably [Apache](https://httpd.apache.org/) or [nginx](https://nginx.org/);
2. [PHP](https://www.php.net/) **7.2** or newer, preferably at least 7.3 (PHP 8 recommended);
3. The following PHP extensions:
	- `php-mysqli` (for handling MySQL database queries);
	- `php-mbstring` (for handling [multibyte strings](https://www.php.net/manual/en/intro.mbstring.php));
	- `php-gd` or `gd2` (for sizing and rescaling images), preferably one which was [compiled with WebP support](https://www.php.net/manual/en/function.gd-info.php).
4. [MySQL](https://www.mysql.com/) 5.6+ or [MariaDB](https://mariadb.org/) 10+ (latest version recommended).

The following PHP features are not required, however they are utilized if enabled:
1. `PCRE` or `PCRE2` (strongly encouraged - enabled by default in PHP), and;
2. `allow_url_fopen` (optional - can be used in My settings for remote avatar uploads, but usually disabled in the PHP environments of most third-party hosting services).

## Dependencies
The esoBB forum software depends on the following third-party libraries (vendor).

1. [PHPMailer](https://github.com/PHPMailer/PHPMailer) 7.0.1 by the PHPMailer organization.
2. [Steve Clay's port of JSMin](https://github.com/mrclay/jsmin-php) by Douglas Crockford.

There are no external JavaScript frameworks being used by the software, excluding any potential third-party plugins.

## How to install
For a comprehensive, step-by-step tutorial on how to install and create a forum using the esoBB forum software, you may refer to the [Guide to installing your forum](https://geteso.org/docs/install-guide/), located on the documentation page.

On the other hand, if you would rather somebody else do the hard work for you: consider the free, no-strings-attached [myesoBB forum hosting service](https://myeso.org/), made exclusively for the esoBB forum software (still a work-in-progress during the pre-release stage).

## Contributing
I am looking for people to contribute to this project as I do not have much free time and can only push this software forward so much.  If you have made a contribution then you are welcome to create a pull request and you will [become a contributor](https://github.com/geteso/esoBB/blob/master/CONTRIBUTORS).  If you make several contributions and show technical prowice I will add you to this project.

One of the most beneficial ways to help those who run forums using this software is by **creating a plugin, skin or language pack.**  If you are interested, learn more here: https://geteso.org/docs
