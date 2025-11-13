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
 * Join controller: handles the 'join this forum' page. Defines form
 * data, validates it, adds the member to the database, and handles the
 * link from the verification email.
 */
class join extends Controller {

var $view = "join.view.php";

// Initialize: define the form contents, and check to see if form data was submitted.
function init()
{
	global $language, $config;

	// If we're already logged in, go to 'My settings'.
	if ($this->eso->user) redirect("settings");

	// If registration is disabled, kick them out.
	elseif (empty($config["registrationOpen"])) {
		$this->eso->message("registrationClosed");
		redirect("");
	}

	// Set the title.
	$this->title = $language["Join this forum"];

	// Only respond to requests for verification emails if we require e-mail verification.
	if (!empty($config["sendEmail"]) && isset($_GET["q2"])) {
		
		// If the user is requesting that we resend their verification email...
		if ($_GET["q2"] == "sendVerification") {
			$memberId = (int)@$_GET["q3"];
			// Generate cryptographically secure random string for verification hash
			$rand = bin2hex(random_bytes(16));
			if (list($email, $name) = $this->eso->db->fetchRow("SELECT email, name FROM {$config["tablePrefix"]}members WHERE memberId=$memberId AND account='Unvalidated'") and $this->eso->db->query("UPDATE {$config["tablePrefix"]}members SET resetPassword='$rand' WHERE memberId=$memberId")) $this->sendVerificationEmail($email, $name, $memberId . $rand);
			$this->eso->message("verifyEmail", false);
		// Otherwise, if there's a verification hash in the URL, attempt to verify the user.
		} else $this->validateMember($_GET["q2"]);
		// Make sure to redirect any requests from logged in users back to 'My settings'.
		if ($this->eso->user) redirect("settings");
		else redirect("");
		return;

	// If we're already logged in, go to 'My settings'.
	} elseif ($this->eso->user) redirect("settings");

	// If registration is disabled, kick them out.
	elseif (empty($config["registrationOpen"])) {
		$this->eso->message("registrationClosed");
		redirect("");
	}
	
	// Define the elements in the join form.
	$this->form = array(
		
		"accountInformation" => array(
			"legend" => $language["Account information"],
			100 => array(
				"id" => "name",
				"html" => @"<label>{$language["Username"]}</label> <input id='name' name='join[name]' type='text' class='text' autocomplete='username' value='{$_POST["join"]["name"]}' maxlength='16' tabindex='100'/>",
				"validate" => "validateName",
				"required" => true,
				"databaseField" => "name",
				"ajax" => true
			),
			200 => array(
				"id" => "email",
				"html" => @"<label>{$language["Email"]}</label> <input id='email' name='join[email]' type='text' class='text' autocomplete='email' value='{$_POST["join"]["email"]}' maxlength='63' tabindex='200'/>",
				"validate" => "validateEmail",
				"required" => true,
				"databaseField" => "email",
				"message" => "emailInfo",
				"ajax" => true
			),
			300 => array(
				"id" => "password",
				"html" => @"<label>{$language["Password"]}</label> <input id='password' name='join[password]' type='password' class='text' autocomplete='new-password' value='{$_POST["join"]["password"]}' tabindex='300'/>",
				"validate" => "validatePassword",
				"required" => true,
				"databaseField" => "password",
				"message" => "passwordInfo",
				"ajax" => true
			),
			400 => array(
				"id" => "confirm",
				"html" => @"<label>{$language["Confirm password"]}</label> <input id='confirm' name='join[confirm]' type='password' class='text' autocomplete='new-password' value='{$_POST["join"]["confirm"]}' tabindex='400'/>",
				"required" => true,
				"validate" => array($this, "validateConfirmPassword"),
				"ajax" => true
			)
		)
		
	);
	
	$this->callHook("init");
	
	// Make an array of just fields (without the enclosing fieldsets) for easy access.
	$this->fields = array();
	foreach ($this->form as $k => $fieldset) {
		if (!is_array($fieldset)) continue;
		foreach ($fieldset as $j => $field) {
			if (!is_array($field)) continue;
			$this->fields[$field["id"]] =& $this->form[$k][$j];
		}
	}
	
	// If the form has been submitted, validate it and add the member into the database.
	if (isset($_POST["join"]) and $this->addMember()) {
		if (!empty($config["sendEmail"]) && !empty($config["requireEmailApproval"])) {
			$this->eso->message("verifyEmail", false);
			redirect("");
		} elseif (!empty($config["requireManualApproval"])) {
			$this->eso->message("waitForApproval", false);
			redirect("");
		} else {
			$this->eso->login($_POST["join"]["name"], $_POST["join"]["password"], false);
			redirect("");
		}
	}
}

// Run AJAX actions.
function ajax()
{
	if ($return = $this->callHook("ajax", null, true)) return $return;
	
	switch ($_POST["action"]) {
		
		// Validate a form field.
		case "validate":
			if ($msg = @call_user_func_array($this->fields[$_POST["field"]]["validate"], array(&$_POST["value"])))
				return array("validated" => false, "message" => $this->eso->htmlMessage($msg));
			else return array("validated" => true, "message" => "");
	}
}

// Validate the form and add the member to the database.
function addMember()
{
	global $config;
	
	// Loop through the form fields and validate them.
	$validationError = false;
	foreach ($this->fields as $k => $field) {
		if (!is_array($field)) continue;
		$this->fields[$k]["input"] = @$_POST["join"][$field["id"]];
		
		// If this field is required, or if data has been entered (regardless of whether it's required), validate it
		// using the field's validation callback function.
		if ((!empty($field["required"]) or $this->fields[$k]["input"]) and !empty($field["validate"])
			and ($msg = @call_user_func_array($field["validate"], array(&$this->fields[$k]["input"])))) {
			
			// If there was a validation error, set the field's message.
			$validationError = true;
			$this->fields[$k]["message"] = $msg;
			$this->fields[$k]["error"] = true;
			
		} else $this->fields[$k]["success"] = true;
	}
	
	$this->callHook("validateForm", array(&$validationError));
	
	// If there was a validation error, don't continue.
	if ($validationError) return false;

	// If registration has been disabled, there's no need to go any further.
	if (($error = $this->canJoin()) !== true) return false;

	// If we're counting logins per minute, impose some flood control measures.
	if (!isset($cookie) and $config["loginsPerMinute"] > 0) {

		// If we have a record of their logins in the session, check how many logins they've performed in the last
		// minute.
		if (!empty($_SESSION["logins"])) {
			// Clean anything older than 60 seconds out of the logins array.
			foreach ($_SESSION["logins"] as $k => $v) {
				if ($v < time() - 60) unset($_SESSION["logins"][$k]);
			}
			// Have they performed >= $config["loginsPerMinute"] logins in the last minute? If so, don't continue.
			if (count($_SESSION["logins"]) >= $config["loginsPerMinute"]) {
				$this->eso->message("waitToLogin", true, array(60 - time() + min($_SESSION["logins"])));
				return;
			}
		}

		// However, if we don't have a record in the session, use the MySQL logins table.
		else {
			// Get the user's IP address.
			$ip = cookieIp();
			// Have they performed >= $config["loginsPerMinute"] logins in the last minute?
			if ($this->eso->db->result("SELECT COUNT(*) FROM {$config["tablePrefix"]}logins WHERE ip=$ip AND memberId=0 AND loginTime>UNIX_TIMESTAMP()-60", 0) >= $config["loginsPerMinute"]) {
				$this->eso->message("waitToLogin", true, 60);
				return;
			}
			// Log this attempt in the logins table (using memberId=0 for anonymous login attempts).
			$this->eso->db->query("INSERT INTO {$config["tablePrefix"]}logins (ip, memberId, loginTime) VALUES ($ip, 0, UNIX_TIMESTAMP())");
			// Proactively clean the logins table of login attempts older than 60 seconds.
			$this->eso->db->query("DELETE FROM {$config["tablePrefix"]}logins WHERE memberId=0 AND loginTime<UNIX_TIMESTAMP()-60");
		}

		// Log this attempt in the session array.
		if (!isset($_SESSION["logins"]) or !is_array($_SESSION["logins"])) $_SESSION["logins"] = array();
		$_SESSION["logins"][] = time();
	}
	
	// Construct the query to insert the member into the database.
	// Loop through the form fields and use their "databaseField" and "input" attributes for the query.
	$insertData = array();
	foreach ($this->fields as $field) {
		if (!is_array($field)) continue;
		if (!empty($field["databaseField"])) $insertData[$field["databaseField"]] = !empty($field["checkbox"])
			? ($field["input"] ? 1 : 0)
			: "'{$field["input"]}'";
	}
	
	// If we're not requiring verification, add a field to the query that "validates" the member without a validation hash.
	if ($config["registrationRequireApproval"] == false) {
		$insertData["account"] = "'Member'";
	}

	// We also need to generate a hash and salt and add them to the query.
	$salt = generateRandomString(32);
	if ($config["hashingMethod"] == "bcrypt") {
		$hash = password_hash($_POST["join"]["password"], PASSWORD_DEFAULT);
	} else {
		$hash = md5($salt . $_POST["join"]["password"]);
	}
	$insertData["password"] = "'$hash'";
	$insertData["salt"] = "'$salt'";

	// Add a few extra fields to the query.
	$insertData["color"] = "FLOOR(1 + (RAND() * {$this->eso->skin->numberOfColors}))";
	$insertData["language"] = "'" . $this->eso->db->escape($config["language"]) . "'";
	$insertData["avatarAlignment"] = "'{$_SESSION["avatarAlignment"]}'";
	
	$this->callHook("beforeAddMember", array(&$insertData));
	
	// Construct the query and make it a REPLACE query rather than an INSERT one (so unvalidated members can be
	// overwritten).
	$insertQuery = $this->eso->db->constructInsertQuery("members", $insertData);
	$insertQuery = "REPLACE" . substr($insertQuery, 6);
	
	// Execute the query and get the new member's ID.
	$this->eso->db->query($insertQuery);
	$memberId = $this->eso->db->lastInsertId();
	
	$this->callHook("afterAddMember", array($memberId));
	
	// Email the member with a verification link so that they can verify their account.
	if (!empty($config["sendEmail"])) {
		// Update their record in the database with a special password reset hash.
		// Generate cryptographically secure random string for verification hash
		$rand = bin2hex(random_bytes(16));
		$this->eso->db->query("UPDATE {$config["tablePrefix"]}members SET resetPassword='$rand' WHERE memberId=$memberId");
		$this->sendVerificationEmail($_POST["join"]["email"], $_POST["join"]["name"], $memberId . $rand);
	}
	
	return true;
}

// To join, registration must be open.
function canJoin() {
	global $config;
	if (empty($config["registrationOpen"])) return "registrationClosed";
	return true;
}

// Send a verification email.
function sendVerificationEmail($email, $name, $verifyHash)
{
	global $language, $config;
	sendEmail($email, sprintf($language["emails"]["join"]["subject"], $name), sprintf($language["emails"]["join"]["body"], $name, $config["forumTitle"], $config["baseURL"] . makeLink("join", $verifyHash)));
}

// Validate a member with the provided validation hash.
function validateMember($hash)
{
	global $config;
	
	// Split the hash into the member ID and password.
	$memberId = (int)substr($hash, 0, strlen($hash) - 32);
	$resetPassword = $this->eso->db->escape(substr($hash, -32));
	
	// See if there is an unvalidated user with this ID and password hash. If there is, validate them and log them in.
	if ($name = @$this->eso->db->result($this->eso->db->query("SELECT name FROM {$config["tablePrefix"]}members WHERE memberId=$memberId AND resetPassword='$resetPassword' AND emailVerified!=1"), 0) and $password = @$this->eso->db->result($this->eso->db->query("SELECT password FROM {$config["tablePrefix"]}members WHERE memberId=$memberId AND resetPassword='$resetPassword' AND emailVerified!=1"), 0)) {
		$this->eso->db->query("UPDATE {$config["tablePrefix"]}members SET " . (empty($config["requireManualApproval"]) ? "account='Member', " : "") . "emailVerified=1 WHERE memberId=$memberId");
		$this->eso->login($name, false, $password);
		if (empty($config["requireManualApproval"]) and $this->eso->db->result($this->eso->db->query("SELECT account FROM {$config["tablePrefix"]}members WHERE memberId=$memberId"), 0) == "Member") $this->eso->message("accountValidated", false);
	}
}

// Add an element to the page's form.
function addToForm($fieldset, $field, $position = false)
{
	return addToArray($this->form[$fieldset], $field, $position);
}

// Add a fieldset to the form.
function addFieldset($fieldset, $legend, $position = false)
{
	return addToArrayString($this->form, $fieldset, array("legend" => $legend), $position);
}

// Validate the confirm password field (see if it matches the password field.)
function validateConfirmPassword($password)
{
	if ($password != (defined("AJAX_REQUEST") ? $_POST["password"] : $_POST["join"]["password"]))
		return "passwordsDontMatch";
}
	
}

?>
