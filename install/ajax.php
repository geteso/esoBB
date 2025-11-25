<?php
/**
 * This file is part of the esoBB project, a derivative of esoTalk.
 * It has been modified by several contributors.  (contact@geteso.org)
 * Copyright (C) 2025 esoTalk, esoBB.  <https://geteso.org>
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

/**
 * Installer AJAX endpoint: handles AJAX requests for the installer.
 */
define("IN_ESO", 1);

// Define directory constants.
if (!defined("PATH_ROOT")) define("PATH_ROOT", realpath(__DIR__ . "/.."));
if (!defined("PATH_LIBRARY")) define("PATH_LIBRARY", PATH_ROOT."/lib");

// Require essential files.
require PATH_LIBRARY."/functions.php";

// Define the session save path.
session_save_path(PATH_ROOT."/sessions");
ini_set('session.gc_probability', 1);

// Start a session if one does not already exist.
if (!session_id()) session_start();

// Initialize CSRF token if not exists.
if (empty($_SESSION["token"])) {
	regenerateToken();
}

// Load language file.
$installLanguage = (!empty($_SESSION["installLanguage"])) ? $_SESSION["installLanguage"] : "English (casual)";
$installLanguage = sanitizeFileName($installLanguage);
if (file_exists("../languages/{$installLanguage}.php")) {
	include "../languages/{$installLanguage}.php";
} else {
	$installLanguage = "English (casual)";
	include "../languages/{$installLanguage}.php";
}

// Sanitize the request data using sanitize().
$_POST = sanitize($_POST);
$_GET = sanitize($_GET);

// Set up the Install controller.
require "install.controller.php";
$install = new Install();

// Call the ajax() method.
$controllerResult = $install->ajax();

// Wrap the result in the expected format (same as main ajax.php)
// JavaScript expects: { "messages": [], "result": <controller result>, "token": <optional> }
$result = array("messages" => array(), "result" => $controllerResult);

// If the token the user has is invalid, send them a new one.
if (isset($_POST["token"]) && $_POST["token"] !== $_SESSION["token"]) {
	$result["token"] = $_SESSION["token"];
}

// Output the result as JSON.
header("Content-type: text/plain; charset=utf-8");
echo json($result);

// Flush output buffer if it exists (like main ajax.php does)
if (ob_get_level()) {
	ob_end_flush();
}

?>

