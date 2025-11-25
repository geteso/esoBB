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
Our software is based on an earlier incarnation of an unmaintained project named [*esoTalk*](https://github.com/esotalk/esoTalk) headed by <a href="http://tobyzerner.com/">Toby Zerner</a>.  We started as a small, private forum by the name of "esotalk.ga" in order to test our fork of the software more than five years ago.  Since then, esoBB has grown into a considerable forum software with many exclusive features.

Changes are always being made as our users [make suggestions on the support forum](https://forum.geteso.org) which is something that we openly encourage.  We are not affiliated with the original *esoTalk* project.

## Requirements
To run the esoBB forum software, you will need:

1. A web server, preferably [Apache](https://httpd.apache.org/) or [nginx](https://nginx.org/);
2. [PHP](https://www.php.net/) **7.2** or newer, preferably 7.3 or newer (PHP 8 recommended);
3. The following PHP extensions:
	a. `php-mysqli` (for handling MySQL database queries);
	b. `php-mbstring` (for handling [multibyte strings](https://www.php.net/manual/en/intro.mbstring.php));
	c. `php-gd` or `gd2` (for sizing and rescaling images), preferably one which was [compiled with WebP support](https://www.php.net/manual/en/function.gd-info.php).
4. [MySQL](https://www.mysql.com/) 5.6+ or [MariaDB](https://mariadb.org/) 10+ (latest version recommended).

The following PHP features are optional:
a. `PCRE` or `PCRE2` (enabled by default in PHP), and;
b. `allow_url_fopen` (can be used in My settings for remote avatar uploads, but usually disabled in the PHP environments of most third-party hosting services).

## Dependencies
The esoBB forum software depends on the following third-party libraries (vendor).

1. [PHPMailer](https://github.com/PHPMailer/PHPMailer) 7.0.1 by the PHPMailer organization.
2. Ryan Grove's port of [JSMin](https://www.crockford.com/jsmin.html) by Douglas Crockford.

There are no external JavaScript frameworks being used by the software, excluding any potential third-party plugins.

## Contributing
I am looking for people to contribute to this project as I do not have much free time and can only push this software forward so much.  If you have made a contribution then you are welcome to create a pull request and you will [become a contributor](https://github.com/geteso/esoBB/blob/master/CONTRIBUTORS).  If you make several contributions and show technical prowice I will add you to this project.

One of the most beneficial ways to help those who run forums using this software is by **creating a plugin, skin or language pack.**  If you are interested, learn more here: https://geteso.org/docs
