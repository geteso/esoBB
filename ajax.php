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
 * Ajax page: fires events required for an ajax page to load and display.
 * Initializes controllers and plugins, works out what to display, and
 * displays it.
 */
define("AJAX_REQUEST", 1);

// Basic page initialization.
require "lib/init.php";

// Start output buffering if gzip is enabled and client accepts it.
// Must be done after init.php loads $config, but before any output.
if (!ob_get_level()) {
	if (!empty($config["gzipOutput"]) and isset($_SERVER["HTTP_ACCEPT_ENCODING"]) and strpos($_SERVER["HTTP_ACCEPT_ENCODING"], "gzip") !== false) {
		if (!ob_start("ob_gzhandler")) ob_start();
	} else {
		ob_start();
	}
}

// Set up the action controller.
if (isset($_GET["controller"])) {
	$eso->action = strtolower($_GET["controller"]);
	
	// Does this controller exist?
	if (!in_array($eso->action, $eso->allowedActions) or !file_exists(dirname(__FILE__) . "/controllers/$eso->action.controller.php")) exit;
	
	// Require and set it up.
	require_once "controllers/$eso->action.controller.php";
	$className = str_replace("-", "", $eso->action);
	$eso->controller = new $className;
	$eso->controller->eso =& $eso;
}

// If none was specified, use the main controller.
else {
	$eso->controller =& $eso;
	$eso->action = "eso";
}

// Include the custom.php file.
if (file_exists("config/custom.php")) include "config/custom.php";

// Run plugin init() functions.  These will hook onto controllers and add things like language definitions.
foreach ($eso->plugins as $plugin) $plugin->init();

// Forum software initialization.
$eso->init();

// Now we're going to collect the result from the controller's ajax() function.
$controllerResult = null;

// Are we still logged in?  If not, display a "been logged out" message/form.
if (!empty($_POST["loggedInAs"]) and empty($eso->user["name"])) {
	$eso->message("beenLoggedOut", false, array($_POST["loggedInAs"], "<input id='loginMsgPassword' type='password' class='text' onkeypress='if(event.keyCode==13)document.getElementById(\"loginMsgSubmit\").click()'/> 
<input type='submit' value='{$language["Log in"]}' onclick='Ajax.login(document.getElementById(\"loginMsgPassword\").value);return false' id='loginMsgSubmit'/> <input type='button' value='{$language["Cancel"]}' onclick='Ajax.dismissLoggedOut()'/>"));
}

// Everything's fine; we're still logged in.  Proceed with normal page actions.
else {
	// Initialize the controller.
	$eso->controller->init();
	// Run the controller's ajax function.
	$controllerResult = $eso->controller->ajax();
}

// $result is the variable that we will pass through json() and output for parsing.
$result = array("messages" => [], "result" => $controllerResult);

// If the token the user has is invalid, send them a new one.
if (isset($_POST["token"]) and $_POST["token"] != $_SESSION["token"]) $result["token"] = $_SESSION["token"];

$eso->callHook("ajaxFinish", array(&$result));

// Format and collect all messages from the session into the $result["messages"] array.
if (count($_SESSION["messages"])) {
	foreach ($_SESSION["messages"] as $k => $v) {
		if (!isset($messages[$v["message"]])) continue;
		$m = $messages[$v["message"]];
		if (!empty($v["arguments"])) $m["message"] = is_array($v["arguments"]) ? vsprintf($m["message"], $v["arguments"]) : sprintf($m["message"], $v["arguments"]);
		$result["messages"][$v["message"]] = array($m["class"], $m["message"], $v["disappear"]);
	}
}

// Output the array.
header("Content-type: text/plain; charset={$language["charset"]}");
echo json($result);
ob_end_flush();

// Clear the messages array.
$_SESSION["messages"] = [];

