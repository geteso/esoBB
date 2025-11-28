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
if (!defined("IN_ESO")) exit;

/**
 * Debug plugin: shows programming debug information for administrators.
 */
class Debug extends Plugin {

public ?string $id = "Debug";
public ?string $name = "Debug";
public ?string $version = "1.0";
public ?string $description = "Shows programming debug information for administrators";
public ?string $author = "the esoBB team";
public ?array $defaultConfig = [
	"showToNonAdmins" => false
];

public float $start = 0;
public float $queryTimer = 0;
public string $log = "";

function __construct()
{
	// Set verboseFatalErrors to true.
	global $config;
	$config["verboseFatalErrors"] = true;
	
	// Start the page load timer.
	$this->start = $this->microtimeFloat();
	if (empty($_SESSION["queries"]) or !is_array($_SESSION["queries"])) $_SESSION["queries"] = [];
	
	parent::__construct();
}

function init()
{
	parent::init();
	
	// Add hooks to be run before and after database queries.
	$this->eso->addHook("beforeDatabaseQuery", array($this, "startQueryTimer"));
	$this->eso->addHook("afterDatabaseQuery", array($this, "addQuery"));
	$this->eso->addLanguage("seconds", "seconds");
	
	// If this is an AJAX request, add a hook to add debug information to the returned JSON array.
	if (defined("AJAX_REQUEST")) {
		$this->eso->addHook("ajaxFinish", array($this, "addInformationToAjaxResult"));
		return;
	}
	
	// Add language definitions, scripts, and stylesheets.
	$this->eso->addLanguage("Debug information", "Debug information");
	$this->eso->addLanguage("Page loaded in", "Page loaded in just over <strong><span id='debugLoadTime'>%s</span> seconds</strong>");
	$this->eso->addLanguage("MySQL queries", "MySQL queries");
	$this->eso->addLanguage("POST + GET + FILES information", "POST + GET + FILES information");
	$this->eso->addLanguage("SESSION + COOKIE information", "SESSION + COOKIE information");
	$this->eso->addLanguage("Hooked functions", "Hooked functions");
	$this->eso->addLanguage("Update debug information for background AJAX requests", "Update debug information for background AJAX requests");
	$this->eso->addScript("plugins/Debug/debug.js", 1000);
	$this->eso->addCSS("plugins/Debug/debug.css");
	
	// Add a hook to the bottom of the page, where we'll output the debug information!
	$this->eso->addHook("pageEnd", array($this, "renderDebug"));
}

// Plugin settings: whether or not to show debug information to non-administrators.
function settings()
{
	global $config, $language;
	
	// Add language definitions.
	$this->eso->addLanguage("Show debug information to non-administrators", "Show debug information to non-administrators");

	// Generate settings panel HTML.
	$settingsHTML = "<ul class='form'>
 	<li><label for='Debug_showToNonAdmins' class='checkbox'>{$language["Show debug information to non-administrators"]}</label> <input id='Debug_showToNonAdmins' name='Debug[showToNonAdmins]' type='checkbox' class='checkbox' value='1' " . ($config["Debug"]["showToNonAdmins"] ? "checked='checked'" : "") . "/></li>
	<li><label></label> " . $this->eso->skin->button(array("value" => $language["Save changes"], "name" => "saveSettings")) . "</li>
	</ul>";
	
	return $settingsHTML;
}

// Save the plugin settings.
function saveSettings()
{
	global $config;
	$config["Debug"]["showToNonAdmins"] = (bool)@$_POST["Debug"]["showToNonAdmins"];
	writeConfigFile("config/Debug.php", '$config["Debug"]', $config["Debug"]);
	$this->eso->message("changesSaved");
}

// Add the debug information to the JSON array which is returned from an AJAX request.
function addInformationToAjaxResult($eso, &$result)
{
	global $config, $language;
	
	// Don't proceed if the user is not permitted to see the debug information!
	if (empty($eso->user["admin"]) and !$config["Debug"]["showToNonAdmins"]) return;
	
	// Add the debug information to the $result array.
	$result["queries"] = "";
	foreach ($_SESSION["queries"] as $query)
		$result["queries"] .= "<li>" . sanitize($query[0]) . " <small>(" . $query[1] . " {$language["seconds"]})</small></li>";
	$end = $this->microtimeFloat();
	$time = round($end - $this->start, 4);
	$result["queriesCount"] = count($_SESSION["queries"]);
	$result["loadTime"] = $time;
	$result["debugPost"] = sanitize(print_r($_POST, true));
	$result["debugGet"] = sanitize(print_r($_GET, true));
	$result["debugFiles"] = sanitize(print_r($_FILES, true));
	$result["debugSession"] = sanitize(print_r($_SESSION, true));
	$result["debugCookie"] = sanitize(print_r($_COOKIE, true));
	$result["log"] = sanitize($this->log);
	$result["hookedFunctions"] = $this->getHookedFunctions();
	$_SESSION["queries"] = [];
}

function microtimeFloat()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

// Start the query timer so we can work out how long it took when it finished.
function startQueryTimer($eso, $query)
{
	$this->queryTimer = $this->microtimeFloat();
}

// Work out how long the query took to run and add it to the log.
function addQuery($eso, $query)
{
	$_SESSION["queries"][] = array($query, round($this->microtimeFloat() - $this->queryTimer, 4));
}

// Add something to the AJAX log.
function log()
{
	$args = func_get_args();
	foreach ($args as $arg) {
		if (is_array($arg)) $log = print_r($arg, true);
		else $log = (string)$arg;
		$this->log .= "$log\n";
	}
}

// Render the debug box at the bottom of the page.
function renderDebug($eso)
{
	global $config, $language;
	
	// Don't proceed if the user is not permitted to see the debug information.	
	if (empty($eso->user["admin"]) and !$config["Debug"]["showToNonAdmins"]) return;
	
	// Stop the page loading timer.
	$end = $this->microtimeFloat();
	$time = round($end - $this->start, 4);
		
	echo "<div id='debug'><h2>
<div id='debugHdr'>{$language["Debug information"]} <small>" . sprintf($language["Page loaded in"], $time) . "</small></div>
<div id='debugSetting'><small><input type='checkbox' class='checkbox' id='debugUpdateBackground' value='1' checked='checked' onchange='Ajax.debugUpdateBackground=this.checked'/> <label for='debugUpdateBackground' class='checkbox'>{$language["Update debug information for background AJAX requests"]}</label></small></div>
</h2>";
	
	// MySQL queries.
	echo "<h3><a href='#' onclick='toggle(document.getElementById(\"debugQueries\"), {animation:\"verticalSlide\"});return false'>{$language["MySQL queries"]} (<span id='debugQueriesCount'>" . count($_SESSION["queries"]) . "</span>)</a></h3>
	<ul id='debugQueries' class='fixed'>";
	if (!count($_SESSION["queries"])) echo "<li></li>";
	else foreach ($_SESSION["queries"] as $query) echo "<li>" . sanitizeHTML($query[0]) . " <small>(" . $query[1] . " {$language["seconds"]})</small></li>";
	$_SESSION["queries"] = [];
	
	// POST + GET + FILES information.
	echo "</ul>
	<h3><a href='#' onclick='toggle(document.getElementById(\"debugPostGetFiles\"), {animation:\"verticalSlide\"});return false'>{$language["POST + GET + FILES information"]}</a></h3>
	<div id='debugPostGetFiles'>
	<p style='white-space:pre' class='fixed' id='debugPost'>\$_POST = ";
	echo sanitizeHTML(print_r($_POST, true));
	echo "</p><p style='white-space:pre' class='fixed' id='debugGet'>\$_GET = ";
	echo sanitizeHTML(print_r($_GET, true));
	echo "</p><p style='white-space:pre' class='fixed' id='debugFiles'>\$_FILES = ";
	echo sanitizeHTML(print_r($_FILES, true));
	echo "</p>
	</div>";
	
	// SESSION + COOKIE information.
	echo "<h3><a href='#' onclick='toggle(document.getElementById(\"debugSessionCookie\"), {animation:\"verticalSlide\"});return false'>{$language["SESSION + COOKIE information"]}</a></h3>
	<div id='debugSessionCookie'><p style='white-space:pre' class='fixed' id='debugSession'>\$_SESSION = ";
	echo sanitizeHTML(print_r($_SESSION, true));
	echo "</p><p style='white-space:pre' class='fixed' id='debugCookie'>\$_COOKIE = ";
	echo sanitizeHTML(print_r($_COOKIE, true));
	echo "</p></div>";
	
	// Hooked functions.
	echo "<h3><a href='#' onclick='toggle(getById(\"debugHooks\"), {animation:\"verticalSlide\"});return false'>{$language["Hooked functions"]}</a></h3>
	<ul id='debugHooks' class='fixed'>" . $this->getHookedFunctions() . "</ul>
	</div>";
	
	// Hide all panels by default.
	echo "<script type='text/javascript'>
	// <![CDATA[
	hide(document.getElementById(\"debugQueries\")); hide(document.getElementById(\"debugPostGetFiles\")); hide(document.getElementById(\"debugSessionCookie\"));
	// ]]>
	</script>";
}

function getHookedFunctions()
{
	$html = "";
	$classes = array(&$this->eso, &$this->eso->controller);
	foreach ($this->eso->plugins as $plugin) $classes[] =& $plugin;
	foreach ($classes as $class) {
		foreach ($class->hookedFunctions as $hook => $functions) {
			$html .= "<li>" . get_class($class) . " class: <strong>$hook</strong><br/>";
			foreach ($functions as $function) $html .= "  " . get_class($function[0]) . "->{$function[1]}";
			$html .= "</li>";
		}
	}
	return $html ? $html : "<li></li>";
}
	
}

