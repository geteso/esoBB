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
 * Captcha plugin: provides image verification during the join process to
 * prevent bots from joining the forum.
 */
class Captcha extends Plugin {

public ?string $id = "Captcha";
public ?string $name = "Captcha";
public ?string $version = "1.0";
public ?string $description = "Provides image verification to prevent bots from joining";
public ?string $author = "the esoBB team";
public ?array $defaultConfig = [
	"numberOfCharacters" => 3
];

function init()
{
	parent::init();
	
	if ($this->checkEnvironment()) return;
	
	// Add language definitions and messages.
	$this->eso->addLanguage("Are you human", "Are you human?");
	$this->eso->addLanguage("Type the letters you see", "Type the letters you see in the image");
	$this->eso->addLanguage("Can't make it out", "Can't make it out? <a href='%s'>Try another one!</a>");
	$this->eso->addMessage("captchaError", "warning", "Oops, you got it wrong! Try again with this combination.");
	
	// Add a hook to the join controller so we can add captcha to the form.
	if ($this->eso->action == "join") $this->eso->controller->addHook("init", array($this, "initCaptchaForm"));

	// Add stylesheets.
	$this->eso->addCSS("plugins/Captcha/captcha.css");
}

// Check the server environment for errors.
function checkEnvironment()
{
	global $messages;
	$this->eso->addMessage("gdNotInstalled", "warning", "GD is not installed.");
	$this->eso->addMessage("gdNoSupportOpenType", "warning", "Your version of GD does not support OpenType font rendering.");
	
	// Check for the gd plugin and support for OpenType font rendering.
	if (!extension_loaded("gd") and !extension_loaded("gd2")) return "gdNotInstalled";
	if (!function_exists("imagettftext")) return "gdNoSupportOpenType";
}

// When enabling the plugin, return any server environment errors.
function enable()
{
	return $this->checkEnvironment();
}

// Add the captcha fieldset and input to the join form.
function initCaptchaForm(&$join)
{
	global $language;
	$join->addFieldset("areYouHuman", $language["Are you human"], 900);
	$join->addToForm("areYouHuman", array(
		"id" => "captcha",
		"html" => "<label style='width:20em'>{$language["Type the letters you see"]}<br/><small>" . sprintf($language["Can't make it out"], "javascript:document.getElementById(\"captchaImg\").src=document.getElementById(\"captchaImg\").src.split(\"?\")[0]+\"?\"+(new Date()).getTime();void(0)") . "</small></label> <div><input id='captcha' name='join[captcha]' type='text' class='text' tabindex='500'/><br/><img src='plugins/Captcha/captchaImg.php' style='margin-top:4px' id='captchaImg' alt='Captcha'/></div>",
		"validate" => array($this, "validateCaptcha"),
		"required" => true
	));
}

// Validate the captcha input.
function validateCaptcha($input)
{
	if ($_SESSION["captcha"] != md5($input) or !$input) return "captchaError";
}

// Plugin settings: captcha preview and how many characters to use.
function settings()
{
	global $config, $language;
	
	// If there's something wrong with the server environment, output an error.
	if ($msg = $this->checkEnvironment()) return $this->eso->htmlMessage($msg);
	
	// Add language definitions.
	$this->eso->addLanguage("Sample captcha image", "Sample captcha image");
	$this->eso->addLanguage("Show another one", "Show another one");
	$this->eso->addLanguage("Number of characters", "Number of characters");

	// Generate settings panel HTML.
	$settingsHTML = "<ul class='form'>
	<li><label>{$language["Sample captcha image"]}<br/><small><a href='javascript:document.getElementById(\"captchaImg\").src=document.getElementById(\"captchaImg\").src.split(\"?\")[0]+\"?\"+(new Date()).getTime();void(0)'>{$language["Show another one"]}</a></small></label> <div><img src='plugins/Captcha/captchaImg.php?" . time() . "' id='captchaImg' alt='Captcha'/></div></li>
	<li><label>{$language["Number of characters"]}</label> <input name='Captcha[numberOfCharacters]' type='text' class='text' value='{$config["Captcha"]["numberOfCharacters"]}'/></li>
	<li><label></label> " . $this->eso->skin->button(array("value" => $language["Save changes"], "name" => "saveSettings")) . "</li>
	</ul>";
	
	return $settingsHTML;
}

// Save the plugin settings.
function saveSettings()
{
	global $config;
	$config["Captcha"]["numberOfCharacters"] = max(1, min(10, (int)$_POST["Captcha"]["numberOfCharacters"]));
	writeConfigFile("config/Captcha.php", '$config["Captcha"]', $config["Captcha"]);
	$this->eso->message("changesSaved");
}

}

?>
