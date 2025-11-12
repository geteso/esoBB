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
 * Index page: fires events requires for the page to load and display.
 * Initializes controllers and plugins, works out what to display, and
 * displays it.
 */

// Basic page initialization.
require "lib/init.php";

// Set up the action controller.
$q1 = strtolower(@$_GET["q1"]);

// If the first address parameter is numeric, assume the conversation controller.
if (is_numeric($q1)) {
	$_GET["q4"] = @$_GET["q3"];
	$_GET["q3"] = @$_GET["q2"];
	$_GET["q2"] = @$_GET["q1"];
	$_GET["q1"] = $q1 = "conversation";
}
// Does this controller exist?  If not, just use the search action.
$eso->action = in_array($q1, $eso->allowedActions) ? $q1 : $eso->action = "search";

// Include and set up the controller corresponding to the chosen action.
require "controllers/$eso->action.controller.php";
$className = str_replace("-", "", $eso->action);
$eso->controller = new $className;
$eso->controller->eso =& $eso;

// Include the custom.php file.
if (file_exists("config/custom.php")) include "config/custom.php";

// Run plugin init() functions.  These will hook onto controllers and add things like language definitions.
foreach ($eso->plugins as $plugin) $plugin->init();

// Initialize eso.
$eso->init();

// Initialize the controller.
$eso->controller->init();

// Check if this is a feed request (Atom feed).
$isFeed = ($eso->view == "feed.view.php" or $eso->controller->view == "feed.view.php");

// Show the page.
if (!$isFeed) {
	header("Content-type: text/html; charset={$language["charset"]}");
	if (!empty($config["gzipOutput"]) or !ob_start("ob_gzhandler")) ob_start();
	$eso->render();
	ob_flush();
} else {
	// For feeds, render the controller directly to bypass the HTML wrapper
	// The feed controller already set the proper content-type header
	if (ob_get_level()) ob_end_clean();
	$eso->controller->render();
	exit;
}

// Clear messages from the session.
$_SESSION["messages"] = array();

?>
