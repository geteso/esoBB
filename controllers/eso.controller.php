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
 * eso controller: handles global actions such as logging in/out,
 * preparing the bar, and collecting messages.
 */
class eso extends Controller {

var $db;
var $user;
var $action;
var $allowedActions = array("admin", "conversation", "feed", "forgot-password", "join", "online", "post", "profile", "search", "settings");
var $controller;
var $view = "wrapper.php";
var $language;
var $skin;
var $head = "";
var $scripts = array();
var $jsLanguage = array();
var $jsVars = array();
var $styleSheets = array();
var $footer = array();
var $labels = array(
	"sticky" => "IF(sticky=1,1,0)",
	"private" => "IF(private=1,1,0)",
	"locked" => "IF(locked=1,1,0)",
	"draft" => "IF(s.draft IS NOT NULL,1,0)"
);
var $memberGroups = array("Administrator", "Moderator", "Member", "Suspended");
var $bar = array("left" => array(), "right" => array());
var $plugins = array();
var $uploader;

// Class constructor: connect to the database and perform other initializations.
function __construct()
{	
	global $config;

	// Connect to the database by setting up the database class.
	$this->db = new Database($config["mysqlHost"], $config["mysqlUser"], $config["mysqlPass"], $config["mysqlDB"]);
	$this->db->eso =& $this;
	if ($this->db->connectError())
		$this->fatalError($config["verboseFatalErrors"] ? $this->db->connectError() : "", "mysql");
	
	// Clear messages in the SESSION messages variable.
	if (!isset($_SESSION["messages"]) or !is_array($_SESSION["messages"])) $_SESSION["messages"] = array();
	
	// Create an instance of the Formatter class.
	$this->formatter = new Formatter();
	
	// Create an instance of the Uploader class.
	$this->uploader = new Uploader();
}

// Initialize: set up the user and initialize the bar and other components of the page.
function init()
{
	global $language, $config;

	// Log out if necessary.
	if (@$_GET["q1"] == "logout") $this->logout();
	// If the user is logged in...
	elseif (isset($_SESSION["user"])) {
		$ip = cookieIp();
		$memberId = (int)$_SESSION["user"]["memberId"];
		// Make sure the user data exists in the members table.
		$memberExists = $this->db->fetchOne("SELECT memberId FROM {$config["tablePrefix"]}members WHERE memberId=?", "i", $memberId);
		// Also make sure this device exists in the logins table: first try by cookie if cookie exists, otherwise by IP
		$whereClause = "";
		$loginExists = false;
		$cookie = null;
		if (isset($_COOKIE[$config["cookieName"]])) {
			$cookie = $_COOKIE[$config["cookieName"]];
			$loginExists = $this->db->fetchOne("SELECT memberId FROM {$config["tablePrefix"]}logins WHERE cookie=? AND memberId=?", "si", $cookie, $memberId);
			if ($loginExists) {
				$whereClause = "cookie";
			}
		} else {
			$loginExists = $this->db->fetchOne("SELECT memberId FROM {$config["tablePrefix"]}logins WHERE cookie IS NULL AND ip=? AND memberId=?", "ii", $ip, $memberId);
			if ($loginExists) {
				$whereClause = "ip";
			}
		}
		// If not found by cookie/IP, try finding any record for this memberId and IP (in case cookie expired)
		if (!$loginExists) {
			$loginExists = $this->db->fetchOne("SELECT memberId FROM {$config["tablePrefix"]}logins WHERE ip=? AND memberId=?", "ii", $ip, $memberId);
			if ($loginExists) {
				$whereClause = "ip";
			}
		}
		// Check if session has expired based on lastTime
		$lastTime = 0;
		if ($loginExists) {
			if ($whereClause == "cookie" && $cookie !== null) {
				$lastTime = $this->db->fetchOne("SELECT lastTime FROM {$config["tablePrefix"]}logins WHERE cookie=? AND memberId=?", "si", $cookie, $memberId);
			} else {
				$lastTime = $this->db->fetchOne("SELECT lastTime FROM {$config["tablePrefix"]}logins WHERE cookie IS NULL AND ip=? AND memberId=?", "ii", $ip, $memberId);
			}
			$lastTime = $lastTime ?: 0;
		}
		// If member doesn't exist, login record doesn't exist, or session has expired, logout
		if ($memberId != $memberExists or !$loginExists or ($lastTime and (time() - $lastTime > $config["sessionExpire"]))) {
			$this->logout();
		} else {
			// Update lastTime to track activity
			if ($whereClause == "cookie" && $cookie !== null) {
				$this->db->queryPrepared("UPDATE {$config["tablePrefix"]}logins SET lastTime=UNIX_TIMESTAMP() WHERE cookie=? AND memberId=?", "si", $cookie, $memberId);
			} else {
				$this->db->queryPrepared("UPDATE {$config["tablePrefix"]}logins SET lastTime=UNIX_TIMESTAMP() WHERE cookie IS NULL AND ip=? AND memberId=?", "ii", $ip, $memberId);
			}
		}
	}
	
	// Attempt to log in, and user assign data to the user array.
	if ($this->login(@$_POST["login"]["name"], @$_POST["login"]["password"])) {
		$this->user = $_SESSION["user"] + array(
			"admin" => $_SESSION["user"]["account"] == "Administrator",
			"moderator" => $_SESSION["user"]["account"] == "Moderator" or $_SESSION["user"]["account"] == "Administrator",
			"member" => $_SESSION["user"]["account"] == "Member",
			"suspended" => $_SESSION["user"]["account"] == "Suspended" ? true : null
		);
		$this->user["color"] = min($this->user["color"], $this->skin->numberOfColors);
	}
	
	// Set the default avatarAlignment for logged out users.
	if (!isset($_SESSION["avatarAlignment"])) $_SESSION["avatarAlignment"] = $config["avatarAlignment"];
	
	// Star a conversation if necessary.
	if (isset($_GET["star"]) and $this->validateToken(@$_GET["token"])) $this->star($_GET["star"]);
	
	// If config/custom.css contains something, add it to be included in the page.
	if (filesize("config/custom.css") > 0) $this->addCSS("config/custom.css");

	// Only do the following for non-ajax requests.
	if (!defined("AJAX_REQUEST")) {

		// If the user IS NOT logged in, add the login form and 'Join us' link to the bar.
		if (!$this->user) {
			$this->addToBar("left", "<form action='" . curLink() . "' method='post' id='login' class='vr'><div>
 <input id='loginName' name='login[name]' type='text' class='text' autocomplete='username' placeholder='" . (!empty($_POST["login"]["name"]) ? $_POST["login"]["name"] : $language["Username"]) . "'/>
 <input id='loginPassword' name='login[password]' type='password' class='text' autocomplete='current-password' placeholder='{$language["Password"]}'/>
 " . $this->skin->button(array("value" => $language["Log in"], "class" => "buttonSmall")) . "
 </div></form>", 100);
 			if (!empty($config["registrationOpen"])) $this->addToBar("left", "<a href='" . makeLink("join") . "' id='joinLink'><span class='button buttonSmall'><input type='submit' value='{$language["Join us"]}'></span></a>", 200);
 			if (!empty($config["sendEmail"])) $this->addToBar("left", "<a href='" . makeLink("forgot-password") . "' id='forgotPassword'><span class='button buttonSmall'><input type='submit' value='{$language["Forgot password"]}'></span></a>", 300);
		}
		
		// If the user IS logged in, we want to display their name and appropriate links.
		else {
						$this->addToBar("left", "<strong id='user'><a href='" . makeLink("profile") . "'>{$this->user["name"]}</a>:</strong>", 100);
						$this->addToBar("left", "<a href='{$config["baseURL"]}'><span class='button buttonSmall'><input type='submit' value='{$language["Home"]}'></span></a>", 200);
						$this->addToBar("left", "<a href='" . makeLink("profile") . "' id='profile'><span class='button buttonSmall'><input type='submit' value='{$language["My profile"]}'></span></a>", 300);
						$this->addToBar("left", "<a href='" . makeLink("settings") . "'><span class='button buttonSmall'><input type='submit' value='{$language["My settings"]}'></span></a>", 400);
						$this->addToBar("left", "<a href='" . makeLink("conversation", "new") . "' id='startConversation'><span class='button buttonSmall'><input type='submit' value='{$language["Start a conversation"]}'></span></a>", 500);
						$this->addToBar("left", "<a href='" . makeLink("logout") . "' id='logout' class='vl'><span class='button buttonSmall'><input type='submit' value='{$language["Log out"]}'></span></a>", 1100);
						if ($this->user["moderator"]) $this->addToBar("left", "<a href='" . makeLink("admin") . "'><span class='button buttonSmall'><input type='submit' value='{$language["Dashboard"]}'></span></a>", 700);
		}

		// Add "Forgot password" link to the footer,
		if (!$this->eso->user) $this->eso->addToFooter("<a href='" . makeLink("forgot-password") . "' id='forgotPassword'><span class='button buttonSmall'><input type='submit' value='{$language["Forgot your password"]}'></span></a>", 100);

		// Set up some default JavaScript files and language definitions.
		$this->addScript("js/eso.js", -1);
		$this->addLanguageToJS("ajaxRequestPending", "ajaxDisconnected", "confirmExternalLink");
		
	}
	
	$this->callHook("init");
}

// Run AJAX actions.
function ajax()
{
	global $config;
	
	if ($return = $this->callHook("ajax", null, true)) return $return;
	
	switch (@$_POST["action"]) {
		
		// Star/unstar a conversation.
		case "star":
			if (!$this->validateToken(@$_POST["token"])) return;
			$this->star((int)$_POST["conversationId"]);
			
	}
}

// Attempt to login with argument data ($name and $password), with a cookie, or with a password hash ($hash).
function login($name = false, $password = false, $hash = false)
{
	global $config;
	
	// Are we already logged in?
	if (isset($_SESSION["user"])) return true;

	// If a raw password was passed, convert it into a hash.
	if ($name and $password) {
		$row = $this->db->fetchAssocPrepared("SELECT salt, password FROM {$config["tablePrefix"]}members WHERE name=?", "s", $name);
		if ($row) {
			$salt = $row["salt"];
			$dbPassword = $row["password"];
			if (verifyPassword($password, $dbPassword, $salt, $config)) {
				$hash = $dbPassword;
			} else {
				$hash = hashPassword($password, $salt, $config);
			}
		}
	}
	
	// Otherwise attempt to get the member ID and password hash from a cookie.
	$memberId = false;
	if ($hash === false and isset($_COOKIE[$config["cookieName"]])) {
		$cookie = $_COOKIE[$config["cookieName"]];
		$memberId = $this->db->fetchOne("SELECT memberId FROM {$config["tablePrefix"]}logins WHERE cookie=?", "s", $cookie);
		if ($memberId) {
			$hash = $this->db->fetchOne("SELECT password FROM {$config["tablePrefix"]}members WHERE memberId=?", "i", $memberId);
		}
	}
	
	// If we successfully have a name or member ID, and a hash, then we attempt to login.
	if (($name or ($memberId = (int)$memberId)) and $hash !== false) {
		
		// Only check flood control for actual login attempts (not cookie-based logins)
		$checkFloodControl = false;
		if ($config["loginsPerMinute"] > 0 and ($name or $password)) {
			if (!checkFloodControl("login", $config["loginsPerMinute"], "logins", "waitToLogin", null)) {
				return false;
			}
			$checkFloodControl = true;
		}

		// Call hook with components for compatibility (though we're using prepared statements now)
		$components = array(
			"select" => array("*"),
			"from" => array("{$config["tablePrefix"]}members"),
			"where" => array($name ? "name=?" : "memberId=?", "password=?")
		);
		$this->callHook("beforeLogin", array(&$components));
		
		// Run the query and get the data if there is a matching user.
		// Use prepared statement for login query
		if ($name) {
			$data = $this->db->fetchAssocPrepared("SELECT * FROM {$config["tablePrefix"]}members WHERE name=? AND password=?", "ss", $name, $hash);
		} else {
			$data = $this->db->fetchAssocPrepared("SELECT * FROM {$config["tablePrefix"]}members WHERE memberId=? AND password=?", "is", $memberId, $hash);
		}
		
		if ($data) {

			$this->callHook("afterLogin", array(&$data));

			// If a raw password was passed, check if it is an md5 hash (only if we are using bcrypt) and update it.
			if ($password and $config["hashingMethod"] == "bcrypt" and !password_verify($hash, $data["password"]) and $hash == md5($data["salt"] . $password)) {
				// Generate cryptographically secure random string for resetPassword
				$rand = bin2hex(random_bytes(16));
				$newHash = hashPassword($password, null, $config);
				$memberId = (int)$data["memberId"];
				$this->db->queryPrepared("UPDATE {$config["tablePrefix"]}members SET resetPassword=?, password=? WHERE memberId=?", "ssi", $rand, $newHash, $memberId);
				$this->message("passwordUpgraded", false);
			}

			if ($data["account"] == "Unvalidated") {
				// If sendEmail is enabled and we're requiring email verification to login, show a message with a link to resend a verification email.
				if (!empty($config["sendEmail"]) and !empty($config["requireEmailApproval"]) and !$data["emailVerified"]) {
					$this->message("accountNotYetVerified", false, makeLink("join", "sendVerification", $data["memberId"]));
					return false;
				// If we're manually approving accounts, show a message that says to wait for approval.
				// Even if this forum doesn't require verification, accounts that were made before that change will need approval.
				} elseif (!empty($config["requireManualApproval"])) {
					$this->message("waitForApproval", false);
					return false;
				}
			}
			
			// Assign the user data to a SESSION variable, and as a property of the eso class.
			$_SESSION["user"] = $this->user = $data;
			
			// Regenerate the session ID and token.
			regenerateToken();

			// Set any necessary cookies and update the logins table.
			if ($name !== false or $password !== false) {
				$ip = cookieIp();
				$userAgent = md5($_SERVER["HTTP_USER_AGENT"]);
				// For join and/or verification, always set 'remember me' cookie. For a normal login form, check if
				// 'remember me' was selected (defaults to true based on config).
				$rememberMe = (@$_POST["join"] or ($name !== false and $password === false and $hash !== false)) ? true : (!empty($config["rememberMe"]));
				// If there already exists a record for this cookie or IP address, update it accordingly.
				$sessionMemberId = (int)$_SESSION["user"]["memberId"];
				$existingRecord = false;
				if (isset($_COOKIE[$config["cookieName"]])) {
					$cookie = $_COOKIE[$config["cookieName"]];
					$existingRecord = $this->db->fetchOne("SELECT cookie FROM {$config["tablePrefix"]}logins WHERE cookie=? AND memberId=?", "si", $cookie, $sessionMemberId);
				} else {
					$existingRecord = $this->db->fetchOne("SELECT cookie FROM {$config["tablePrefix"]}logins WHERE cookie IS NULL AND ip=? AND memberId=?", "ii", $ip, $sessionMemberId);
				}
				if ($existingRecord) {
					if (isset($_COOKIE[$config["cookieName"]])) {
						$cookie = $_COOKIE[$config["cookieName"]];
						$this->db->queryPrepared("UPDATE {$config["tablePrefix"]}logins SET ip=?, userAgent=?, lastTime=UNIX_TIMESTAMP() WHERE cookie=? AND memberId=?", "issi", $ip, $userAgent, $cookie, $sessionMemberId);
					} else {
						$this->db->queryPrepared("UPDATE {$config["tablePrefix"]}logins SET ip=?, userAgent=?, lastTime=UNIX_TIMESTAMP() WHERE cookie IS NULL AND ip=? AND memberId=?", "isii", $ip, $userAgent, $ip, $sessionMemberId);
					}
				// Otherwise, the cookie is either expired or doesn't exist.
				} else {
					// If the user chooses to "remember" their credentials, set a new cookie.
					$newCookie = null;
					if ($rememberMe) {
						// Generate cryptographically secure cookie value (32 hex characters = 128 bits entropy)
						$newCookie = bin2hex(random_bytes(16));
						// Set cookie with security flags: Secure (if HTTPS), HttpOnly, SameSite
						setSecureCookie($config["cookieName"], $newCookie, time() + $config["cookieExpire"], $config);
					// Clean up the database of any recorded (expired) cookie if there is one.
					} elseif (isset($_COOKIE[$config["cookieName"]])) {
						$cookie = $_COOKIE[$config["cookieName"]];
						$dataMemberId = (int)$data["memberId"];
						$this->db->queryPrepared("DELETE FROM {$config["tablePrefix"]}logins WHERE cookie=? AND cookie IS NOT NULL AND memberId=?", "si", $cookie, $dataMemberId);
						// Delete cookie with same security flags
						deleteSecureCookie($config["cookieName"], $config);
					}
					// Record this in the logins table.
					$dataMemberId = (int)$data["memberId"];
					if ($newCookie) {
						$this->db->queryPrepared("INSERT INTO {$config["tablePrefix"]}logins (cookie, ip, userAgent, memberId, firstTime, lastTime) VALUES (?, ?, ?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())", "sisi", $newCookie, $ip, $userAgent, $dataMemberId);
					} else {
						$this->db->queryPrepared("INSERT INTO {$config["tablePrefix"]}logins (cookie, ip, userAgent, memberId, firstTime, lastTime) VALUES (NULL, ?, ?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())", "isi", $ip, $userAgent, $dataMemberId);
					}
				}
			}

			// Ensure session is written before redirect to ensure new session ID cookie is sent
			// PHP saves sessions automatically at script end, but we want to ensure it's saved now
			// so the cookie header is sent with the redirect response
			if (!defined("AJAX_REQUEST")) {
				session_write_close();
				refresh();
			}
			return true;
		}

		// If the user was intentionally logging in but it didn't work, show an incorrect login details error.
		if (!isset($cookie)) $this->message("incorrectLogin", false);

	}
	
	// Didn't completely fill out the login form? Return an error.
	elseif ($name or $password)	$this->message("incorrectLogin", false);
		
	return false;
}

// Log the user out.
function logout()
{
	global $config;
	$memberId = isset($_SESSION["user"]["memberId"]) ? $_SESSION["user"]["memberId"] : 0;
	$ip = cookieIp();
	
	// Destroy session data and regenerate the unique token.
	unset($_SESSION["user"]);
	regenerateToken();

	// Delete the login record from the logins table.
	if (isset($_COOKIE[$config["cookieName"]])) {
		$cookie = $_COOKIE[$config["cookieName"]];
		$this->db->queryPrepared("DELETE FROM {$config["tablePrefix"]}logins WHERE cookie=? AND cookie IS NOT NULL AND memberId=?", "si", $cookie, $memberId);
	} else {
		$this->db->queryPrepared("DELETE FROM {$config["tablePrefix"]}logins WHERE cookie IS NULL AND ip=? AND memberId=?", "ii", $ip, $memberId);
	}
	if (isset($_COOKIE[$config["cookieName"]])) {
		// Delete cookie with same security flags as when it was set
		deleteSecureCookie($config["cookieName"], $config);
	}

	$this->callHook("logout");

	// Redirect to the home page.
	redirect("");
}

// Validate $token against the actual token, $_SESSION["token"]. If it's incorrect, show a message.
function validateToken($token)
{
	if ($token != $_SESSION["token"]) {
		$this->message("noPermission");
		return false;
	} else return true;
}

// Fetch forum statistics, returning them in an array of key => statistic_text.
function getStatistics()
{
	global $config, $language;
	$result = $this->db->query("SELECT (SELECT COUNT(*) FROM {$config["tablePrefix"]}posts),
		(SELECT COUNT(*) FROM {$config["tablePrefix"]}conversations),
		(SELECT COUNT(*) FROM {$config["tablePrefix"]}members),
		(SELECT COUNT(*) FROM {$config["tablePrefix"]}members WHERE UNIX_TIMESTAMP()-{$config["userOnlineExpire"]}<lastSeen)");
	list($posts, $conversations, $membersList, $membersOnline) = $this->db->fetchRow($result);
	$result = array(
		"posts" => number_format($posts) . " {$language["posts"]}",
		"conversations" => number_format($conversations) . " {$language["conversations"]}",
		"membersOnline" => (!empty($config["onlineMembers"]) ? number_format($membersOnline) . " " . "<a href='" . makeLink("online") . "'>" . ($language[$membersOnline == 1 ? "member online" : "members online"]) . "</a>" : number_format($membersList) . " " . lcfirst($language[$membersList == 1 ? "Member" : "Member-plural"]))
	);
	$this->callHook("getStatistics", array(&$result));
	
	return $result;
}

// Get an array of language packs from the languages/ directory.
function getLanguages()
{
	$languages = array();
	if ($handle = opendir("languages")) {
		while (false !== ($v = readdir($handle))) {
			if (!in_array($v, array(".", "..")) and substr($v, -4) == ".php" and $v[0] != ".") {
				$v = substr($v, 0, strrpos($v, "."));
				$languages[] = $v;
			}
		}
	}
	sort($languages);
	return $languages;
}

// Get the installed skins and their details by reading the skins/ directory.
function getSkins()
{
	global $language, $config;
	$skins = array();
	if ($handle = opendir("skins")) {
		while (false !== ($file = readdir($handle))) {
			// Make sure the skin is valid, and set up its class.
			if ($file[0] != "." and is_dir("skins/$file") and file_exists("skins/$file/skin.php") and (include_once "skins/$file/skin.php") and class_exists($file)) {
				// $file = substr($file, 0, strrpos($file, "."));
				$skins[] = $file;
			}
		}
		closedir($handle);
	}
	ksort($skins);
	return $skins;
}

// Check for updates to the software.
function checkForUpdates()
{
	if (defined("AJAX_REQUEST")) return;
	
	// Write this as the latest update check time, so that another update check will not be performed for 24 hours.
	writeConfigFile("config/lastUpdateCheck.php", '$lastUpdateCheck', time());
	
	// Get the latest version from geteso.org.
	if (($handle = @fopen("https://geteso.org/latestVersion.txt", "r")) === false) return;
	$latestVersion = fread($handle, 8192);
	fclose($handle);
	
	// Compare the installed version and the latest version. Show a message if there is a new version.
	if (version_compare(ESO_VERSION, $latestVersion) == -1) $latestVersion;
}

// Check the first parameter of the URL against $name, and instigate the controller it refers to if they match.
function registerController($name, $file)
{
	if (@$_GET["q1"] == $name) {
		require_once $file;
		$this->action = $name;
		$this->controller = new $name;
		$this->controller->eso =& $this;
	}
}

// Halt page execution and show a fatal error message.
function fatalError($message, $esoSearch = "")
{
	global $language, $config;
	$this->callHook("fatalError", array(&$message));
	if (defined("AJAX_REQUEST")) {
		header("HTTP/1.0 500 Internal Server Error");
		echo strip_tags("{$language["Fatal error"]} - $message");
	} else {
		$messageTitle = isset($language["Fatal error"]) ? $language["Fatal error"] : "Fatal error";
		$messageBody = sprintf($language["fatalErrorMessage"], $esoSearch) . ($message ? "<div class='info'>$message</div>" : "");
		include "views/message.php";
	}
	exit;
}

// Add a message to the messages language definition array.
function addMessage($key, $class, $message)
{
	global $messages;
	if (!isset($messages[$key])) $messages[$key] = array("class" => $class, "message" => $message);
	return $key;
}

// Display a message (referred to by $key) on the page. $arguments will be used to fill out placeholders in a message.
function message($key, $disappear = true, $arguments = false)
{
	$this->callHook("message", array(&$key, &$disappear, &$arguments));
	$_SESSION["messages"][] = array("message" => $key, "arguments" => $arguments, "disappear" => $disappear);
}

// Generate the HTML of a single message.
function htmlMessage($key, $arguments = false)
{
	global $messages;
	$m = $messages[$key];
	if (!empty($arguments)) $m["message"] = is_array($arguments) ? vsprintf($m["message"], $arguments) : sprintf($m["message"], $arguments);
	return "<div class='msg {$m["class"]}'>{$m["message"]}</div>";
}

// Generate the HTML of all of the collected messages to be displayed at the top of the page.
function getMessages()
{
	global $messages;
	
	// Loop through the messages and append the HTML of each one.
	$html = "<div id='messages'>";
	foreach ($_SESSION["messages"] as $m) $html .= $this->htmlMessage($m["message"], $m["arguments"]) . "\n";
	$html .= "</div>";
	
	// Add JavaScript code to register the messages individually in the Messages object.
	$html .= "<script type='text/javascript'>
Messages.init();";
	foreach ($_SESSION["messages"] as $m) {
		if (!empty($m["arguments"])) $text = is_array($m["arguments"]) ? vsprintf($messages[$m["message"]]["message"], $m["arguments"]) : sprintf($messages[$m["message"]]["message"], $m["arguments"]);
		else $text = $messages[$m["message"]]["message"];
		$html .= "Messages.showMessage(\"{$m["message"]}\", \"{$messages[$m["message"]]["class"]}\", \"" . escapeDoubleQuotes($text) . "\", " . ($m["disappear"] ? "true" : "false") . ");\n";
	}
	$html .= "</script>";
	
	return $html;
}

// Add a definition to the language array, but only if it has not already been defined.
function addLanguage($key, $value)
{
	global $language;
	$definition =& $language;
	foreach ((array)$key as $k) $definition =& $definition[$k];
	if (isset($definition)) return false;
	$definition = $value;
}

// Set a language definition(s) to be accessible by JavaScript code on the page.
function addLanguageToJS()
{
	global $language;
	$args = func_get_args();
	foreach ($args as $key) {
		$definition =& $language;
		foreach ((array)$key as $k) $definition =& $definition[$k];
		$this->jsLanguage[$k] =& $definition;
	}
}

// Set a JavaScript variable so it can be accessed by JavaScript code on the page.
function addVarToJS($key, $val)
{
	$this->jsVars[$key] = $val;
}

// Add a JavaScript file to be included in the page.
function addScript($script, $position = false)
{
	if (in_array($script, $this->scripts)) return false;
	addToArray($this->scripts, $script, $position);
}

// Add a string of HTML to be outputted inside of the <head> tag.
function addToHead($string)
{
	$this->head .= "\n$string";
}

// Generate all of the HTML to be outputted inside of the <head> tag.
function head()
{
	global $config, $language;
	
	$head = "<!-- This page was generated by esoBB (https://geteso.org) -->\n";
	
	// Base URL and Atom Feeds.
	$head .= "<base href='{$config["baseURL"]}'/>\n";
	$head .= "<link href='{$config["baseURL"]}" . makeLink("feed") . "' rel='alternate' type='application/atom+xml' title='{$language["Recent posts"]}'/>\n";
	if ($this->action == "conversation" and !empty($this->controller->conversation["id"]))
		$head .= "<link href='{$config["baseURL"]}" . makeLink("feed", "conversation", $this->controller->conversation["id"]) . "' rel='alternate' type='application/atom+xml' title='\"{$this->controller->conversation["title"]}\"'/>";

	// Stylesheets.
	ksort($this->styleSheets);
	foreach ($this->styleSheets as $styleSheet) {
		// If media is ie6 or ie7, use conditional comments.
		if ($styleSheet["media"] == "ie6" or $styleSheet["media"] == "ie7")
			$head .= "<!--[if " . ($styleSheet["media"] == "ie6" ? "lte IE 6" : "IE 7") . "]><link rel='stylesheet' href='{$styleSheet["href"]}' type='text/css'/><![endif]-->\n";
		// If not, use media as an attribute for the link tag.
		else $head .= "<link rel='stylesheet' href='{$styleSheet["href"]}' type='text/css'" . (!empty($styleSheet["media"]) ? " media='{$styleSheet["media"]}'" : "") . "/>\n";
	}

	// Custom favicon if any or skin favicon.
	$head .= "<link rel='shortcut icon' type='image/ico' href='" . (!empty($config["shortcutIcon"]) ? $config["shortcutIcon"] : "skins/{$config["skin"]}/" . (isset($this->skin->favicon) ? $this->skin->favicon : "favicon.ico")) . "'/>";

	// JavaScript: add the scripts collected in the $this->scripts array (via $this->addScript()).
 	ksort($this->scripts);
 	foreach ($this->scripts as $script) $head .= "<script type='text/javascript' src='$script'></script>\n";

 	// Conditional browser comments to detect IE.
 	$head .= "<!--[if lte IE 6]><script type='text/javascript' src='js/ie6TransparentPNG.js'></script><script type='text/javascript'>var isIE6=true</script><![endif]-->\n<!--[if IE 7]><script type='text/javascript'>var isIE7=true</script><![endif]-->";

 	// Output all necessary config variables and language definitions, as well as other variables.
	$esoJS = array(
		"baseURL" => $config["baseURL"],
		"user" => $this->user ? $this->user["name"] : false,
		"skin" => $config["skin"],
		"colors" => $this->skin->numberOfColors,
		"avatarLeft" => isset($this->skin->avatarLeft) ? $this->skin->avatarLeft : "avatarLeft.svg",
		"avatarRight" => isset($this->skin->avatarRight) ? $this->skin->avatarRight : "avatarRight.svg",
		"avatarThumb" => isset($this->skin->avatarThumb) ? $this->skin->avatarThumb : "avatarThumb.svg",
		"disableAnimation" => !empty($this->eso->user["disableJSEffects"]),
		"disableLinkAlerts" => !empty($this->eso->user["disableLinkAlerts"]),
		"avatarAlignment" => !empty($this->eso->user["avatarAlignment"]) ? $this->eso->user["avatarAlignment"] : $_SESSION["avatarAlignment"],
		"messageDisplayTime" => $config["messageDisplayTime"],
		"maxUploadSize" => $this->uploader->maxUploadSize(),
		"language" => $this->jsLanguage,
		"token" => $_SESSION["token"]
	) + $this->jsVars;
	$head .= "<script type='text/javascript'>// <![CDATA[
var eso=" . json($esoJS) . ",isIE6,isIE7// ]]></script>\n";
	
	// Finally, append the custom HTML string constructed via $this->addToHead().
	$head .= $this->head;
	
	$this->callHook("head", array(&$head));

	return $head;
}

// Add a string of HTML to the bar.
function addToBar($side, $html, $position = false)
{
	addToArray($this->bar[$side], $html, $position);
}

// Add a string of HTML to the page footer.
function addToFooter($html, $position = false)
{
	addToArray($this->footer, $html, $position);
}

// Add a CSS file to be included on the page.
function addCSS($styleSheet, $media = false, $position = false) 
{
	if (in_array(array("href" => $styleSheet, "media" => $media), $this->styleSheets)) return false;
	addToArray($this->styleSheets, array("href" => $styleSheet, "media" => $media), $position);
}

// Star/unstar a conversation.
function star($conversationId)
{
	if (!$this->user) return false;

	global $config;	
	$conversationId = (int)$conversationId;
	
	// If this is a new conversation (ie. no ID is specified), toggle the SESSION star variable.
	if (!$conversationId) $_SESSION["starred"] = !@$_SESSION["starred"];
	
	// Otherwise, toggle the database star field.
	else {
		$memberId = (int)$this->user["memberId"];
		$this->db->queryPrepared("INSERT INTO {$config["tablePrefix"]}status (conversationId, memberId, starred) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE starred=IF(starred=1,0,1)", "ii", $conversationId, $memberId);
		
		$this->callHook("star", array($conversationId));
	}
}

// Generate the HTML for a star.
function htmlStar($conversationId, $starred)
{
	global $language;
	
	// If the user is not logged in, return a blank star.
	if (!$this->user) return "<span class='star0'>&nbsp;</span>";
	
	// Otherwise, return a clickable star, depending on the starred state.
	else {
		$conversationId = (int)$conversationId;
		return "<a href='" . makeLink(@$_GET["q1"], @$_GET["q2"], @$_GET["q3"], "?star=$conversationId", "&token={$_SESSION["token"]}") . "' onclick='toggleStar($conversationId, this);return false' class='star" . ($starred ? "1" : "0") . "'>{$language["*"]}<span> " . ($starred ? $language["Starred"] : $language["Unstarred"]) . "</span></a>";
	}
}

// Return the path to a user's avatar, depending on its format.
function getAvatar($memberId, $avatarFormat, $type = false)
{
	if ($return = $this->callHook("getAvatar", array($memberId, $avatarFormat, $type))) return $return;
	
	// If this is a full-sized gif avatar, we need to render it via g.php for security purposes.
	if ($avatarFormat == "gif" and $type != "thumb" and file_exists("avatars/$memberId.gif")) {
	 	return "avatars/g.php?id=$memberId";
	}
	
	// Otherwise, construct the avatar path from the provided information.
	elseif ($avatarFormat) {
		$file = "avatars/$memberId" . ($type == "thumb" ? "_thumb" : "") . ".$avatarFormat";
		if (file_exists($file)) return $file;
	}
	
	// If the user doesn't have an avatar, return the default one.
	if (!$avatarFormat) {
		global $config;
		switch ($type) {
			case "l": return "skins/{$config["skin"]}/" . (isset($this->skin->avatarLeft) ? $this->skin->avatarLeft : "avatarLeft.svg");
			case "r": return "skins/{$config["skin"]}/" . (isset($this->skin->avatarRight) ? $this->skin->avatarRight : "avatarRight.svg");
			case "thumb": return "skins/{$config["skin"]}/" . (isset($this->skin->avatarThumb) ? $this->skin->avatarThumb : "avatarThumb.svg");
		}
	}
}

// Update the user's last action.
function updateLastAction($action)
{
	if (!$this->user) return false;
	
	$this->callHook("updateLastAction", array(&$action));
	
	global $config;
	$action = substr($action, 0, 255);
	$lastSeen = time();
	$memberId = (int)$this->user["memberId"];
	$this->db->queryPrepared("UPDATE {$config["tablePrefix"]}members SET lastAction=?, lastSeen=? WHERE memberId=?", "sii", $action, $lastSeen, $memberId);
	$this->user["lastSeen"] = $_SESSION["user"]["lastSeen"] = $lastSeen;
	$this->user["lastAction"] = $_SESSION["user"]["lastAction"] = $action;
}

// Update user role flags based on account type
// This ensures consistency when account changes or is refreshed from database
function updateUserRoleFlags($account = null)
{
	if (!$this->user) return;
	
	// If account not provided, use current account from user object
	if ($account === null) {
		$account = $this->user["account"];
	}
	
	// Update all role flags based on account
	$this->user["admin"] = ($account == "Administrator");
	$this->user["moderator"] = ($account == "Moderator" || $account == "Administrator");
	$this->user["member"] = ($account == "Member");
	$this->user["suspended"] = ($account == "Suspended" ? true : null);
	$this->user["unvalidated"] = ($account == "Unvalidated" ? true : false);
	
	$this->callHook("updateUserRoleFlags", array(&$this->user, $account));
}

// Change a member's group.
function changeMemberGroup($memberId, $newGroup, $currentGroup = false)
{
	global $config;
	$memberId = (int)$memberId;
	
	// Make sure we have the member's current group (if it wasn't passed as an argument.)
	if (!$currentGroup) {
		$currentGroup = $this->db->fetchOne("SELECT account FROM {$config["tablePrefix"]}members WHERE memberId=?", "i", $memberId);
	}
	
	// Determine which groups the member can be changed to.
	if (!($possibleGroups = $this->canChangeGroup($memberId, $currentGroup)) or !in_array($newGroup, $possibleGroups)) return false;
	
	$this->callHook("changeMemberGroup", array(&$newGroup));
	
	// Change the group!
	$this->db->queryPrepared("UPDATE {$config["tablePrefix"]}members SET account=? WHERE memberId=?", "si", $newGroup, $memberId);
	
	// If this is the currently logged-in user, update their session and regenerate session ID
	if ($this->user && $this->user["memberId"] == $memberId && $currentGroup != $newGroup) {
		$_SESSION["user"]["account"] = $newGroup;
		$this->user["account"] = $newGroup;
		$this->updateUserRoleFlags($newGroup);
		regenerateToken();
	}
}

// To change $member's group $this->user must be an admin and $member != rootAdmin and $member != $this->user.
// If $this->user is a moderator and $member's $group is member or suspended, the group can be changed between member/suspended.
// This function will return an array of groups $member can be changed to.
function canChangeGroup($memberId, $group)
{
	global $config;
	if (!$this->user or !$this->user["moderator"] or $memberId == $this->user["memberId"] or $memberId == $config["rootAdmin"]) return false;
//	if ($this->user["admin"]) return $this->memberGroups;

	// If the $member's group is validated, return a complete list of $memberGroups.
	// Administrator, Moderator, Member, and Suspended.
	if ($this->user["admin"] and ($group != "Unvalidated")) return $this->memberGroups;
	//
	// If their $member's group is unvalidated, return that same list but add "Unvalidated."
	if ($this->user["admin"] and ($group == "Unvalidated")) {
		$this->memberGroups[5] = "Unvalidated";
		return $this->memberGroups;
	}

	// Moderators don't get to choose from a complete list.
	if ($this->user["moderator"] and ($group == "Member" or $group == "Suspended")) {
		return array("Member", "Suspended");
	//
	// Again, let's not forget about the unvalidated group...
	} elseif ($this->user["moderator"] and ($group == "Unvalidated")) {
		return array("Member", "Suspended", "Unvalidated");
	} else {
		return false;
	}
}

// Returns whether or not the logged in user is suspended.
function isSuspended()
{
	global $config;
	if (!$this->user) return false;
	
	// If the user's suspension status is unknown (null), refresh from database
	if ($this->user["suspended"] === null) {
		$memberId = (int)$this->user["memberId"];
		$account = $this->db->fetchOne("SELECT account FROM {$config["tablePrefix"]}members WHERE memberId=?", "i", $memberId);
		$this->user["account"] = $_SESSION["user"]["account"] = $account;
		$this->updateUserRoleFlags($account);
	}
	return $this->user["suspended"] ?? false;
}

// Returns whether or not the logged in user has been validated or not.
// Does not return "Member", only "Unvalidated" if the user is unvalidated.
function isUnvalidated()
{
	if (!$this->user) return false;

	return $this->user["account"] == "Unvalidated";
}

}

?>
