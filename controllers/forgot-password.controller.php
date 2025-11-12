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
		$result = $this->eso->db->query("SELECT memberId FROM {$config["tablePrefix"]}members WHERE resetPassword='$hash'");
		if (!$this->eso->db->numRows($result)) redirect("forgotPassword");
		list($memberId) = $this->eso->db->fetchRow($result);
		
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
				$salt = generateRandomString(32);
				if ($config["hashingMethod"] == "bcrypt") {
					$passwordHash = password_hash($password, PASSWORD_DEFAULT);
				} else {
					$passwordHash = md5($salt . $password);
				}
				$this->eso->db->query("UPDATE {$config["tablePrefix"]}members SET resetPassword=NULL, password='$passwordHash', salt='$salt' WHERE memberId=$memberId");
				$this->eso->message("passwordChanged", false);
				redirect("");
			}
		}
	}
	
	// If they've submitted their email to get a password reset link, email one to them!
	if (isset($_POST["email"])) {

		// If we're counting logins per minute, impose some flood control measures.
		if ($config["loginsPerMinute"] > 0) {

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
		
		// Find the member with this email.
		$result = $this->eso->db->query("SELECT memberId, name, email FROM {$config["tablePrefix"]}members WHERE email='{$_POST["email"]}'");
		if (!$this->eso->db->numRows($result)) {
			$this->eso->message("emailDoesntExist");
			return;
		}
		list($memberId, $name, $email) = $this->eso->db->fetchRow($result);
		
		// Update their record in the database with a special password reset hash.
		$hash = md5(rand());
		$this->eso->db->query("UPDATE {$config["tablePrefix"]}members SET resetPassword='$hash' WHERE memberId=$memberId");
		
		// Send them email containing the link, and redirect to the home page.
		if (sendEmail($email, sprintf($language["emails"]["forgotPassword"]["subject"], $name), sprintf($language["emails"]["forgotPassword"]["body"], $name, $config["forumTitle"], $config["baseURL"] . makeLink("forgot-password", $hash)))) {
			$this->eso->message("passwordEmailSent", false);
			redirect("");
		}
	}
}


}
