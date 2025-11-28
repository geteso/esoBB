<?php
declare(strict_types=1);
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
 * Classes: this file contains all the base classes which are extended
 * throughout the software...
 */
if (!defined("IN_ESO")) exit;

// Hookable => a class in which code can be hooked on to.
// Extend this class and then use $this->callHook("uniqueMarker") in the class code to call any code which has been hooked via $classInstance->addHook("uniqueMarker", "function").
class Hookable {

public array $hookedFunctions = [];

// Run all collective hooked functions for the specified marker.
public function callHook(string $marker, array|null $parameters = [], bool $return = false): mixed
{
	if (isset($this->hookedFunctions[$marker]) && count($this->hookedFunctions[$marker])) {
		
		// Add the instance of this class to the parameters.
		// We can't use array_unshift here because call-time pass-by-reference has been deprecated.
		$parameters = is_array($parameters) ? array_merge([&$this], $parameters) : [&$this];
		
		// Loop through the functions which have been hooked on this hook and execute them.
		// If this hook requires a return value and the function we're running returns something, return that.
		foreach ($this->hookedFunctions[$marker] as $function) {
			if (($returned = call_user_func_array($function, $parameters)) && $return) return $returned;
		}
	}
	return null;
}

// Hook a function to a specific marker.
public function addHook(string $hook, callable $function): void
{
	$this->hookedFunctions[$hook][] = $function;
}

}


// Defines a view and handles input.
// Extend this class and then use $eso->registerController() to register your new controller.
class Controller extends Hookable {

public ?string $action = null;
public ?string $view = null;
public ?string $title = null;
public ?object $eso = null;

public function init() {}
public function ajax() {}

// Render the page according to the controller's $view.
public function render(): void
{
	global $language, $messages, $config;
	include $this->eso->skin->getView($this->view);
}

}

// Defines a plugin.
// Extend this class to make a plugin. See the plugin documentation for more information.
class Plugin extends Hookable {

public ?string $id = null;
public ?string $name = null;
public ?string $version = null;
public ?string $author = null;
public ?string $description = null;
public ?array $defaultConfig = null;
public ?object $eso = null;

// Constructor: include the config file or write the default config if it doesn't exist.
public function __construct()
{
	if (!empty($this->defaultConfig)) {
		global $config;
		$filename = sanitizeFileName($this->id);
		if (!file_exists("config/$filename.php")) writeConfigFile("config/$filename.php", '$config["' . escapeDoubleQuotes($this->id) . '"]', $this->defaultConfig);
		include "config/$filename.php";
	}
}

// For automatic version checking, call this function (parent::init()) at the beginning of a plugin's init() function.
public function init()
{
	// Compare the version of the code ($this->version) to the installed one (config/versions.php).
	// If it's different, run the upgrade() function, and write the new version number to config/versions.php.
	global $versions;
	if (!isset($versions[$this->id]) || $versions[$this->id] != $this->version) {
		$this->upgrade($versions[$this->id] ?? null);
		$versions[$this->id] = $this->version;
		writeConfigFile("config/versions.php", '$versions', $versions);	
	}
}

public function settings() {}
public function saveSettings() {}
public function upgrade($oldVersion) {}
public function enable() {}

}

// Defines a skin.
// Extend this class to make a skin.
class Skin {

public ?string $name = null;
public ?string $version = null;
public ?string $author = null;
public array $views = [];
public ?object $eso = null;

public function init() {}

// Generate button HTML.
public function button($attributes)
{
	$attr = " type='submit'";
	foreach ($attributes as $k => $v) $attr .= " $k='$v'";
	return "<input$attr/>";
}

// Register a custom view.
// Whenever a controller attempts to include $view, this new $file associated with $view will be included instead.
public function registerView(string $view, string $file): void
{
	$this->views[$view] = $file;
}

// Get the path to a view file, using a custom view if registered.
public function getView(string $view): string
{
	return $this->views[$view] ?? "views/$view";
}

// Get the path to the forum logo.
public function getForumLogo(): string
{
	global $config;
	$logo = "";
	if (isset($this->eso->skin->logo) && file_exists("skins/{$config["skin"]}/" . $this->eso->skin->logo)) $logo = $this->eso->skin->logo;
	elseif (file_exists("skins/{$config["skin"]}/logo.svg")) $logo = "logo.svg";
	return !empty($config["forumLogo"]) ? $config["forumLogo"] : "skins/{$config["skin"]}/" . $logo;
}

// Get the path to the forum icon.
public function getForumIcon(): string
{
	global $config;
	$icon = "";
	if (isset($this->eso->skin->icon) && file_exists("skins/{$config["skin"]}/" . $this->eso->skin->icon)) $icon = $this->eso->skin->icon;
	elseif (file_exists("skins/{$config["skin"]}/icon.png")) $icon = "icon.png";
	return !empty($config["forumIcon"]) ? $config["forumIcon"] : "skins/{$config["skin"]}/" . $icon;
}

}

?>
