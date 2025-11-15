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

/**
 * Basic page initialization: include configuration settings, check the
 * version, require essential files, start a session, fix magic quotes/
 * register_globals, sanitize request data, include the eso controller,
 * skin file, language file, and load plugins.
 */
if (!defined("IN_ESO")) exit;

// Start a page load timer. We don't make use of it by default, but a plugin can if it needs to.
define("PAGE_START_TIME", microtime(true));

// By default, only report important errors (no warnings or notices.)
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);

// Make sure a default timezone is set... silly PHP 5.
if (ini_get("date.timezone") == "") date_default_timezone_set("GMT");

// Define directory constants.
if (!defined("PATH_ROOT")) define("PATH_ROOT", realpath(__DIR__ . "/.."));
if (!defined("PATH_CONFIG")) define("PATH_CONFIG", PATH_ROOT."/config");
if (!defined("PATH_CONTROLLERS")) define("PATH_CONTROLLERS", PATH_ROOT."/controllers");
if (!defined("PATH_LANGUAGES")) define("PATH_LANGUAGES", PATH_ROOT."/languages");
if (!defined("PATH_LIBRARY")) define("PATH_LIBRARY", PATH_ROOT."/lib");
if (!defined("PATH_PLUGINS")) define("PATH_PLUGINS", PATH_ROOT."/plugins");
if (!defined("PATH_SKINS")) define("PATH_SKINS", PATH_ROOT."/skins");
if (!defined("PATH_UPLOADS")) define("PATH_UPLOADS", PATH_ROOT."/uploads");
if (!defined("PATH_VIEWS")) define("PATH_VIEWS", PATH_ROOT."/views");

// Include our config files.
require PATH_ROOT."/config.default.php";
@include PATH_CONFIG."/config.php";
// If $config isn't set, the forum hasn't been installed.  Redirect to the installer.
if (!isset($config)) {
	if (!defined("AJAX_REQUEST")) header("Location: install/index.php");
	exit;
}
// Combine config.default.php and config/config.php into $config (the latter will overwrite the former.)
$config = array_merge($defaultConfig, $config);

// Compare the hardcoded version of eso (ESO_VERSION) to the installed one ($versions["eso"]).
// If they're out-of-date, redirect to the upgrader.
require PATH_CONFIG."/versions.php";
if ($versions["eso"] != ESO_VERSION) {
	if (!defined("AJAX_REQUEST")) header("Location: {$config["baseURL"]}upgrade/index.php");
	exit;
}

// Compare the forum base URL's host to the actual request's host.  If they differ, redirect to the base URL.
// i.e. if baseURL is www.example.com and the forum is accessed from example.com, redirect to www.example.com.
if (isset($_SERVER["HTTP_HOST"])) {
	$urlParts = parse_url($config["baseURL"]);
	if (isset($urlParts["port"])) $urlParts["host"] .= ":{$urlParts["port"]}";
	if ($urlParts["host"] != $_SERVER["HTTP_HOST"]) {
		header("Location: " . $config["baseURL"] . substr($_SERVER["REQUEST_URI"], strlen($urlParts["path"])));
		exit;
	}
}

// Require essential files.
require PATH_LIBRARY."/functions.php";
require PATH_LIBRARY."/database.php";
require PATH_LIBRARY."/classes.php";
require PATH_LIBRARY."/formatter.php";
require PATH_LIBRARY."/uploader.php";

// Define the session save path.
session_save_path(PATH_ROOT."/sessions");
ini_set('session.gc_probability', 1);

if (session_id()) {
	// Only validate session if it has the required fields (prevents issues after session regeneration)
	if (isset($_SESSION["ip"]) && isset($_SESSION["time"]) && isset($_SESSION["userAgent"])) {
		// Destroy sessions that are started longer ago than sessionExpire.
		$sessionExpired = (time() - $_SESSION["time"] > $config["sessionExpire"]);
		
		// IP validation is optional (disabled by default) as it breaks for users behind proxies/VPNs/load balancers
		// When enabled, it provides additional security but may cause legitimate users to be logged out
		$ipMismatch = false;
		if (!empty($config["validateSessionIp"])) {
			$ipMismatch = ($_SERVER["REMOTE_ADDR"] != $_SESSION["ip"]);
		}
		
		// Check the current user agent against the one that initiated the session.
		$userAgentMismatch = (md5($_SERVER["HTTP_USER_AGENT"]) != $_SESSION["userAgent"]);
		
		if ($sessionExpired or $ipMismatch or $userAgentMismatch) {
			session_unset();
			session_destroy();
		}
	}
// Start a session if one does not already exist.
} else {
	session_name("{$config["cookieName"]}_Session");
	// Configure session cookie parameters
	$lifetime = 0; // Session cookie (expires when browser closes)
	$path = "/";
	$domain = $config["cookieDomain"] ? $config["cookieDomain"] : "";
	$secure = !empty($config["https"]); // Only send over HTTPS if enabled
	$httponly = true; // Prevent JavaScript access
	// session_set_cookie_params() array syntax requires PHP 7.3.0+
	if (PHP_VERSION_ID >= 70300) {
		session_set_cookie_params(array(
			"lifetime" => $lifetime,
			"path" => $path,
			"domain" => $domain,
			"secure" => $secure,
			"httponly" => $httponly,
			"samesite" => "Lax"
		));
	} else {
		// PHP 7.2.x compatibility: use individual parameters
		// Note: SameSite cannot be set for session cookies in PHP 7.2.x via session_set_cookie_params()
		// Regular cookies (remember me) will still get SameSite via manual header setting
		session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
	}
	session_start();
	$_SESSION["ip"] = $_SERVER["REMOTE_ADDR"];
	$_SESSION["time"] = time();
	$_SESSION["userAgent"] = md5($_SERVER["HTTP_USER_AGENT"]);
	if (empty($_SESSION["token"])) regenerateToken();
}

// Undo register_globals.
undoRegisterGlobals();

// Do we want to force HTTPS?
if (!empty($config["https"]) and $_SERVER["HTTPS"] != "on") {
    header("Location: https://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]);
    exit;
}

// Set security headers
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Replace GET values with ones from the request URI.  (ex. index.php/test/123 -> ?q1=test&q2=123)
if (!empty($config["useFriendlyURLs"]) and isset($_SERVER["REQUEST_URI"])) {
	$parts = processRequestURI($_SERVER["REQUEST_URI"]);
	for ($i = 1, $count = count($parts); $i <= $count; $i++) $_GET["q$i"] = $parts[$i - 1];
}

// Sanitize the request data using sanitize().
$_POST = sanitize($_POST);
$_GET = sanitize($_GET);
$_COOKIE = sanitize($_COOKIE);

// Include and set up the main controller.
require PATH_CONTROLLERS."/eso.controller.php";
$eso = new eso();
$eso->eso =& $eso;

// Redirect if the 'Start a conversation' button was pressed.
if (isset($_POST["new"]) and !defined("AJAX_REQUEST")) redirect("conversation", "new");

// Include the language file.
$eso->language = sanitizeFileName((!empty($_SESSION["user"]["language"]) and file_exists("languages/{$_SESSION["user"]["language"]}.php")) ? $_SESSION["user"]["language"] : $config["language"]);
if (file_exists("languages/$eso->language.php")) include "languages/$eso->language.php";
// If we haven't got a working language, show an error!
if (empty($language)) $eso->fatalError("We can't find a language file to use. Please make sure <code>languages/$eso->language.php</code> exists or change the default language by adding <code>\"language\" => \"YourLanguage\",</code> to <code>config/config.php</code>.", "language");

// Include the skin file.
require "config/skin.php";
if (file_exists("skins/{$config["skin"]}/skin.php")) include_once "skins/{$config["skin"]}/skin.php";
if (class_exists($config["skin"])) {
	$eso->skin = new $config["skin"];
	$eso->skin->eso =& $eso;
	$eso->skin->init();
}
// If we haven't got a working skin, show an error.
if (empty($eso->skin)) $eso->fatalError("We can't find a skin file to use. Please make sure <code>skins/{$config["skin"]}/skin.php</code> exists or change the default skin in <code>config/skin.php</code>.", "skin");

// Load plugins, which will hook on to controllers.
require "config/plugins.php";
foreach ($config["loadedPlugins"] as $v) {
	$v = sanitizeFileName($v);
	if (file_exists("plugins/$v/plugin.php")) include_once "plugins/$v/plugin.php";
	if (class_exists($v)) {
		$eso->plugins[$v] = new $v;
		$eso->plugins[$v]->eso =& $eso;
	}
}

?>
