<?php
/**
 * This file is part of the esoBB project, a derivative of esoTalk.
 * It has been modified by several contributors.  (contact@geteso.org)
 * Copyright (C) 2023 esoTalk, esoBB.  <https://geteso.org>
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
define("IN_ESO", 1);

/**
 * Upgrader: this page is loaded whenever the build version in
 * config.default.php differs from the one the forum is up-to-date with
 * (in config.php).  Sets up the Upgrade controller.
 */

// No timeout.
@set_time_limit(0);

// Get the new version and the current version, and compare them.  If we don't need to upgrade, home we go!
require "../config.default.php";
require "../config/versions.php";
if ($versions["eso"] == ESO_VERSION) {
	header("Location: ../index.php");
	exit;
}

// Require essential files.
require "../lib/functions.php";
require "../lib/classes.php";
require "../lib/database.php";
require "../config/config.php";
require "upgrade.controller.php";

// Sanitize the request data using sanitize().
$_POST = sanitize($_POST);
$_GET = sanitize($_GET);
$_COOKIE = sanitize($_COOKIE);

// Set up the upgrade controller and start the upgrade.
$upgrade = new Upgrade();
$upgrade->init();

?>
