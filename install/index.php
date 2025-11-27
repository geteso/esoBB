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
 * Installer wrapper: sets up the Install controller and displays the
 * installer interface.
 */
define("IN_ESO", 1);

// Unset the page execution time limit.
@set_time_limit(0);

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

// Require essential files.
require PATH_LIBRARY."/functions.php";
require PATH_LIBRARY."/classes.php";
require PATH_LIBRARY."/database.php";

// Define the session save path.
session_save_path(PATH_ROOT."/sessions");
ini_set('session.gc_probability', 1);

// Start a session if one does not already exist.
if (!session_id()) session_start();

// Initialize CSRF token if not exists.
if (empty($_SESSION["token"])) {
	regenerateToken();
}

// Load language file early.
// Get available languages from ../languages/ folder.
$availableLanguages = array();
if ($handle = opendir("../languages")) {
	while (false !== ($v = readdir($handle))) {
		if (!in_array($v, array(".", "..")) and substr($v, -4) == ".php" and $v[0] != ".") {
			$v = substr($v, 0, strrpos($v, "."));
			$availableLanguages[] = $v;
		}
	}
	closedir($handle);
}

// Determine selected language from session or default to "English (casual)".
$installLanguage = (!empty($_SESSION["installLanguage"]) and in_array($_SESSION["installLanguage"], $availableLanguages)) 
	? $_SESSION["installLanguage"] 
	: "English (casual)";

// Sanitize language name using sanitizeFileName().
$installLanguage = sanitizeFileName($installLanguage);

// Load language file.
if (file_exists("../languages/{$installLanguage}.php")) {
	include "../languages/{$installLanguage}.php";
} else {
	// Fallback to "English (casual)" if file doesn't exist.
	$installLanguage = "English (casual)";
	include "../languages/{$installLanguage}.php";
}

// Sanitize the request data using sanitize().
$_POST = sanitize($_POST);
$_GET = sanitize($_GET);
$_COOKIE = sanitize($_COOKIE);

// Set up the Install controller, which will perform all installation tasks.
require "install.controller.php";
$install = new Install();
$install->init();

?>
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
<title>esoBB installer</title>
<link rel="icon" type="image/ico" href="../install/favicon.ico">
<link rel="shortcut icon" type="image/ico" href="../install/favicon.ico">
<script type='text/javascript' src='../js/eso.js'></script>
<script type='text/javascript'>
// <![CDATA[
// Calculate baseURL for install context
var baseURL = window.location.origin;
var pathname = window.location.pathname;
// Find /install and get everything before it, plus the root slash
var installPos = pathname.indexOf('/install');
if (installPos !== -1) {
	// Get the path up to and including the root, then add the install directory
	baseURL += pathname.substring(0, installPos + 1);
} else {
	// Fallback: just use root
	baseURL += '/';
}

// Initialize eso object for install context
var eso = {
	user: false,
	token: '<?php echo $_SESSION["token"]; ?>',
	baseURL: baseURL,
	language: {
		"ajaxDisconnected": "<?php echo addslashes($language["ajaxDisconnected"]); ?>",
		"ajaxRequestPending": "<?php echo addslashes($language["ajaxRequestPending"]); ?>"
	}
};

// Define IE variables (normally set by conditional comments in main app, but install page doesn't have them)
var isIE6 = false;
var isIE7 = false;

// Fix URL construction for install context
// The Form validation tries to use eso.baseURL + "ajax.php?controller=install"
// but install uses "install/ajax.php" instead
// Also fix relative URLs that start with "install/" to be absolute
var originalAjaxRequest = Ajax.request;
Ajax.request = function(request) {
	if (request && request.url) {
		// Fix Form validation URLs
		if (request.url.indexOf("ajax.php?controller=install") !== -1) {
			request.url = "/install/ajax.php";
		}
		// Fix relative URLs starting with "install/" to be absolute from root
		if (request.url.indexOf("install/") === 0) {
			// Make it absolute from the document root
			request.url = "/" + request.url;
		}
	}
	return originalAjaxRequest.call(this, request);
};

// Initialize Messages system for install context (handle missing container gracefully)
// Override Messages.init to create container if it doesn't exist
if (typeof Messages !== 'undefined') {
	var originalMessagesInit = Messages.init;
	Messages.init = function() {
		this.container = getById("messages");
		if (!this.container && document.body) {
			// Create a messages container if it doesn't exist
			this.container = document.createElement("div");
			this.container.id = "messages";
			this.container.style.display = "none";
			document.body.insertBefore(this.container, document.body.firstChild);
		}
		return originalMessagesInit.call(this);
	};
	// Initialize Messages when DOM is ready
	if (document.readyState === 'complete' || document.readyState === 'interactive') {
		setTimeout(function() { Messages.init(); }, 0);
	} else {
		document.addEventListener("DOMContentLoaded", function() { Messages.init(); });
	}
}
// ]]>
</script>
<script type='text/javascript' src='install.js'></script>
<link type='text/css' rel='stylesheet' href='install.css'/>
</head>

<body>
<div id='loading' style='display:none'><?php echo $language["Loading"];?></div>
	
<form action='' method='post'>
<input type='hidden' name='token' value='<?php echo $_SESSION["token"];?>'/>
<div id='container'>

<?php

switch ($install->step) {


// Start step - language selection.
case "start": ?>
<h1><img src='logo.svg' alt='esoBB logo'/><?php echo $language["install"]["welcomeTitle"]; ?></h1>
<div class='progress'><span class='step active'></span><span class='step'></span><span class='step'></span></div>
<p class='lead'><?php echo $language["install"]["welcomeDescription"]; ?></p>

<ul class='form'>
<li><label><?php echo $language["install"]["selectLanguage"]; ?></label> <div><select id='installLanguage' name='language' onchange='Install.changeLanguage(this.value)'>
<?php 
if (!empty($install->languages)) {
	foreach ($install->languages as $lang) echo "<option value='$lang'" . ((!empty($_SESSION["installLanguage"]) ? $_SESSION["installLanguage"] : "English (casual)") == $lang ? " selected='selected'" : "") . ">$lang</option>"; 
} else {
	echo "<option value='English (casual)'>English (casual)</option>";
}
?>
</select></div></li>
</ul>

<p id='footer'><input class='button' value='<?php echo $language["install"]["nextStep"]; ?> &#155;' type='submit' name='next'/></p>
<hr class='separator'/>
<p id='version'>esoBB version <?php echo $install->getVersion(); ?></p>
<?php break;


// Fatal checks.
case "fatalChecks": ?>
<h1><img src='logo.svg' alt='esoBB logo'/><?php echo $language["install"]["uhOh"]; ?></h1>
<div class='progress'><span class='step active'></span><span class='step'></span><span class='step'></span></div>
<p><?php echo $language["install"]["errorsMustResolve"]; ?></p>
<ul>
<?php foreach ($install->errors as $error) echo "<li>$error</li>"; ?>
</ul>
<p><?php echo $language["install"]["getHelp"]; ?></p>
<p id='footer'><input class='button' value='<?php echo $language["install"]["tryAgain"]; ?>' type='submit'/></p>
<hr class='separator'/>
<p id='version'>esoBB version <?php echo $install->getVersion(); ?></p>
<?php break;


// Warning checks.
case "warningChecks": ?>
<h1><img src='logo.svg' alt='esoBB logo'/><?php echo $language["install"]["warning"]; ?></h1>
<div class='progress'><span class='step active'></span><span class='step'></span><span class='step'></span></div>
<p><?php echo $language["install"]["errorsCanContinue"]; ?></p>
<ul>
<?php foreach ($install->errors as $error) echo "<li>$error</li>"; ?>
</ul>
<p><?php echo $language["install"]["getHelp"]; ?></p>
<p id='footer'><input class='button' value='<?php echo $language["install"]["nextStep"]; ?> &#155;' type='submit' name='next'/></p>
<hr class='separator'/>
<p id='version'>esoBB version <?php echo $install->getVersion(); ?></p>
<?php break;


// Specify setup information.
case "info": ?>
<h1><img src='logo.svg' alt='esoBB logo'/><?php echo $language["install"]["specifySetupInfo"]; ?></h1>
<div class='progress'><span class='step'></span><span class='step active'></span><span class='step'></span></div>
<p class='lead'><?php echo $language["install"]["setupInfoDescription"]; ?></p>

<fieldset id='basicDetails'><legend><?php echo $language["install"]["specifyBasicDetails"]; ?></legend>
<ul class='form'>
<li><label><?php echo $language["install"]["forumTitle"]; ?></label> <input id='forumTitle' name='forumTitle' tabindex='1' type='text' class='text' placeholder="e.g. Simon's Krav Maga Forum" value='<?php echo @$_POST["forumTitle"]; ?>'/>
<div id='forumTitle-message'></div>
<?php if (isset($install->errors["forumTitle"])): ?><div class='warning msg'><?php echo $install->errors["forumTitle"]; ?></div><?php endif; ?></li>

<li><label><?php echo $language["install"]["forumDescription"]; ?></label> <input id='forumDescription' name='forumDescription' tabindex='2' type='text' class='text' placeholder="e.g. Learn about Krav Maga." value='<?php echo @$_POST["forumDescription"]; ?>'/>
<div id='forumDescription-message'></div>
<?php if (isset($install->errors["forumDescription"])): ?><div class='warning msg'><?php echo $install->errors["forumDescription"]; ?></div><?php endif; ?></li>

<li><label><?php echo $language["install"]["defaultLanguage"]; ?></label> <div><select id='language' name='language' tabindex='3'>
<?php foreach ($install->languages as $lang) echo "<option value='$lang'" . ((!empty($_POST["language"]) ? $_POST["language"] : "English (casual)") == $lang ? " selected='selected'" : "") . ">$lang</option>"; ?>
</select><br/>
<small><?php echo $language["install"]["moreLanguagePacks"]; ?></small></div></li>
</ul>
</fieldset>

<fieldset id='mysqlConfig'><legend><?php echo $language["install"]["configureDatabase"]; ?></legend>
<p><?php echo $language["install"]["databaseDescription"]; ?></p>

<?php if (isset($install->errors["mysql"])): ?><div class='warning msg'><?php echo $install->errors["mysql"]; ?></div><?php endif; ?>

<ul class='form'>
<li><label><?php echo $language["install"]["mysqlHostAddress"]; ?></label> <input id='mysqlHost' name='mysqlHost' tabindex='4' type='text' class='text' autocomplete='off' value='<?php echo isset($_POST["mysqlHost"]) ? $_POST["mysqlHost"] : "localhost"; ?>'/>
<div id='mysqlHost-message'></div></li>

<li><label><?php echo $language["install"]["mysqlUsername"]; ?></label> <input id='mysqlUser' name='mysqlUser' tabindex='5' type='text' class='text' placeholder='esoman' autocomplete='off' value='<?php echo @$_POST["mysqlUser"]; ?>'/>
<div id='mysqlUser-message'></div></li>

<li><label><?php echo $language["install"]["mysqlPassword"]; ?></label> <input id='mysqlPass' name='mysqlPass' tabindex='6' type='password' class='text' autocomplete='off' value='<?php echo @$_POST["mysqlPass"]; ?>'/>
<div id='mysqlPass-message'></div></li>

<li><label><?php echo $language["install"]["mysqlDatabase"]; ?></label> <input id='mysqlDB' name='mysqlDB' tabindex='7' type='text' class='text' placeholder='esodb' autocomplete='off' value='<?php echo @$_POST["mysqlDB"]; ?>'/>
<div id='mysqlDB-message'></div></li>
</ul>
</fieldset>

<fieldset id='emailConfig'><legend><?php echo $language["install"]["outgoingMailServer"]; ?></legend>
<p><?php echo $language["install"]["mailServerDescription"]; ?></p>

<ul class='form'>
<li><label><?php echo $language["install"]["sendEmails"]; ?></label> <input name='sendEmail' type='checkbox' tabindex='8' class='checkbox' value='1' <?php echo (!empty($_POST["sendEmail"])) ? "checked" : ""; ?>/>
<!-- <small>If you leave this disabled, the SMTP configuration will be ignored.</small> -->
</li>
</ul>

<a href='#smtpConfig' onclick='toggleSmtpConfig();return false' title='What, you&#39;re too cool for the normal settings?' tabindex='9'><?php echo $language["install"]["smtpMailServer"]; ?></a>
<div id='smtpConfig'>

<ul class='form'>
<li><label><?php echo $language["install"]["smtpAuthentication"]; ?></label><div><select id='smtpAuth' name='smtpAuth' tabindex='10'>
<?php foreach ($install->smtpOptions as $k => $v) echo "<option value='$k'" . ((!empty($_POST["smtpAuth"]) ? $_POST["smtpAuth"] : "0") == $k ? " selected='selected'" : "") . ">$v</option>"; ?>
</select></div></li>

<li><label><?php echo $language["install"]["smtpHostAddress"]; ?></label> <input id='smtpHost' name='smtpHost' tabindex='11' type='text' class='text' autocomplete='off' value='<?php echo @$_POST["smtpHost"]; ?>'/></li>

<li><label><?php echo $language["install"]["smtpHostPort"]; ?></label> <input id='smtpPort' name='smtpPort' tabindex='12' type='text' class='text' placeholder='25' autocomplete='off' value='<?php echo @$_POST["smtpPort"]; ?>'/>
<div id='smtpPort-message'></div></li>

<li><label><?php echo $language["install"]["smtpUsername"]; ?></label> <input id='smtpUser' name='smtpUser' tabindex='13' type='text' class='text' placeholder='simon@example.com' autocomplete='off' value='<?php echo @$_POST["smtpUser"]; ?>'/></li>

<li><label><?php echo $language["install"]["smtpPassword"]; ?></label> <input id='smtpPass' name='smtpPass' tabindex='14' type='password' class='text' autocomplete='off' value='<?php echo @$_POST["smtpPass"]; ?>'/></li>
</ul>

<input type='hidden' name='showSmtpConfig' id='showSmtpConfig' value='<?php echo @$_POST["showSmtpConfig"]; ?>'/>
<script type='text/javascript'>
// <![CDATA[
function toggleSmtpConfig() {
	toggle(document.getElementById("smtpConfig"), {animation: "verticalSlide"});
	document.getElementById("showSmtpConfig").value = document.getElementById("smtpConfig").showing ? "1" : "";
	if (document.getElementById("smtpConfig").showing) {
		animateScroll(document.getElementById("smtpConfig").offsetTop + document.getElementById("smtpConfig").offsetHeight + getClientDimensions()[1]);
//		document.getElementById("smtpAuth").focus();
	}
}
<?php if (empty($_POST["showSmtpConfig"])): ?>hide(document.getElementById("smtpConfig"));<?php endif; ?>
// ]]>
</script>
</div>
</fieldset>

<fieldset id='adminConfig'><legend><?php echo $language["install"]["administratorAccount"]; ?></legend>
<p><?php echo $language["install"]["administratorDescription"]; ?></p>

<ul class='form'>
<li><label><?php echo $language["install"]["administratorUsername"]; ?></label> <input id='adminUser' name='adminUser' tabindex='15' type='text' class='text' placeholder='Simon' autocomplete='username' value='<?php echo @$_POST["adminUser"]; ?>'/>
<div id='adminUser-message'></div>
<?php if (isset($install->errors["adminUser"])): ?><div class='warning msg'><?php echo $install->errors["adminUser"]; ?></div><?php endif; ?></li>
	
<li><label><?php echo $language["install"]["administratorEmail"]; ?></label> <input id='adminEmail' name='adminEmail' tabindex='16' type='text' class='text' placeholder='simon@example.com' autocomplete='email' value='<?php echo @$_POST["adminEmail"]; ?>'/>
<div id='adminEmail-message'></div>
<?php if (isset($install->errors["adminEmail"])): ?><span class='warning msg'><?php echo $install->errors["adminEmail"]; ?></span><?php endif; ?></li>
	
<li><label><?php echo $language["install"]["administratorPassword"]; ?></label> <input id='adminPass' name='adminPass' tabindex='17' type='password' class='text' autocomplete='new-password' value='<?php echo @$_POST["adminPass"]; ?>'/>
<div id='adminPass-message'></div>
<?php if (isset($install->errors["adminPass"])): ?><span class='warning msg'><?php echo $install->errors["adminPass"]; ?></span><?php endif; ?></li>
	
<li><label><?php echo $language["install"]["confirmPassword"]; ?></label> <input id='adminConfirm' name='adminConfirm' tabindex='18' type='password' class='text' autocomplete='off' value='<?php echo @$_POST["adminConfirm"]; ?>'/>
<div id='adminConfirm-message'></div>
<?php if (isset($install->errors["adminConfirm"])): ?><span class='warning msg'><?php echo $install->errors["adminConfirm"]; ?></span><?php endif; ?></li>
</ul>
</fieldset>

<fieldset id='advancedOptions'>
<legend><a href='#' onclick='Settings.toggleFieldset("advancedOptions");return false' title='What, you&#39;re too cool for the normal settings?' tabindex='19'><?php echo $language["install"]["advancedOptions"]; ?></a></legend>

<?php if (isset($install->errors["tablePrefix"])): ?><p class='warning msg'><?php echo $install->errors["tablePrefix"]; ?></p><?php endif; ?>

<ul class='form' id='advancedOptionsForm'>
<li><label><?php echo $language["install"]["mysqlTablePrefix"]; ?></label> <input name='tablePrefix' id='tablePrefix' tabindex='20' type='text' class='text' autocomplete='off' value='<?php echo isset($_POST["tablePrefix"]) ? $_POST["tablePrefix"] : "et_"; ?>'/>
<div id='tablePrefix-message'></div></li>

<li><label><?php echo $language["install"]["mysqlCharacterSet"]; ?></label> <input name='characterEncoding' id='characterEncoding' tabindex='21' type='text' class='text' autocomplete='off' value='<?php echo isset($_POST["characterEncoding"]) ? $_POST["characterEncoding"] : "utf8mb4"; ?>'/>
<div id='characterEncoding-message'></div></li>

<li><label><?php echo $language["install"]["mysqlStorageEngine"]; ?></label><div><select id='storageEngine' name='storageEngine' tabindex='22'>
<?php foreach ($install->storageEngines as $k => $v) echo "<option value='$k'" . ((!empty($_POST["storageEngine"]) ? $_POST["storageEngine"] : "InnoDB") == $k ? " selected='selected'" : "") . ">$v</option>"; ?>
</select></div></li>

<li><label><?php echo $language["install"]["hashingAlgorithm"]; ?></label><div><select id='hashingMethod' name='hashingMethod' tabindex='23'>
<?php foreach ($install->hashingMethods as $k => $v) echo "<option value='$k'" . ((!empty($_POST["hashingMethod"]) ? $_POST["hashingMethod"] : "bcrypt") == $k ? " selected='selected'" : "") . ">$v</option>"; ?>
</select></div></li>

<li><label><?php echo $language["install"]["baseURL"]; ?></label> <input name='baseURL' id='baseURL' type='text' tabindex='24' class='text' autocomplete='off' value='<?php echo isset($_POST["baseURL"]) ? $_POST["baseURL"] : $install->suggestBaseUrl(); ?>'/>
<div id='baseURL-message'></div></li>

<li><label><?php echo $language["install"]["useFriendlyURLs"]; ?></label> <input name='friendlyURLs' type='checkbox' tabindex='25' class='checkbox' value='1' <?php echo (!empty($_POST["friendlyURLs"]) or $install->suggestFriendlyUrls()) ? "checked" : ""; ?>/></li>
</ul>
</fieldset>
<script type='text/javascript'>Settings.hideFieldset("advancedOptions")</script>

<p id='footer' style='margin:0'><input type='submit' class='button' value='&#139; <?php echo $language["install"]["goBack"]; ?>' name='back'/> <input type='submit' id='installSubmit' tabindex='26' value='<?php echo $language["install"]["nextStep"]; ?> &#155;' class='button'/></p>
<script type='text/javascript'>
// <![CDATA[
Install.fieldsValidated = {
	'forumTitle': false,
	'forumDescription': false,
	'adminUser': false,
	'adminEmail': false,
	'adminPass': false,
	'adminConfirm': false,
	'mysqlHost': false,
	'mysqlUser': false,
	'mysqlPass': false,
	'mysqlDB': false
};
Install.init();
// ]]>
</script>
<hr class='separator'/>
<p id='version'>esoBB version <?php echo $install->getVersion(); ?></p>
<?php break;


// Show an installation error.
case "install": ?>
<h1><img src='logo.svg' alt='esoBB logo'/><?php echo $language["install"]["fatalErrorTitle"]; ?></h1>
<div class='progress'><span class='step'></span><span class='step active'></span><span class='step'></span></div>
<p class='warning msg'><?php echo $language["install"]["installerEncounteredError"]; ?></p>
<p><?php echo $language["install"]["fatalErrorDescription"]; ?></p>
<ul>
<li><?php echo $language["install"]["tryAgainTip"]; ?></li>
<li><?php echo $language["install"]["checkSettingsTip"]; ?></li>
<li><?php echo $language["install"]["getHelpTip"]; ?></li>
</ul>

<a href='#' onclick='toggleError();return false'><?php echo $language["install"]["showErrorInformation"]; ?></a>
<hr class='aboveToggle'/>
<div id='error'>
<?php echo $install->errors[1]; ?>
</div>
<script type='text/javascript'>
// <![CDATA[
function toggleError() {
	toggle(document.getElementById("error"), {animation: "verticalSlide"});
}
hide(document.getElementById("error"));
// ]]>
</script>
<p id='footer' style='margin:0'>
<input type='submit' class='button' value='&#139; <?php echo $language["install"]["goBack"]; ?>' name='back'/>
<input type='submit' class='button' value='<?php echo $language["install"]["tryAgain"]; ?>'/>
</p>
<hr class='separator'/>
<p id='version'>esoBB version <?php echo $install->getVersion(); ?></p>
<?php break;


// Finish!
case "finish": ?>
<h1><img src='logo.svg' alt='esoBB logo'/><?php echo $language["install"]["congratulations"]; ?></h1>
<div class='progress'><span class='step'></span><span class='step'></span><span class='step active'></span></div>
<p class='lead'><?php echo $language["install"]["forumInstalled"]; ?></p>

<a href='javascript:toggleAdvanced()'><?php echo $language["install"]["showAdvancedInformation"]; ?></a>
<hr class='aboveToggle'/>
<div id='advanced'>
<strong><?php echo $language["install"]["queriesRun"]; ?></strong>
<pre>
<?php if (isset($_SESSION["queries"]) and is_array($_SESSION["queries"]))
	foreach ($_SESSION["queries"] as $query) echo sanitizeHTML($query) . ";<br/><br/>"; ?>
</pre>
</div>
<script type='text/javascript'>
// <![CDATA[
function toggleAdvanced() {
	toggle(document.getElementById("advanced"), {animation: "verticalSlide"});
}
hide(document.getElementById("advanced"));
// ]]>
</script>
<p style='text-align:center' id='footer'><input type='submit' class='button' value='<?php echo $language["install"]["takeMeToForum"]; ?>' name='finish'/></p>
<hr class='separator'/>
<p id='version'>esoBB version <?php echo $install->getVersion(); ?></p>
<?php break;

}
?>

</div>
</form>

</body>
</html>
