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
 * Forgot password controller: sends a user an email containing a link to
 * reset their password, and handles this link to enable the user to set
 * a new password.
 */
class forgotpassword extends Controller {

var $view = "forgotPassword.view.php";
var $title = "";
var $errors = array();
var $setPassword = false;

function init()
{
	global $language, $messages, $config;

	// If we're already logged in, go to 'My settings'.
	if ($this->eso->user) redirect("settings");
	
	// If email sending is disabled, kick them out.
	elseif (empty($config["sendEmail"])) {
		$this->eso->message("sendEmailDisabled");
		redirect("");
	}
	
	// Set the title.
	$this->title = $language["Forgot your password"];
	
	// If a password reset token has been provided, ie. they've clicked the link in their email.
	if ($hash = @$_GET["q2"]) {
		
		// Find the user with this password reset token.  If it's an invalid token, take them back to the email form.
		$memberId = $this->eso->db->fetchOne("SELECT memberId FROM {$config["tablePrefix"]}members WHERE resetPassword=?", "s", $hash);
		if (!$memberId) redirect("forgotPassword");
		
		$this->setPassword = true;
		
		// If the change password form has been submitted.
		if (isset($_POST["changePassword"])) {
			
			// Validate the passwords they entered.
			$password = @$_POST["password"];
			$confirm = @$_POST["confirm"];
			if ($error = validatePassword($password)) $this->errors["password"] = $error;
			if ($password != $confirm) $this->errors["confirm"] = "passwordsDontMatch";
			
			// If it's all good, update the password in the database, show a success message, and redirect.
			if (!count($this->errors)) {
				$salt = $config["hashingMethod"] == "bcrypt" ? "" : generateRandomString(32);
				$passwordHash = hashPassword($password, $salt ?: null, $config);
				$memberId = (int)$memberId;
				$this->eso->db->queryPrepared("UPDATE {$config["tablePrefix"]}members SET resetPassword=NULL, password=?, salt=? WHERE memberId=?", "ssi", $passwordHash, $salt, $memberId);
				$this->eso->message("passwordChanged", false);
				redirect("");
			}
		}
	}
	
	// If they've submitted their email to get a password reset link, email one to them!
	if (isset($_POST["email"])) {

		// If we're counting logins per minute, impose some flood control measures.
		if ($config["loginsPerMinute"] > 0) {
			if (!checkFloodControl("login", $config["loginsPerMinute"], "logins", "waitToLogin", null)) {
				return;
			}
		}
		
		// Find the member with this email.
		$row = $this->eso->db->fetchAssocPrepared("SELECT memberId, name, email FROM {$config["tablePrefix"]}members WHERE email=?", "s", $_POST["email"]);
		if (!$row) {
			$this->eso->message("emailDoesntExist");
			return;
		}
		$memberId = $row["memberId"];
		$name = $row["name"];
		$email = $row["email"];
		
		// Update their record in the database with a special password reset hash.
		$hash = bin2hex(random_bytes(16));
		$memberId = (int)$memberId;
		$this->eso->db->queryPrepared("UPDATE {$config["tablePrefix"]}members SET resetPassword=? WHERE memberId=?", "si", $hash, $memberId);
		
		// Send them email containing the link, and redirect to the home page.
		if (sendEmail($email, sprintf($language["emails"]["forgotPassword"]["subject"], $name), sprintf($language["emails"]["forgotPassword"]["body"], $name, $config["forumTitle"], $config["baseURL"] . makeLink("forgot-password", $hash)))) {
			$this->eso->message("passwordEmailSent", false);
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
			if ($_POST["field"] == "email") {
				$email = @$_POST["value"];
				// Check email format.
				if (!preg_match("/^[A-Z0-9._%-+.-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i", $email)) {
					return array("validated" => false, "message" => $this->eso->htmlMessage("invalidEmail"));
				}
				return array("validated" => true, "message" => "");
			}
			break;
	}
}


}
