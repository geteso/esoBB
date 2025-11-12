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

// Undo register_globals.
undoRegisterGlobals();

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
<title>esoInstaller</title>
<script type='text/javascript' src='../js/eso.js'></script>
<link type='text/css' rel='stylesheet' href='install.css'/>
</head>

<body>
	
<form action='' method='post'>
<div id='container'>

<?php

switch ($install->step) {


// Fatal checks.
case "fatalChecks": ?>
<h1><img src='logo.svg' data-fallback='logo.png' alt='Forum logo'/>Uh oh, something's not right!</h1>
<hr/>
<p>The following errors were found with your forum's setup. They must be resolved before you can continue the installation.</p>
<ul>
<?php foreach ($install->errors as $error) echo "<li>$error</li>"; ?>
</ul>
<p>If you run into any other problems or just want some help with the installation, feel free to join the <a href='https://forum.geteso.org'>esoBB support forum</a> where a bunch of friendly people will be happy to help you out.</p>
<p id='footer'><input class='button' value='Try again' type='submit'/></p>
<hr/>
<p id='version'>esoBB version <?php echo $install->getVersion(); ?></p>
<?php break;


// Warning checks.
case "warningChecks": ?>
<h1><img src='logo.svg' data-fallback='logo.png' alt='Forum logo'/>Warning!</h1>
<hr/>
<p>The following errors were found with your forum's setup. You can continue the installation without resolving them, but some functionality may be limited.</p>
<ul>
<?php foreach ($install->errors as $error) echo "<li>$error</li>"; ?>
</ul>
<p>If you run into any other problems or just want some help with the installation, feel free to join the <a href='https://forum.geteso.org'>esoBB support forum</a> where a bunch of friendly people will be happy to help you out.</p>
<p id='footer'><input class='button' value='Next step &#155;' type='submit' name='next'/></p>
<hr/>
<p id='version'>esoBB version <?php echo $install->getVersion(); ?></p>
<?php break;


// Specify setup information.
case "info": ?>
<h1><img src='logo.svg' alt=''/>Specify setup information</h1>
<p class='lead'>Welcome to the esoBB installer.  We need a few details from you so we can get your forum ready to go.
<br/>If you have any trouble, read the <a href='https://geteso.org/docs/setup'>setup guide</a> or get help on the <a href='https://forum.geteso.org'>esoBB support forum</a>.</p>

<fieldset id='basicDetails'><legend>Specify basic details</legend>
<ul class='form'>
<li><label>Forum title</label> <input id='forumTitle' name='forumTitle' tabindex='1' type='text' class='text' placeholder="e.g. Simon's Krav Maga Forum" value='<?php echo @$_POST["forumTitle"]; ?>'/>
<?php if (isset($install->errors["forumTitle"])): ?><div class='warning msg'><?php echo $install->errors["forumTitle"]; ?></div><?php endif; ?></li>

<li><label>Forum description</label> <input id='forumDescription' name='forumDescription' tabindex='2' type='text' class='text' placeholder="e.g. Learn about Krav Maga." value='<?php echo @$_POST["forumDescription"]; ?>'/>
<?php if (isset($install->errors["forumDescription"])): ?><div class='warning msg'><?php echo $install->errors["forumDescription"]; ?></div><?php endif; ?></li>

<li><label>Default language</label> <div><select id='language' name='language' tabindex='3'>
<?php foreach ($install->languages as $language) echo "<option value='$language'" . ((!empty($_POST["language"]) ? $_POST["language"] : "English (casual)") == $language ? " selected='selected'" : "") . ">$language</option>"; ?>
</select><br/>
<small>More language packs are <a href='https://geteso.org/languages'>available for download</a>.</small></div></li>
</ul>
</fieldset>

<fieldset id='mysqlConfig'><legend>Configure the database</legend>
<p>esoBB needs a database to store all your forum's data in, such as conversations and posts. If you're unsure of any of these details, you may need to ask your hosting provider.</p>

<?php if (isset($install->errors["mysql"])): ?><div class='warning msg'><?php echo $install->errors["mysql"]; ?></div><?php endif; ?>

<ul class='form'>
<li><label>MySQL host address</label> <input id='mysqlHost' name='mysqlHost' tabindex='4' type='text' class='text' autocomplete='off' value='<?php echo isset($_POST["mysqlHost"]) ? $_POST["mysqlHost"] : "localhost"; ?>'/></li>

<li><label>MySQL username</label> <input id='mysqlUser' name='mysqlUser' tabindex='5' type='text' class='text' placeholder='esoman' autocomplete='off' value='<?php echo @$_POST["mysqlUser"]; ?>'/></li>

<li><label>MySQL password</label> <input id='mysqlPass' name='mysqlPass' tabindex='6' type='password' class='text' autocomplete='off' value='<?php echo @$_POST["mysqlPass"]; ?>'/></li>

<li><label>MySQL database</label> <input id='mysqlDB' name='mysqlDB' tabindex='7' type='text' class='text' placeholder='esodb' autocomplete='off' value='<?php echo @$_POST["mysqlDB"]; ?>'/></li>
</ul>
</fieldset>

<fieldset id='emailConfig'><legend>Outgoing mail server</legend>
<p>esoBB needs a mail server to send emails to members, but your forum will work with email sending disabled. If you haven't configured server-side email sending or are unsure of whether you can send emails, leave this disabled and change it later.</p>

<ul class='form'>
<li><label>Send emails</label> <input name='sendEmail' type='checkbox' tabindex='8' class='checkbox' value='1' <?php echo (!empty($_POST["sendEmail"])) ? "checked" : ""; ?>/>
<!-- <small>If you leave this disabled, the SMTP configuration will be ignored.</small> -->
</li>
</ul>

<a href='#smtpConfig' onclick='toggleSmtpConfig();return false' title='What, you&#39;re too cool for the normal settings?' tabindex='9'>SMTP mail server (optional)</a>
<div id='smtpConfig'>

<ul class='form'>
<li><label>SMTP authentication</label><div><select id='smtpAuth' name='smtpAuth' tabindex='10'>
<?php foreach ($install->smtpOptions as $k => $v) echo "<option value='$k'" . ((!empty($_POST["smtpAuth"]) ? $_POST["smtpAuth"] : "0") == $k ? " selected='selected'" : "") . ">$v</option>"; ?>
</select></div></li>

<li><label>SMTP host address</label> <input id='smtpHost' name='smtpHost' tabindex='11' type='text' class='text' autocomplete='off' value='<?php echo @$_POST["smtpHost"]; ?>'/></li>

<li><label>SMTP host port</label> <input id='smtpPort' name='smtpPort' tabindex='12' type='text' class='text' placeholder='25' autocomplete='off' value='<?php echo @$_POST["smtpPort"]; ?>'/></li>

<li><label>SMTP username</label> <input id='smtpUser' name='smtpUser' tabindex='13' type='text' class='text' placeholder='simon@example.com' autocomplete='off' value='<?php echo @$_POST["smtpUser"]; ?>'/></li>

<li><label>SMTP password</label> <input id='smtpPass' name='smtpPass' tabindex='14' type='password' class='text' autocomplete='off' value='<?php echo @$_POST["smtpPass"]; ?>'/></li>
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

<fieldset id='adminConfig'><legend>Administrator account</legend>
<p>esoBB will use the following information to set up your administrator account on your forum.</p>

<ul class='form'>
<li><label>Administrator username</label> <input id='adminUser' name='adminUser' tabindex='15' type='text' class='text' placeholder='Simon' autocomplete='username' value='<?php echo @$_POST["adminUser"]; ?>'/>
<?php if (isset($install->errors["adminUser"])): ?><div class='warning msg'><?php echo $install->errors["adminUser"]; ?></div><?php endif; ?></li>
	
<li><label>Administrator email</label> <input id='adminEmail' name='adminEmail' tabindex='16' type='text' class='text' placeholder='simon@example.com' autocomplete='email' value='<?php echo @$_POST["adminEmail"]; ?>'/>
<?php if (isset($install->errors["adminEmail"])): ?><span class='warning msg'><?php echo $install->errors["adminEmail"]; ?></span><?php endif; ?></li>
	
<li><label>Administrator password</label> <input id='adminPass' name='adminPass' tabindex='17' type='password' class='text' autocomplete='new-password' value='<?php echo @$_POST["adminPass"]; ?>'/>
<?php if (isset($install->errors["adminPass"])): ?><span class='warning msg'><?php echo $install->errors["adminPass"]; ?></span><?php endif; ?></li>
	
<li><label>Confirm password</label> <input id='adminConfirm' name='adminConfirm' tabindex='18' type='password' class='text' autocomplete='off' value='<?php echo @$_POST["adminConfirm"]; ?>'/>
<?php if (isset($install->errors["adminConfirm"])): ?><span class='warning msg'><?php echo $install->errors["adminConfirm"]; ?></span><?php endif; ?></li>
</ul>
</fieldset>

<fieldset id='advancedOptions'>
<legend><a href='#' onclick='Settings.toggleFieldset("advancedOptions");return false' title='What, you&#39;re too cool for the normal settings?' tabindex='19'>Advanced options</a></legend>

<?php if (isset($install->errors["tablePrefix"])): ?><p class='warning msg'><?php echo $install->errors["tablePrefix"]; ?></p><?php endif; ?>

<ul class='form' id='advancedOptionsForm'>
<li><label>MySQL table prefix</label> <input name='tablePrefix' id='tablePrefix' tabindex='20' type='text' class='text' autocomplete='off' value='<?php echo isset($_POST["tablePrefix"]) ? $_POST["tablePrefix"] : "et_"; ?>'/></li>

<li><label>MySQL character set</label> <input name='characterEncoding' id='characterEncoding' tabindex='21' type='text' class='text' autocomplete='off' value='<?php echo isset($_POST["characterEncoding"]) ? $_POST["characterEncoding"] : "utf8mb4"; ?>'/></li>

<li><label>MySQL storage engine</label><div><select id='storageEngine' name='storageEngine' tabindex='22'>
<?php foreach ($install->storageEngines as $k => $v) echo "<option value='$k'" . ((!empty($_POST["storageEngine"]) ? $_POST["storageEngine"] : "InnoDB") == $k ? " selected='selected'" : "") . ">$v</option>"; ?>
</select></div></li>

<li><label>Hashing algorithm</label><div><select id='hashingMethod' name='hashingMethod' tabindex='23'>
<?php foreach ($install->hashingMethods as $k => $v) echo "<option value='$k'" . ((!empty($_POST["hashingMethod"]) ? $_POST["hashingMethod"] : "bcrypt") == $k ? " selected='selected'" : "") . ">$v</option>"; ?>
</select></div></li>

<li><label>Base URL</label> <input name='baseURL' type='text' tabindex='24' class='text' autocomplete='off' value='<?php echo isset($_POST["baseURL"]) ? $_POST["baseURL"] : $install->suggestBaseUrl(); ?>'/></li>

<li><label>Use friendly URLs</label> <input name='friendlyURLs' type='checkbox' tabindex='25' class='checkbox' value='1' <?php echo (!empty($_POST["friendlyURLs"]) or $install->suggestFriendlyUrls()) ? "checked" : ""; ?>/></li>
</ul>
</fieldset>
<script type='text/javascript'>Settings.hideFieldset("advancedOptions")</script>

<p id='footer' style='margin:0'><input type='submit' tabindex='26' value='Next step &#155;' class='button'/></p>
<hr class='separator'/>
<p id='version'>esoBB version <?php echo $install->getVersion(); ?></p>
<?php break;


// Show an installation error.
case "install": ?>
<h1><img src='logo.svg' alt=''/>Uh oh! It's a fatal error...</h1>
<hr class='separator'/>
<p class='warning msg'>The forum installer encountered an error.</p>
<p>The esoBB installer has encountered a nasty error which is making it impossible to install a forum on your server. But don't feel down, <strong>here are a few things you can try</strong>:</p>
<ul>
<li><strong>Try again.</strong> Everyone makes mistakes: maybe the computer made one this time.</li>
<li><strong>Go back and check your settings.</strong> In particular, make sure your database information is correct.</li>
<li><strong>Get help.</strong> Go on the <a href='https://forum.geteso.org'>esoBB support forum</a> to see if anyone else is having the same problem as you are. If not, open a new issue, including the error details below.</li>
</ul>

<a href='#' onclick='toggleError();return false'>Show error information</a>
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
<input type='submit' class='button' value='&#139; Go back' name='back'/>
<input type='submit' class='button' value='Try again'/>
</p>
<hr/>
<p id='version'>esoBB version <?php echo $install->getVersion(); ?></p>
<?php break;


// Finish!
case "finish": ?>
<h1><img src='logo.svg' alt=''/>Congratulations!</h1>
<hr class='separator'/>
<p>Your forum has been installed, and it should be ready to go.</p>
<p>It's highly recommended that you <strong>remove the <code>install</code> folder</strong> to secure your forum.</p>

<a href='javascript:toggleAdvanced()'>Show advanced information</a>
<hr class='aboveToggle'/>
<div id='advanced'>
<strong>Queries run</strong>
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
<p style='text-align:center' id='footer'><input type='submit' class='button' value='Take me to my forum!' name='finish'/></p>
<hr class='separator'/>
<p id='version'>esoBB version <?php echo $install->getVersion(); ?></p>
<?php break;

}
?>

</div>
</form>

</body>
</html>
