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

// Initialize: perform any necessary saving actions, and define the form contents.
function init()
{
	// If we're not logged in, go to the join page.
	if (!$this->eso->user) redirect("join");
	
	// Set the title.
	global $config, $language;
	$this->title = $language["My settings"];
	
	// Has the user verified their email address?
	if (!empty($config["sendEmail"])) $this->emailVerified = $this->eso->db->result("SELECT emailVerified FROM {$config["tablePrefix"]}members WHERE memberId={$this->eso->user["memberId"]}", 0);
	
	// Change the user's color.
	if (!empty($_GET["changeColor"]) and (int)$_GET["changeColor"]
		and $this->eso->validateToken(@$_GET["token"])) $this->changeColor($_GET["changeColor"]);
	
	// Change the user's avatar.
	if (!empty($config["changeAvatar"]) and isset($_POST["changeAvatar"])
	 	and $this->eso->validateToken(@$_POST["token"])
		and $this->changeAvatar()) $this->eso->message("changesSaved");
	
	// Change the user's password or email.
	if (isset($_POST["settingsPasswordEmail"]["submit"])
		and $this->eso->validateToken(@$_POST["token"])
		and $this->changePasswordEmail()) {
		$this->eso->message("changesSaved");
		redirect("settings");
	}
	// Change the user's name.
	if (!empty($config["changeUsername"]) and isset($_POST["settingsUsername"]["submit"])
		and $this->eso->validateToken(@$_POST["token"])
		and $this->changeUsername()) {
		$this->eso->message("changesSaved");
		// Update the username in session and user object.
		$newName = substr($_POST["settingsUsername"]["name"], 0, 31);
		$_SESSION["user"]["name"] = $newName;
		$this->eso->user["name"] = $newName;
		redirect("settings");
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
			300 => array(
				"id" => "emailOnPrivateAdd",
				"html" => "<label for='emailOnPrivateAdd' class='checkbox'>{$language["emailOnPrivateAdd"]} <span class='label private'>{$language["labels"]["private"]}</span></label> <input id='emailOnPrivateAdd' type='checkbox' class='checkbox' name='emailOnPrivateAdd' value='1' " . ($this->eso->user["emailOnPrivateAdd"] ? "checked='checked' " : "") . ($this->emailVerified == 1 && !empty($config["sendEmail"]) ? "" : "disabled") . "/>",
				"databaseField" => "emailOnPrivateAdd",
				"checkbox" => true
			),
			400 => array(
				"id" => "emailOnStar",
				"html" => "<label for='emailOnStar' class='checkbox'>{$language["emailOnStar"]} <span class='star1 starInline'>*</span></label> <input id='emailOnStar' type='checkbox' class='checkbox' name='emailOnStar' value='1' " .  ($this->eso->user["emailOnStar"] ? "checked='checked' " : "") . ($this->emailVerified == 1 && !empty($config["sendEmail"]) ? "" : "disabled") . "/>",
				"databaseField" => "emailOnStar",
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
			// Skip email-related settings if email is not verified or sendEmail is disabled.
			if (($field["databaseField"] == "emailOnPrivateAdd" || $field["databaseField"] == "emailOnStar") && ($this->emailVerified != 1 || empty($config["sendEmail"]))) continue;
			$updateData[$field["databaseField"]] = !empty($field["checkbox"])
				? ($field["input"] ? 1 : 0)
				: "'{$field["input"]}'";
		}
	}
	
	$this->callHook("beforeSave", array(&$updateData));
	
	// Construct and execute the query!
	$updateQuery = $this->eso->db->constructUpdateQuery("members", $updateData, array("memberId" => $this->eso->user["memberId"]));
	$this->eso->db->query($updateQuery);
	
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
	$salt = $this->eso->db->result("SELECT salt FROM {$config["tablePrefix"]}members WHERE memberId={$this->eso->user["memberId"]}", 0);
	$password = $this->eso->db->result("SELECT password FROM {$config["tablePrefix"]}members WHERE memberId={$this->eso->user["memberId"]}", 0);

	// Are we setting a new username?
	if (!empty($_POST["settingsUsername"]["name"])) {

		// Validate the name, then add the updating part to the query.
		$name = substr($_POST["settingsUsername"]["name"], 0, 31);
		// Prevent duplicates and allow changes to capitalization of the same name.
		if (!($name !== $this->eso->user["name"] and strtolower($name) == strtolower($this->eso->user["name"])) and $error = validateName($name)) $this->messages["username"] = $error;
		else $updateData["name"] = "'{$_POST["settingsUsername"]["name"]}'";
//		$this->messages["current"] = "reenterInformation";

		if ($name !== $this->eso->user["name"]) {
			if ($name !== $this->eso->user["name"] and strtolower($name) == strtolower($this->eso->user["name"]))
			if ($error = validateName($name)) $this->messages["username"] = $error;
			else $updateData["name"] = "'{$_POST["settingsUsername"]["name"]}'";
		} else {

		}
	}

	// Check if the user entered their old password correctly.
	$hash = md5($salt . $_POST["settingsUsername"]["password"]);
	if (($config["hashingMethod"] == "bcrypt" and !password_verify($_POST["settingsUsername"]["password"], $password)) or ($config["hashingMethod"] !== "bcrypt" and !$this->eso->db->result("SELECT 1 FROM {$config["tablePrefix"]}members WHERE memberId={$this->eso->user["memberId"]} AND password='" . $hash . "'", 0))) $this->messages["password"] = "incorrectPassword";

	// Everything is valid and good to go! Run the query if necessary.
	elseif (count($updateData)) {
		$query = $this->eso->db->constructUpdateQuery("members", $updateData, array("memberId" => $this->eso->user["memberId"]));
		$this->eso->db->query($query);
		$this->messages = array();
		return true;
	}

	return false;
}

// Change the user's password and/or email.
function changePasswordEmail()
{
	global $config;
	$updateData = array();
	$salt = $this->eso->db->result("SELECT salt FROM {$config["tablePrefix"]}members WHERE memberId={$this->eso->user["memberId"]}", 0);
	$newSalt = generateRandomString(32);
	$password = $this->eso->db->result("SELECT password FROM {$config["tablePrefix"]}members WHERE memberId={$this->eso->user["memberId"]}", 0);
	
	// Are we setting a new password?
	if (!empty($_POST["settingsPasswordEmail"]["new"])) {
		
		// Make a copy of the raw password and format it into a hash.
		if ($config["hashingMethod"] == "bcrypt") {
			$hash = password_hash($_POST["settingsPasswordEmail"]["new"], PASSWORD_DEFAULT);
		} else {
			$hash = md5($newSalt . $_POST["settingsPasswordEmail"]["new"]);
		}
		if ($error = validatePassword($_POST["settingsPasswordEmail"]["new"])) $this->messages["new"] = $error;
		
		// Do both of the passwords entered match?
		elseif ($_POST["settingsPasswordEmail"]["new"] != $_POST["settingsPasswordEmail"]["confirm"]) $this->messages["confirm"] = "passwordsDontMatch";
		
		// Alright, the password stuff is all good. Add the password updating part to the query.
		else {
			$updateData["password"] = "'$hash'";
			$updateData["salt"] = "'$newSalt'";
		}
		
		// Show a 'reenter information' message next to the current password field just in case we fail later on.
		$this->messages["current"] = "reenterInformation"; 
	}
	
	// Are we setting a new email?
	if (!empty($_POST["settingsPasswordEmail"]["email"])) {

		// Make sure the user isn't suspended (to avoid abuse of email requests).
		if ($this->eso->isSuspended()) return false;
		
		// Validate the email address. If it's ok, add the updating part to the query.
		if ($error = validateEmail($_POST["settingsPasswordEmail"]["email"])) $this->messages["email"] = $error;
		else $updateData["email"] = "'{$_POST["settingsPasswordEmail"]["email"]}'";
		$this->messages["current"] = "reenterInformation";
		
	}
	
	// Check if the user entered their old password correctly.
	$hash = md5($salt . $_POST["settingsPasswordEmail"]["current"]);
	if (($config["hashingMethod"] == "bcrypt" and !password_verify($_POST["settingsPasswordEmail"]["current"], $password)) or ($config["hashingMethod"] !== "bcrypt" and !$this->eso->db->result("SELECT 1 FROM {$config["tablePrefix"]}members WHERE memberId={$this->eso->user["memberId"]} AND password='" . $hash . "'", 0))) $this->messages["current"] = "incorrectPassword";

	// Everything is valid and good to go! Run the query if necessary.
	elseif (count($updateData)) {
		$query = $this->eso->db->constructUpdateQuery("members", $updateData, array("memberId" => $this->eso->user["memberId"]));
		$this->eso->db->query($query);
		$this->messages = array();
		return true;
	}
	
	return false;
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
			$this->eso->db->query("UPDATE {$config["tablePrefix"]}members SET avatarFormat=NULL WHERE memberId={$this->eso->user["memberId"]}");
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
	
	$avatarFormatEscaped = $this->eso->db->escape($avatarFormat);
	$this->eso->db->query("UPDATE {$config["tablePrefix"]}members SET avatarFormat='$avatarFormatEscaped' WHERE memberId={$this->eso->user["memberId"]}");
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
	$this->eso->db->query("UPDATE {$config["tablePrefix"]}members SET color=$color WHERE memberId={$this->eso->user["memberId"]}");
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
					global $language;
					$defaultMessage = $this->eso->htmlMessage("changeYourName");
				}
				return array("validated" => true, "message" => $defaultMessage);
			}
	}
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
					if ($error = validateEmail($email)) {
						// If email is taken, check if it's the user's own email.
						if ($error == "emailTaken") {
							$currentEmail = $this->eso->db->result("SELECT email FROM {$config["tablePrefix"]}members WHERE memberId={$this->eso->user["memberId"]}", 0);
							if (strtolower($email) == strtolower($currentEmail)) return false; // Same email is valid
						}
						return $error;
					}
					return false; // Valid
				
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
					// Allow capitalization changes of the same name.
					if ($name !== $this->eso->user["name"] && strtolower($name) == strtolower($this->eso->user["name"])) {
						return false; // Valid (just capitalization change)
					}
					return validateName($name);
				
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
