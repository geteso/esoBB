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
 * Settings controller: handles the 'my settings' page.  Changes avatar,
 * color, and handles settings forms.
 */
class settings extends Controller {
	
var $view = "settings.view.php";
var $messages = array();
var $emailVerified = 1;
var $languages;
var $form;
var $loggedOutElsewhere = 0;
var $sessions = array();

// Initialize: perform any necessary saving actions, and define the form contents.
function init()
{
	// If we're not logged in, go to the join page.
	if (!$this->eso->user) redirect("join");
	
	// Set the title.
	global $config, $language;
	$this->title = $language["My settings"];
	
	$this->eso->addLanguageToJS("avatarFileInvalidType", "avatarFileTooLarge", "avatarUrlInvalidExtension", "avatarUrlInvalidFormat");
	
	// Has the user verified their email address?
	if (!empty($config["sendEmail"])) {
		$memberId = (int)$this->eso->user["memberId"];
		$this->emailVerified = $this->eso->db->fetchOne("SELECT emailVerified FROM {$config["tablePrefix"]}members WHERE memberId=?", "i", $memberId);
	}
	
	// Change the user's color.
	if (!empty($_GET["changeColor"]) and (int)$_GET["changeColor"]
		and $this->eso->validateToken(@$_GET["token"])) $this->changeColor($_GET["changeColor"]);
	
	// Change the user's avatar.
	if (!empty($config["changeAvatar"]) and isset($_POST["changeAvatar"])
	 	and $this->eso->validateToken(@$_POST["token"])
		and $this->changeAvatar()) { $this->eso->message("changesSaved"); unset($_POST["avatar"]); }
	
	// Change the user's password or email.
	if (isset($_POST["settingsPasswordEmail"]["submit"])
		and $this->eso->validateToken(@$_POST["token"])
		and $this->changePasswordEmail()) {
		$this->eso->message($this->loggedOutElsewhere ? "changesSavedLoggedOutElsewhere" : "changesSaved");
		redirect("settings");
	}
	// Change the user's name.
	if (!empty($config["changeUsername"]) and isset($_POST["settingsUsername"]["submit"])
		and $this->eso->validateToken(@$_POST["token"])
		and $this->changeUsername()) {
		$this->eso->message("changesSaved");
		redirect("settings");
	}

	if (isset($_GET["logOutSession"]) and $this->eso->validateToken(@$_GET["token"])) {
		if ($this->eso->endLogin($_GET["logOutSession"], $this->eso->user["memberId"])) $this->eso->message("sessionLoggedOut");
		redirect("settings");
	}
	if (isset($_POST["logOutOtherSessions"]) and $this->eso->validateToken(@$_POST["token"])) {
		$this->eso->endLogins($this->eso->user["memberId"], (int)@$_SESSION["loginId"]);
		$this->eso->message("otherSessionsLoggedOut");
		redirect("settings");
	}

	if (!defined("AJAX_REQUEST")) {
		$this->sessions = $this->eso->getLogins($this->eso->user["memberId"]);
		$this->eso->addLanguageToJS("show", "hide", "confirmLogOutOtherSessions");
	}

	// Loop through the languages directory to create a string of options to go in the language <select> tag.
	$langOptions = "";
	$this->languages = $this->eso->getLanguages();
 	foreach ($this->languages as $v) {
 		$value = ($v == $config["language"]) ? "" : $v;
 		$langOptions .= "<option value='$value'" . ($this->eso->user["language"] == $value ? " selected='selected'" : "") . ">$v</option>";
	}
	
	// Create a string of options to go in the avatar alignment <select> tag.
	$avatarAlignmentOptions = "";
	$align = array(
		"alternate" => $language["on alternating sides"],
		"right" => $language["on the right"],
		"left" => $language["on the left"],
		"none" => $language["do not display avatars"]
	);
	foreach ($align as $k => $v)
		$avatarAlignmentOptions .= "<option value='$k'" . ($this->eso->user["avatarAlignment"] == $k ? " selected='selected'" : "") . ">$v</option>";

	// Create a string of options to go in the 'remember me' <select> tag.
	$rememberMeOptions = "";
	foreach (array("ask" => "rememberMeAsk", "always" => "rememberMeAlways", "never" => "rememberMeNever") as $k => $v)
		$rememberMeOptions .= "<option value='$k'" . ($this->eso->user["rememberMe"] == $k ? " selected='selected'" : "") . ">{$language[$v]}</option>";

	$canEmail = ($this->emailVerified == 1 and !empty($config["sendEmail"]));
	
	// Define the elements in the settings form.
	$this->form = array(
		
		"settingsOther" => array(
			"legend" => $language["Other settings"],
			100 => array(
				"id" => "language",
				"html" => "<label>{$language["Forum language"]}</label> <select id='language' name='language'>$langOptions</select>",
				"databaseField" => "language",
				"required" => true,
				"validate" => array("Settings", "validateLanguage")
			),
			200 => array(
				"id" => "avatarAlignment",
				"html" => "<label>{$language["Display avatars"]}</label> <select id='avatarAlignment' name='avatarAlignment'>$avatarAlignmentOptions</select>",
				"databaseField" => "avatarAlignment",
				"validate" => array("Settings", "validateAvatarAlignment"),
				"required" => true
			),
			250 => array(
				"id" => "rememberMe",
				"html" => "<label>{$language["rememberMe"]}</label> <select id='rememberMe' name='rememberMe'>$rememberMeOptions</select>",
				"databaseField" => "rememberMe",
				"validate" => "validateRememberMe",
				"required" => true
			),
			275 => array(
				"id" => "showOnline",
				"html" => "<label for='showOnline' class='checkbox'>{$language["showOnline"]}</label> <input id='showOnline' type='checkbox' class='checkbox' name='showOnline' value='1' " . ($this->eso->user["showOnline"] ? "checked='checked' " : "") . "/>",
				"databaseField" => "showOnline",
				"checkbox" => true
			),
			300 => array(
				"id" => "emailOnPrivateAdd",
				"html" => "<label for='emailOnPrivateAdd' class='checkbox'>{$language["emailOnPrivateAdd"]} <span class='label private'>{$language["labels"]["private"]}</span></label> <input id='emailOnPrivateAdd' type='checkbox' class='checkbox' name='emailOnPrivateAdd' value='1' " . ($this->eso->user["emailOnPrivateAdd"] ? "checked='checked' " : "") . ($canEmail ? "" : "disabled") . "/>",
				"databaseField" => $canEmail ? "emailOnPrivateAdd" : false,
				"checkbox" => true
			),
			400 => array(
				"id" => "emailOnStar",
				"html" => "<label for='emailOnStar' class='checkbox'>{$language["emailOnStar"]} <span class='star1 starInline'>*</span></label> <input id='emailOnStar' type='checkbox' class='checkbox' name='emailOnStar' value='1' " .  ($this->eso->user["emailOnStar"] ? "checked='checked' " : "") . ($canEmail ? "" : "disabled") . "/>",
				"databaseField" => $canEmail ? "emailOnStar" : false,
				"checkbox" => true
			),
			500 => array(
				"id" => "disableJSEffects",
				"html" => "<label for='disableJSEffects' class='checkbox'>{$language["disableJSEffects"]}</label> <input id='disableJSEffects' type='checkbox' class='checkbox' name='disableJSEffects' value='1' " .  (!empty($this->eso->user["disableJSEffects"]) ? "checked='checked' " : "") . "/>",
				"databaseField" => "disableJSEffects",
				"checkbox" => true
			),
			550 => array(
				"id" => "disableLinkAlerts",
				"html" => "<label for='disableLinkAlerts' class='checkbox'>{$language["disableLinkAlerts"]}</label> <input id='disableLinkAlerts' type='checkbox' class='checkbox' name='disableLinkAlerts' value='1' " .  (!empty($this->eso->user["disableLinkAlerts"]) ? "checked='checked' " : "") . "/>",
				"databaseField" => "disableLinkAlerts",
				"checkbox" => true
			)//use 550 to avoid breaking plugins
		)
	
	);
	
	if (empty($config["sendEmail"])) unset($this->form["settingsOther"][300], $this->form["settingsOther"][400]);
	
	$this->callHook("init");
	
	// Save settings if the big submit button was clicked.
	if (isset($_POST["submit"]) and $this->eso->validateToken(@$_POST["token"]) and $this->saveSettings()) {
		$this->eso->message("changesSaved");
		redirect("settings");
	}	
}

// Save settings defined by the fields in the big form array.
function saveSettings()
{
	// Get the fields which we are saving into an array (we don't need fieldsets.)
	$fields = array();
	foreach ($this->form as $k => $fieldset) {
		foreach ($fieldset as $j => $field) {
			if (!is_array($field)) continue;
			$this->form[$k][$j]["input"] = @$_POST[$field["id"]];
			$fields[] = &$this->form[$k][$j];
		}
	}
		
	// Go through the fields and validate them. If a field is required, or if data has been entered (regardless of
	// whether it's required), validate it using the field's validation callback function.
	$validationError = false;
	foreach ($fields as $k => $field) {
		if ((!empty($field["required"]) or $field["input"])	and !empty($field["validate"])
			and $msg = @call_user_func_array($field["validate"], array(&$fields[$k]["input"]))) {
			$validationError = true;
			$fields[$k]["message"] = $msg;
		}
	}
	
	$this->callHook("validateForm", array(&$validationError));
	
	// If there was a validation error, don't continue.
	if ($validationError) return false;
	
	// Construct the query to save the member's settings.
	// Loop through the form fields and use their "databaseField" and "input" attributes for the query.
	global $config;
	$updateData = array();
	foreach ($fields as $field) {
		if (!is_array($field)) continue;
		if (!empty($field["databaseField"])) {
			$updateData[$field["databaseField"]] = !empty($field["checkbox"])
				? ($field["input"] ? 1 : 0)
				: $field["input"];
		}
	}
	
	$this->callHook("beforeSave", array(&$updateData));
	
	// Build UPDATE query with prepared statement placeholders
	if (count($updateData)) {
		$setClause = array();
		$types = "";
		$params = array();
		foreach ($updateData as $field => $value) {
			$setClause[] = "$field=?";
			$types .= is_int($value) ? "i" : "s";
			$params[] = $value;
		}
		$memberId = (int)$this->eso->user["memberId"];
		$query = "UPDATE {$config["tablePrefix"]}members SET " . implode(", ", $setClause) . " WHERE memberId=?";
		$types .= "i";
		$params[] = $memberId;
		$this->eso->db->queryPrepared($query, $types, ...$params);
	}
	
	// Update user session data according to the field "databaseField" values.
	foreach ($fields as $field) {
		if (!empty($field["databaseField"]))
			$_SESSION["user"][$field["databaseField"]] = $this->eso->user[$field["databaseField"]] = $field["input"];
	}
	
	$this->callHook("afterSave");
	
	return true;
}

function changeUsername()
{
	global $config;
	if ($this->eso->isSuspended()) return false;
	$updateData = array();
	$memberId = (int)$this->eso->user["memberId"];
	$row = $this->eso->db->fetchAssocPrepared("SELECT salt, password FROM {$config["tablePrefix"]}members WHERE memberId=?", "i", $memberId);
	if ($row) {
		$salt = $row["salt"];
		$password = $row["password"];
	} else {
		return false;
	}

	// Are we setting a new username?
	if (!empty($_POST["settingsUsername"]["name"])) {

		// Validate the name, then add the updating part to the query.
		$name = substr($_POST["settingsUsername"]["name"], 0, 31);
		if ($error = $this->validateNewName($name)) $this->messages["username"] = $error;
		elseif ($name !== $this->eso->user["name"]) $updateData["name"] = $name;
	}

	// Check if the user entered their old password correctly.
	if (!verifyPassword($_POST["settingsUsername"]["password"], $password, $salt, $config)) {
		$this->messages["password"] = "incorrectPassword";
		return false;
	}

	// If the new name wasn't valid, don't save it.
	if (count($this->messages)) return false;
	
	// Everything is valid and good to go! Save the new name, if it's different.
	if (count($updateData)) $this->updateMember($updateData);
	$this->messages = array();
	return true;
}

// Validate a new username for the member.
function validateNewName(&$name)
{
	global $config;
	$lower = function($s) { return function_exists("mb_strtolower") ? mb_strtolower($s, "UTF-8") : strtolower($s); };
	if ($config["changeUsername"] === "changeCaps" and $lower($name) !== $lower($this->eso->user["name"])) return "nameChangeCapsOnly";
	return validateName($name, (int)$this->eso->user["memberId"]);
}

// Change the user's password and/or email.
function changePasswordEmail()
{
	global $config;
	$updateData = array();
	$memberId = (int)$this->eso->user["memberId"];
	$row = $this->eso->db->fetchAssocPrepared("SELECT salt, password, email FROM {$config["tablePrefix"]}members WHERE memberId=?", "i", $memberId);
	if ($row) {
		$salt = $row["salt"];
		$password = $row["password"];
	} else {
		return false;
	}
	$newSalt = generateRandomString(32);
	
	// Are we setting a new password?
	if (!empty($_POST["settingsPasswordEmail"]["new"])) {
		
		// Make a copy of the raw password and format it into a hash.
		$hash = hashPassword($_POST["settingsPasswordEmail"]["new"], $config["hashingMethod"] == "bcrypt" ? null : $newSalt, $config);
		if ($error = validatePassword($_POST["settingsPasswordEmail"]["new"])) $this->messages["new"] = $error;
		
		// Do both of the passwords entered match?
		elseif ($_POST["settingsPasswordEmail"]["new"] != $_POST["settingsPasswordEmail"]["confirm"]) $this->messages["confirm"] = "passwordsDontMatch";
		
		// Alright, the password stuff is all good. Add the password updating part to the query.
		else {
			$updateData["password"] = $hash;
			$updateData["salt"] = $newSalt;
		}
		
		// Show a 'reenter information' message next to the current password field just in case we fail later on.
		$this->messages["current"] = "reenterInformation"; 
	}
	
	// Are we setting a new email?
	if (!empty($_POST["settingsPasswordEmail"]["email"])) {

		// Make sure the user isn't suspended (to avoid abuse of email requests).
		if ($this->eso->isSuspended()) return false;
		
		// Validate the email address. If it's ok, add the updating part to the query.
		$email = $_POST["settingsPasswordEmail"]["email"];
		if ($error = validateEmail($email, $memberId)) $this->messages["email"] = $error;
		elseif ($email !== $row["email"]) $updateData["email"] = $email;
		$this->messages["current"] = "reenterInformation";
	
	}
	
	// Check if the user entered their old password correctly.
	if (!verifyPassword($_POST["settingsPasswordEmail"]["current"], $password, $salt, $config)) {
		$this->messages["current"] = "incorrectPassword";
		return false;
	}

	// Everything is valid and good to go! Run the query if necessary.
	if (count(array_diff_key($this->messages, array("current" => true)))) return false;
	
	// Everything is valid and good to go! Save whatever has changed.
	if (count($updateData)) $this->updateMember($updateData);

	if (isset($updateData["password"])) $this->loggedOutElsewhere = $this->eso->endLogins($memberId, (int)@$_SESSION["loginId"]);

	$this->messages = array();
	return true;
}

function updateMember($updateData)
{
	global $config;
	$setClause = array();
	$types = "";
	$params = array();
	foreach ($updateData as $field => $value) {
		$setClause[] = "$field=?";
		$types .= is_int($value) ? "i" : "s";
		$params[] = $value;
	}
	$query = "UPDATE {$config["tablePrefix"]}members SET " . implode(", ", $setClause) . " WHERE memberId=?";
	$types .= "i";
	$params[] = (int)$this->eso->user["memberId"];
	$this->eso->db->queryPrepared($query, $types, ...$params);
	foreach ($updateData as $field => $value) $_SESSION["user"][$field] = $this->eso->user[$field] = $value;
}

// Change the user's avatar.
function changeAvatar()
{
	global $config;
	if ($this->eso->isSuspended()) return false;
	if (empty($config["changeAvatar"])) return false;
	if (empty($_POST["avatar"]["type"])) return false;
	
	$allowedTypes = array("image/jpeg", "image/png", "image/gif", "image/pjpeg", "image/x-png", "image/webp");
	
	// This is where the user's avatar will be saved, suffixed with _thumb and an extension (eg. .jpg).
	$avatarFile = "avatars/{$this->eso->user["memberId"]}";
	$file = false;
	$tempFile = false;
	
	switch ($_POST["avatar"]["type"]) {
		
		// Upload an avatar from the user's computer.
		case "upload":
			
			// Use uploader to validate and get the uploaded file.
			if (!($file = $this->eso->uploader->getUploadedFile("avatarUpload", $allowedTypes))) {
				$this->eso->message($this->eso->uploader->lastError ? $this->eso->uploader->lastError : "avatarError");
				return false;
			}
			$tempFile = false; // Don't delete uploaded files
			break;
		
		// Upload an avatar from a remote URL.
		case "url":
			
			// Use uploader to download and validate the remote image.
			if (!($file = $this->eso->uploader->downloadFromUrl($_POST["avatar"]["url"], $allowedTypes))) {
				$this->eso->message($this->eso->uploader->lastError ? $this->eso->uploader->lastError : "avatarError");
				return false;
			}
			$tempFile = true; // Delete temporary downloaded file
			break;
		
		// Unset the user's avatar.
		case "none":
		
			// If the user doesn't have an avatar, we don't need to do anything!
			if (empty($this->eso->user["avatarFormat"])) return true;
			
			// Delete the avatar and thumbnail files.
			$file = "$avatarFile.{$this->eso->user["avatarFormat"]}";
			if (file_exists($file)) @unlink($file);
			$file = "{$avatarFile}_thumb.{$this->eso->user["avatarFormat"]}";
			if (file_exists($file)) @unlink($file);
			
			// Clear the avatarFormat field in the database and session variable.
			$memberId = (int)$this->eso->user["memberId"];
			$this->eso->db->queryPrepared("UPDATE {$config["tablePrefix"]}members SET avatarFormat=NULL WHERE memberId=?", "i", $memberId);
			$this->eso->user["avatarFormat"] = $_SESSION["user"]["avatarFormat"] = "";
			return true;
			
		default: return false;
	}
	
	// Get image information for hook support.
	$imageInfo = $this->eso->uploader->getImageInfo($file);
	if (!$imageInfo) {
		if ($tempFile) @unlink($file);
		$this->eso->message("avatarError");
		return false;
	}
	
	$mimeType = $imageInfo["mime"];
	$curWidth = $imageInfo["width"];
	$curHeight = $imageInfo["height"];
	
	// Create image resource for hook support (plugins may need it).
	$image = $this->eso->uploader->createImageResource($file, $mimeType);
	if (!$image) {
		if ($tempFile) @unlink($file);
		$this->eso->message("avatarError");
		return false;
	}
	
	// The dimensions we'll need are the normal avatar size and a thumbnail.
	$dimensions = array("" => array($config["avatarMaxWidth"], $config["avatarMaxHeight"]), "_thumb" => array($config["avatarThumbHeight"], $config["avatarThumbHeight"]));

	// Create new destination images according to the $dimensions.
	foreach ($dimensions as $suffix => $values) {
		
		// Set the destination.
		$destination = $avatarFile . $suffix;
		
		// Delete the user's current avatar.
		if (file_exists("$destination.{$this->eso->user["avatarFormat"]}"))
			unlink("$destination.{$this->eso->user["avatarFormat"]}");
		
		// Call hook - if plugin handles it, skip uploader processing.
		if ($this->callHook("resizeAvatar", array($image, $destination, $mimeType, $values[0], $values[1]), true)) continue;

		// Use uploader to save the image.
		$options = array(
			"maxWidth" => $values[0],
			"maxHeight" => $values[1],
			"format" => "auto",
			"preserveAnimation" => ($suffix != "_thumb")
		);
		
		$result = $this->eso->uploader->saveAsImage($file, $destination, $options);
		
		if (!$result["success"]) {
			imagedestroy($image);
			if ($tempFile) @unlink($file);
			$this->eso->message($result["error"] ? $result["error"] : "avatarError");
			return false;
		}
	}
	
	// Clean up temporary stuff.
	imagedestroy($image);
	if ($tempFile) @unlink($file);
	
	// Determine format from uploader's last file info or result.
	$avatarFormat = "jpg";
	if ($this->eso->uploader->lastFileInfo) {
		switch ($this->eso->uploader->lastFileInfo["mime"]) {
			case "image/jpeg":
			case "image/pjpeg":
				$avatarFormat = "jpg";
				break;
			case "image/png":
			case "image/x-png":
				$avatarFormat = "png";
				break;
			case "image/gif":
				$avatarFormat = "gif";
				break;
			case "image/webp":
				$avatarFormat = "webp";
				break;
		}
	}
	
	$memberId = (int)$this->eso->user["memberId"];
	$this->eso->db->queryPrepared("UPDATE {$config["tablePrefix"]}members SET avatarFormat=? WHERE memberId=?", "si", $avatarFormat, $memberId);
	$this->eso->user["avatarFormat"] = $_SESSION["user"]["avatarFormat"] = $avatarFormat;
	
	return true;
}

// Change the user's color.
function changeColor($color)
{
	global $config;
	
	// Make sure the color exists within the current skin!
	if ($this->eso->skin->numberOfColors) $color = max(1, min((int)$color, $this->eso->skin->numberOfColors));
	else $color = 0;

	// Update the database and session variables with the new color.
	$memberId = (int)$this->eso->user["memberId"];
	$this->eso->db->queryPrepared("UPDATE {$config["tablePrefix"]}members SET color=? WHERE memberId=?", "ii", $color, $memberId);
	$this->eso->user["color"] = $_SESSION["user"]["color"] = $color;
}

// Run AJAX actions.
function ajax()
{
	if ($return = $this->callHook("ajax", null, true)) return $return;

	switch ($_POST["action"]) {
		
		// Change the user's color.
		case "changeColor":
			if (!$this->eso->validateToken(@$_POST["token"])) return;
			$this->changeColor(@$_POST["color"]);
			break;

		// Log out one of the member's other sessions.
		case "logOutSession":
			if (!$this->eso->validateToken(@$_POST["token"])) return;
			if (!$this->eso->endLogin(@$_POST["id"], $this->eso->user["memberId"])) return false;
			$this->eso->message("sessionLoggedOut");
			return true;

		// Log out all of the member's other sessions.
		case "logOutOtherSessions":
			if (!$this->eso->validateToken(@$_POST["token"])) return;
			$this->eso->endLogins($this->eso->user["memberId"], (int)@$_SESSION["loginId"]);
			$this->eso->message("otherSessionsLoggedOut");
			return true;

		// Validate a form field.
		case "validate":
			$fieldId = @$_POST["field"];
			$formId = @$_POST["form"];
			$value = @$_POST["value"];
			
			// Validate based on form and field.
			$msg = $this->validateFieldAjax($fieldId, $formId, $value, @$_POST["newPassword"]);
			if ($msg) {
				return array("validated" => false, "message" => $this->eso->htmlMessage($msg));
			} else {
				$defaultMessage = "";
				if ($formId == "settingsUser" && $fieldId == "name" && empty($value)) {
					global $config;
					$defaultMessage = $this->eso->htmlMessage($config["changeUsername"] === "changeCaps" ? "changeYourNameCaps" : "changeYourName");
				}
				return array("validated" => true, "message" => $defaultMessage);
			}
	}
}

// Generate the HTML for one of the member's sessions.
function htmlSession($session)
{
	global $language, $config;
	$id = (int)$session["loginId"];
	$current = $id == (int)@$_SESSION["loginId"];
	$agent = parseUserAgent($session["userAgent"]);
	$ip = $session["ip"] !== null ? @inet_ntop($session["ip"]) : false;
	$lastTime = $current ? time() : (int)$session["lastTime"];
	$abbrTime = function($time) use ($language) {
		return "<abbr title='" . date($language["dateFormat"], $time) . "'>" . relativeTime($time) . "</abbr>";
	};

	$where = $agent["device"] ? $agent["device"] : $agent["system"];
	if ($agent["browser"] and $where) $title = sprintf($language["browserOnSystem"], $agent["browser"], $where);
	elseif ($agent["browser"] or $where) $title = $agent["browser"] ? $agent["browser"] : $where;
	else $title = $language["Unknown browser"];

	// Controls: show its details, and an actions menu.
	$copy = array(100 => "<a href='#' onclick='return copyText(\"$id\")'>{$language["session ID"]}</a>");
	if ($session["userAgent"]) $copy[200] = "<a href='#' onclick='return Settings.copySessionDetail($id, \"userAgent\")'>{$language["user agent"]}</a>";
	if ($ip) $copy[300] = "<a href='#' onclick='return Settings.copySessionDetail($id, \"ip\")'>{$language["IP address"]}</a>";
	$actions = array(100 => $this->eso->htmlDropdown("copy text", $copy, array("align" => "right")));
	if (!$current) $actions[200] = "<a href='" . makeLink("settings", "?logOutSession=$id", "&token={$_SESSION["token"]}") . "' onclick='return Settings.logOutSession($id)'>{$language["log out session"]}</a>";
	$controls = array(
		100 => "<a href='#' id='session-$id-toggle' onclick='return Settings.toggleSession($id)'>{$language["show"]}</a>",
		"actions" => $actions
	);
	$this->callHook("getSessionControls", array($session, &$controls));

	$details = array();
	if ($session["firstTime"]) $details[$language["Logged in"]] = $abbrTime((int)$session["firstTime"]);
	$details[$language["Last active"]] = $abbrTime($lastTime);
	if ($session["remembered"]) $details[$language["Remember until"]] = $abbrTime($lastTime + $config["cookieExpire"]);
	if ($agent["system"]) $details[$language["System"]] = $agent["system"] . ($agent["device"] ? " ({$agent["device"]})" : "");
	if ($agent["browser"]) $details[$language["Browser"]] = $agent["browser"] . ($agent["version"] ? " {$agent["version"]}" : "");
	if ($session["userAgent"]) $details[$language["User agent"]] = "<code class='userAgent'>" . sanitizeHTML($session["userAgent"]) . "</code>";
	if ($ip) $details[$language["IP address"]] = "<code class='ip'>$ip</code>";
	$detailsHtml = "";
	foreach ($details as $label => $value) $detailsHtml .= "<li><label>$label</label> <div>$value</div></li>\n";

	return "<li id='session-$id'" . ($current ? " class='current'" : "") . ">
<div class='hdr'>
<div class='controls'>" . $this->eso->htmlControls($controls, array("align" => "right")) . "</div>
<strong>$title</strong>
<small>#$id" . ($current ? " {$language["(this device)"]}" : "") . "</small>
<small><span title='" . date($language["dateFormat"], $lastTime) . "'>" . relativeTime($lastTime) . "</span></small>
</div>
<div id='session-$id-details' class='details'><ul class='form stats'>
$detailsHtml</ul></div>
</li>\n";
}

// Add an element to the a fieldset in the form.
function addToForm($fieldset, $field, $position = false)
{
	return addToArray($this->form[$fieldset], $field, $position);
}

// Add a fieldset to the form.
function addFieldset($fieldset, $legend, $position = false)
{
	return addToArrayString($this->form, $fieldset, array("legend" => $legend), $position);
}

// Validate the avatar alignment field: it must be "alternate", "right", "left", or "none".
function validateAvatarAlignment(&$alignment)
{
	if (!in_array($alignment, array("alternate", "right", "left", "none"))) $alignment = "alternate";
}

// Validate the language field: make sure the selected language actually exists.
function validateLanguage(&$language)
{
	if (!in_array($language, $this->languages)) $language = "";
}

// Validate a field for AJAX validation.
function validateFieldAjax($fieldId, $formId, $value, $newPassword = null)
{
	global $config;
	
	switch ($formId) {
		
		// Password/Email form.
		case "settingsPassword":
			switch ($fieldId) {
				case "newPassword":
					// Validate new password format - match Join behavior: validate even if short
					// If field is empty, it's optional so return valid.
					if (empty($value)) return false; // Optional field
					// Always validate password length (even for short passwords) - matches Join
					return validatePassword($value);
				
				case "confirm":
					// Confirm password must match new password - match Join behavior exactly
					// Get newPassword value (from POST or parameter)
					$passwordValue = $newPassword !== null ? $newPassword : @$_POST["newPassword"];
					
					// If newPassword has a value, confirm must match (even if confirm is empty)
					if (!empty($passwordValue)) {
						if ($value != $passwordValue) return "passwordsDontMatch";
					} else if (!empty($value)) {
						// If newPassword is empty but confirm has a value, they don't match
						return "passwordsDontMatch";
					}
					return false; // Valid
				
				case "email":
					// Validate email format (but allow same email).
					if (empty($value)) return false; // Optional field
					$email = $value;
					return validateEmail($email, (int)$this->eso->user["memberId"]);
				
				case "current":
					// Current password validation: check format only for AJAX.
					// Whether it's required depends on if new password or email is being changed.
					// This is handled client-side. Full password verification happens on form submission.
					// Match username form password validation behavior - always validate length if provided
					if (empty($value)) {
						// Check if newPassword or email is being changed (client should send this info).
						// For now, return passwordTooShort if empty (client will handle conditional requirement).
						return "passwordTooShort";
					}
					// Validate password length if provided - matches username form behavior
					// Pass by reference to match validatePassword() signature
					$passwordValue = $value;
					return validatePassword($passwordValue);
			}
			break;
		
		// Username form.
		case "settingsUser":
			switch ($fieldId) {
				case "name":
					// Validate username format and uniqueness.
					// Don't show error when empty - match Join behavior (errors only show after user types)
					if (empty($value)) return false;
					$name = substr($value, 0, 31);
					return $this->validateNewName($name);
				
				case "password":
					// Password is required for username form - match Join behavior
					// Don't show error when empty - match Join behavior (errors only show after user types)
					if (empty($value)) return false;
					// Validate password length if provided - matches Join behavior
					return validatePassword($value);
			}
			break;
	}
	
	return false; // Default: valid
}

}

?>
