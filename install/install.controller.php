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
 * Install controller: performs all installation tasks - checks server
 * environment, runs installation queries, creates configuration files...
 */
class Install {

var $step;
var $config;
var $errors = array();
var $queries = array();

// Initialize: perform an action depending on what step the user is at in the installation.
function init()
{
	// Determine which step we're on:
	// If there are fatal errors, then remain on the fatal error step.
	// Otherwise, use the step in the URL if it's available.
	// Otherwise, default to the start step.
	if ($this->errors = $this->fatalChecks()) $this->step = "fatalChecks";
	elseif (@$_GET["step"]) $this->step = $_GET["step"];
	else $this->step = "start";
	
	switch ($this->step) {
		
		// On the "start" step, handle language selection.
		case "start":
			global $language;
			// Get list of available languages from ../languages/ folder.
			$this->languages = array();
			if ($handle = opendir("../languages")) {
				while (false !== ($v = readdir($handle))) {
					if (!in_array($v, array(".", "..")) and substr($v, -4) == ".php" and $v[0] != ".") {
						$v = substr($v, 0, strrpos($v, "."));
						$this->languages[] = $v;
					}
				}
				closedir($handle);
			}
			
			// Handle "Next step" button click - proceed to warningChecks.
			// Check this FIRST before checking for language changes.
			if (isset($_POST["next"])) {
				// If no language in session, default to "English (casual)".
				if (empty($_SESSION["installLanguage"])) {
					$_SESSION["installLanguage"] = "English (casual)";
				}
				$this->step("warningChecks");
			}
			// If language selected via POST (but not via Next button), store in session but stay on start step.
			elseif (isset($_POST["language"])) {
				// Validate CSRF token.
				if (!isset($_POST["token"]) || $_POST["token"] !== $_SESSION["token"]) {
					$this->errors[1] = $language["Invalid security token. Please refresh the page and try again."];
					return;
				}
				
				// Validate and sanitize language name.
				$selectedLanguage = sanitizeFileName($_POST["language"]);
				if (in_array($selectedLanguage, $this->languages) && file_exists("../languages/{$selectedLanguage}.php")) {
					$_SESSION["installLanguage"] = $selectedLanguage;
					// Reload language file.
					include "../languages/{$selectedLanguage}.php";
					// Regenerate token after successful form submission.
					regenerateToken();
					// Stay on start step - page will update via JavaScript
				} else {
					$this->errors[1] = $language["Invalid security token. Please refresh the page and try again."];
				}
			}
			// If language already selected in session, skip to warningChecks.
			// But only if user didn't explicitly navigate to start step (e.g., via back button).
			elseif (!empty($_SESSION["installLanguage"]) && @$_GET["step"] != "start") {
				$this->step("warningChecks");
			}
			break;
		
		// If on the warning checks step and there are no warnings or the user has clicked "Next", go to the next step.
		case "warningChecks":
			if (!($this->errors = $this->warningChecks()) or isset($_POST["next"])) $this->step("info");
			break;
			
		
		// On the "Specify setup information" step, handle the form processing.
		case "info":
		
			// Handle back button - go back to start step.
			if (isset($_POST["back"])) {
				$this->step("start");
				return;
			}
		
			// Prepare a list of language packs in the ../languages folder.
			$this->languages = array();
			if ($handle = opendir("../languages")) {
			    while (false !== ($v = readdir($handle))) {
					if (!in_array($v, array(".", "..")) and substr($v, -4) == ".php" and $v[0] != ".") {
						$v = substr($v, 0, strrpos($v, "."));
						$this->languages[] = $v;
					}
				}
			}
			// Prepare a list of SMTP email authentication options.
			$this->smtpOptions = array(
				false => "None at all (internal email)",
				"ssl" => "SSL",
				"tls" => "TLS"
			);
			// Prepare a list of MySQL storage engines.
			$this->storageEngines = array(
				"InnoDB" => "InnoDB (recommended)",
				"MyISAM" => "MyISAM (less efficient, smaller)"
			);
			// Prepare a list of hashing algorithms.
			$this->hashingMethods = array(
				"bcrypt" => "bcrypt (recommended)",
				"md5" => "MD5 (less secure, faster)"
			);
			
			// If the form has been submitted...
			if (isset($_POST["forumTitle"])) {
				
				// Validate CSRF token.
				global $language;
				if (!isset($_POST["token"]) || $_POST["token"] !== $_SESSION["token"]) {
					$this->errors[1] = $language["Invalid security token. Please refresh the page and try again."];
					return;
				}
				
				// Validate the form data - do not continue if there were errors!
				if ($this->errors = $this->validateInfo()) return;
				
				// Put all the POST data into the session and proceed to the install step.
				$_SESSION["install"] = array(
					"forumTitle" => $_POST["forumTitle"],
					"forumDescription" => $_POST["forumDescription"],
					"language" => $_POST["language"],
					// DB settings
					"mysqlHost" => $_POST["mysqlHost"],
					"mysqlUser" => $_POST["mysqlUser"],
					"mysqlPass" => $_POST["mysqlPass"],
					"mysqlDB" => $_POST["mysqlDB"],
					// SMTP settings
					"sendEmail" => $_POST["sendEmail"],
					"smtpAuth" => $_POST["smtpAuth"],
					"smtpHost" => $_POST["smtpHost"],
					"smtpPort" => $_POST["smtpPort"],
					"smtpUser" => $_POST["smtpUser"],
					"smtpPass" => $_POST["smtpPass"],
					// Root user settings
					"adminUser" => $_POST["adminUser"],
					"adminEmail" => $_POST["adminEmail"],
					"adminPass" => $_POST["adminPass"],
					"adminConfirm" => $_POST["adminConfirm"],
					// Advanced settings
					"tablePrefix" => $_POST["tablePrefix"],
					"characterEncoding" => $_POST["characterEncoding"],
					"storageEngine" => $_POST["storageEngine"],
					"hashingMethod" => $_POST["hashingMethod"],
					"baseURL" => $_POST["baseURL"],
					"friendlyURLs" => $_POST["friendlyURLs"]
				);
				// Regenerate token after successful form submission.
				regenerateToken();
				$this->step("install");
			}
			
			// If the form hasn't been submitted but there's form data in the session, fill out the form with it.
			elseif (isset($_SESSION["install"])) $_POST = $_SESSION["install"];
			break;
			
		
		// Run the actual installation.
		case "install":
		
			// Go back to the previous step if it hasn't been completed.
			if (isset($_POST["back"]) or empty($_SESSION["install"])) $this->step("info");
			
			// Fo the installation. If there are errors, do not continue.
			if ($this->errors = $this->doInstall()) return;
			
			// Log queries to the session and proceed to the final step.
			$_SESSION["queries"] = $this->queries;
			$this->step("finish");
			break;
			
		
		// Finalise the installation and redirect to the forum.
		case "finish":
		
			// If they clicked the 'go to my forum' button, log them in as the administrator and redirect to the forum.
			if (isset($_POST["finish"])) {
				include "../config/config.php";
				initSession($config, $_SESSION["user"]);
				header("Location: ../");
				exit;
			}
			// Lock the installer using atomic file operation.
			global $language;
			if (($handle = @fopen("lock", "c")) === false) {
				$this->errors[1] = $language["Your forum can't seem to lock the installer. Please manually delete the install folder, otherwise your forum's security will be vulnerable."];
			} else {
				// Use exclusive lock to prevent race conditions.
				if (!flock($handle, LOCK_EX | LOCK_NB)) {
					fclose($handle);
					$this->errors[1] = $language["Installer is locked by another process."];
				} else {
					fwrite($handle, time() . "\n" . $_SERVER["REMOTE_ADDR"]);
					fflush($handle);
					// Keep handle open to maintain lock (will be closed when script ends).
				}
			}
	}

}

// Obtain the hardcoded version of eso (ESO_VERSION).
function getVersion()
{
	include "../config.default.php";
	$version = ESO_VERSION;
	return $version;
}

// Generate a default value for the baseURL based on server environment variables.
function suggestBaseUrl()
{
	$dir = substr($_SERVER["PHP_SELF"], 0, strrpos($_SERVER["PHP_SELF"], "/"));
	$dir = substr($dir, 0, strrpos($dir, "/"));
	if (array_key_exists("HTTPS", $_SERVER) and $_SERVER["HTTPS"] === "on") $baseURL = "https://{$_SERVER["HTTP_HOST"]}{$dir}/";
	else $baseURL = "http://{$_SERVER["HTTP_HOST"]}{$dir}/";
	return $baseURL;
}

// Generate a default value for whether or not to use friendly URLs, depending on if the REQUEST_URI variable is available.
function suggestFriendlyUrls()
{
	return !empty($_SERVER["REQUEST_URI"]);
}

// Perform a MySQL query, and log it.
public function query($link, $query)
{	
	$result = mysqli_query($link, $query);
	$this->queries[] = $query;
	return $result;
}

// Execute a prepared statement query, and log it.
public function queryPrepared($link, $query, $types, ...$params)
{
	$stmt = mysqli_prepare($link, $query);
	if (!$stmt) {
		$this->queries[] = $query . " [PREPARE ERROR: " . mysqli_error($link) . "]";
		return false;
	}
	
	// Bind parameters by reference (required by mysqli_stmt_bind_param)
	if (!empty($params)) {
		$refs = array();
		foreach ($params as $key => $value) {
			$refs[$key] = &$params[$key];
		}
		array_unshift($refs, $types);
		call_user_func_array(array($stmt, 'bind_param'), $refs);
	}
	
	// Execute the statement
	if (!mysqli_stmt_execute($stmt)) {
		$this->queries[] = $query . " [EXECUTE ERROR: " . mysqli_stmt_error($stmt) . "]";
		mysqli_stmt_close($stmt);
		return false;
	}
	
	// Log the query with parameter values for debugging
	$logQuery = $query;
	foreach ($params as $i => $param) {
		$type = isset($types[$i]) ? $types[$i] : 's';
		$value = $type == 'i' ? (int)$param : (is_string($param) ? "'" . addslashes($param) . "'" : $param);
		$logQuery = preg_replace('/\?/', $value, $logQuery, 1);
	}
	$this->queries[] = $logQuery;
	
	mysqli_stmt_close($stmt);
	return true;
}

// Fetch a sequential array.  $input can be a string or a MySQL result.
public function fetchRow($link, $input)
{
	if ($input instanceof \mysqli_result) return mysqli_fetch_row($input);
	$result = $this->query($link, $input);
	if (!$this->numRows($link, $result)) return false;
	return $this->fetchRow($link, $result);
}

// Return the number of rows in the result.  $input can be a string or a MySQL result.
public function numRows($link, $input)
{
	if (!$input) return false;
	if ($input instanceof \mysqli_result) return mysqli_num_rows($input);
	$result = $this->query($link, $input);
	return $this->numRows($link, $result);
}

// Perform the installation: run installation queries, and write configuration files.
function doInstall()
{
	global $config;
	
	// Make sure the base URL has a trailing slash.
	if (substr($_SESSION["install"]["baseURL"], -1) != "/") $_SESSION["install"]["baseURL"] .= "/";
	
	// Validate and sanitize language filename to prevent path traversal.
	$language = basename($_SESSION["install"]["language"]);
	if (!preg_match("/^[a-zA-Z0-9_() -]+$/", $language)) {
		$_SESSION["install"]["language"] = "English (casual)";
	} else {
		$_SESSION["install"]["language"] = $language;
	}
	if (!file_exists("../languages/{$_SESSION["install"]["language"]}.php")) {
		$_SESSION["install"]["language"] = "English (casual)";
	}

	// Make sure there is a character set.
	if (empty($_SESSION["install"]["characterEncoding"]))
		$_SESSION["install"]["characterEncoding"] = "utf8mb4";
	
	// Prepare the $config variable with the installation settings.
	$config = array(
		"forumTitle" => $_SESSION["install"]["forumTitle"],
		"forumDescription" => $_SESSION["install"]["forumDescription"],
		"language" => $_SESSION["install"]["language"],
		// DB settings
		"mysqlHost" => desanitize($_SESSION["install"]["mysqlHost"]),
		"mysqlUser" => desanitize($_SESSION["install"]["mysqlUser"]),
		"mysqlPass" => desanitize($_SESSION["install"]["mysqlPass"]),
		"mysqlDB" => desanitize($_SESSION["install"]["mysqlDB"]),
		// SMTP settings
		"emailFrom" => "do_not_reply@{$_SERVER["HTTP_HOST"]}",
		"sendEmail" => !empty($_SESSION["install"]["sendEmail"]),
		// Advanced settings
		"tablePrefix" => desanitize($_SESSION["install"]["tablePrefix"]),
		"characterEncoding" => desanitize($_SESSION["install"]["characterEncoding"]),
		"storageEngine" => desanitize($_SESSION["install"]["storageEngine"]),
		"hashingMethod" => desanitize($_SESSION["install"]["hashingMethod"]),
		"baseURL" => $_SESSION["install"]["baseURL"],
		"cookieName" => preg_replace(array("/\s+/", "/[^\w]/"), array("_", ""), desanitize($_SESSION["install"]["forumTitle"])),
		"useFriendlyURLs" => !empty($_SESSION["install"]["friendlyURLs"]),
		"useModRewrite" => !empty($_SESSION["install"]["friendlyURLs"]) and function_exists("apache_get_modules") and in_array("mod_rewrite", apache_get_modules())
	);
	$smtpConfig = array(
		"smtpAuth" => desanitize($_SESSION["install"]["smtpAuth"]),
		"smtpHost" => desanitize($_SESSION["install"]["smtpHost"]),
		"smtpPort" => desanitize($_SESSION["install"]["smtpPort"]),
		"smtpUser" => desanitize($_SESSION["install"]["smtpUser"]),
		"smtpPass" => desanitize($_SESSION["install"]["smtpPass"]),
	);
	if (!empty($_SESSION["install"]["smtpAuth"])) $config = array_merge($config, $smtpConfig);
	
	// Connect to the MySQL database.
	$db = @mysqli_connect($config["mysqlHost"], $config["mysqlUser"], $config["mysqlPass"], $config["mysqlDB"]);
	mysqli_set_charset($db, $config["characterEncoding"]);
	
	// Run the queries one by one and halt if there's an error!
	include "queries.php";
	
	// Execute DDL queries (CREATE TABLE, DROP TABLE, etc.) - these don't contain user data
	foreach ($queries as $query) {
		if (!$this->query($db, $query)) return array(1 => "<code>" . sanitizeHTML(mysqli_error($db)) . "</code><p><strong>The query that caused this error was</strong></p><pre>" . sanitizeHTML($query) . "</pre>");
	}
	
	// Execute prepared statement queries (contain user-provided data)
	if (isset($preparedQueries)) {
		foreach ($preparedQueries as $preparedQuery) {
			if (!$this->queryPrepared($db, $preparedQuery["query"], $preparedQuery["types"], ...$preparedQuery["params"])) {
				return array(1 => "<code>" . sanitizeHTML(mysqli_error($db)) . "</code><p><strong>The query that caused this error was</strong></p><pre>" . sanitizeHTML($preparedQuery["query"]) . "</pre>");
			}
		}
	}
	
	// Clear sensitive data from session after use.
	unset($_SESSION["install"]["mysqlPass"]);
	unset($_SESSION["install"]["adminPass"]);
	unset($_SESSION["install"]["adminConfirm"]);
	unset($_SESSION["install"]["smtpPass"]);
	
	// Write the $config variable to config.php.
	writeConfigFile("../config/config.php", '$config', $config);
	
	// Write the plugins.php file, which contains plugins enabled by default.
	$enabledPlugins = array("Emoticons");
	if ((extension_loaded("gd") or extension_loaded("gd2")) and function_exists("imagettftext"))
		$enabledPlugins[] = "Captcha";
	if (!file_exists("../config/plugins.php")) writeConfigFile(PATH_CONFIG."/plugins.php", '$config["loadedPlugins"]', $enabledPlugins);
	
	// Write the skin.php file, which contains the enabled skin, and custom.php.
	if (!file_exists("../config/skin.php")) writeConfigFile(PATH_CONFIG."/skin.php", '$config["skin"]', "Plastic");
	if (!file_exists("../config/custom.php")) writeFile(PATH_CONFIG."/custom.php", '<?php
if (!defined("IN_ESO")) exit;
// Any language declarations, messages, or anything else custom to this forum goes in this file.
// Examples:
// $language["My settings"] = "Preferences";
// $messages["incorrectLogin"]["message"] = "Oops! The login details you entered are incorrect. Did you make a typo?";
?>');
	// Write custom.css and index.html as empty files (if they're not already there.)
	if (!file_exists("../config/custom.css")) writeFile(PATH_CONFIG."/custom.css", "");
	if (!file_exists("../config/index.html")) writeFile(PATH_CONFIG."/index.html", "");
	
	// Write the versions.php file with the current version.
	include "../config.default.php";
	writeConfigFile("../config/versions.php", '$versions', array("eso" => ESO_VERSION));
	
	// Write a .htaccess file if they are using friendly URLs (and mod_rewrite).
	if ($config["useModRewrite"]) {
		writeFile(PATH_ROOT."/.htaccess", "# Generated by esoBB (https://geteso.org)
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php/$1 [QSA,L]
</IfModule>");
	}
	
	// Write a robots.txt file.
	writeFile(PATH_ROOT."/robots.txt", "User-agent: *
Disallow: /search/
Disallow: /online/
Disallow: /join/
Disallow: /forgot-password/
Disallow: /conversation/new/
Disallow: /site.webmanifest/
Sitemap: {$config["baseURL"]}sitemap.php");
	
	// Prepare to log in the administrator.
	// Don't actually log them in, because the current session gets renamed during the final step.
	$_SESSION["user"] = array(
		"memberId" => 1,
		"name" => $_SESSION["install"]["adminUser"],
		"account" => "Administrator",
		"color" => $color,
		"emailOnPrivateAdd" => false,
		"emailOnStar" => false,
		"language" => $_SESSION["install"]["language"],
		"avatarAlignment" => "alternate",
		"avatarFormat" => "",
		"disableJSEffects" => false,
		"disableLinkAlerts" => false
	);
}

// Validate the information entered in the 'Specify setup information' form.
function validateInfo()
{
	global $language;
	$errors = array();

	// Forum title must contain at least one character.
	if (!strlen($_POST["forumTitle"])) $errors["forumTitle"] = $language["Your forum title must consist of at least one character"];

	// Forum description also must contain at least one character.
	if (!strlen($_POST["forumDescription"])) $errors["forumDescription"] = $language["Your forum description must consist of at least one character"];
	
	// Username must not be reserved, and must not contain special characters.
	if (in_array(strtolower($_POST["adminUser"]), array("guest", "member", "members", "moderator", "moderators", "administrator", "administrators", "suspended", "everyone", "myself"))) $errors["adminUser"] = $language["The name you have entered is reserved and cannot be used"];
	if (!strlen($_POST["adminUser"])) $errors["adminUser"] = $language["You must enter a name"];
	if (preg_match("/[" . preg_quote("!/%+-", "/") . "]/", $_POST["adminUser"])) $errors["adminUser"] = $language["You can't use any of these characters in your name: ! / % + -"];
	
	// Email must be valid.
	if (!preg_match("/^[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i", $_POST["adminEmail"])) $errors["adminEmail"] = $language["You must enter a valid email address"];
	
	// Password must be at least 6 characters.
	if (strlen($_POST["adminPass"]) < 6) $errors["adminPass"] = $language["Your password must be at least 6 characters"];
	
	// Password confirmation must match.
	if ($_POST["adminPass"] != $_POST["adminConfirm"]) $errors["adminConfirm"] = $language["Your passwords do not match"];
	
	// Validate table prefix format and length.
	if (!preg_match("/^[a-zA-Z0-9_]+$/", $_POST["tablePrefix"])) {
		$errors["tablePrefix"] = $language["Table prefix can only contain letters, numbers, and underscores"];
	}
	if (strlen($_POST["tablePrefix"]) > 20) {
		$errors["tablePrefix"] = $language["Table prefix must be 20 characters or less"];
	}
	if (strlen($_POST["tablePrefix"]) < 1) {
		$errors["tablePrefix"] = $language["Table prefix must be at least 1 character"];
	}
	
	// Validate character encoding.
	$allowedEncodings = array("utf8", "utf8mb4", "latin1");
	if (!empty($_POST["characterEncoding"]) && !in_array(strtolower($_POST["characterEncoding"]), $allowedEncodings)) {
		$errors["characterEncoding"] = $language["Character encoding must be one of"] . " " . implode(", ", $allowedEncodings);
	}
	
	// Validate base URL format.
	if (!empty($_POST["baseURL"]) && !filter_var($_POST["baseURL"], FILTER_VALIDATE_URL)) {
		$errors["baseURL"] = $language["Base URL must be a valid URL"];
	}
	
	// Validate SMTP port if provided.
	if (!empty($_POST["smtpPort"]) && (!is_numeric($_POST["smtpPort"]) || $_POST["smtpPort"] < 1 || $_POST["smtpPort"] > 65535)) {
		$errors["smtpPort"] = $language["SMTP port must be a number between 1 and 65535"];
	}
	
	// Validate storage engine.
	$allowedEngines = array("InnoDB", "MyISAM");
	if (!empty($_POST["storageEngine"]) && !in_array($_POST["storageEngine"], $allowedEngines)) {
		$errors["storageEngine"] = $language["Storage engine must be InnoDB or MyISAM"];
	}
	
	// Validate hashing method.
	$allowedMethods = array("bcrypt", "md5");
	if (!empty($_POST["hashingMethod"]) && !in_array($_POST["hashingMethod"], $allowedMethods)) {
		$errors["hashingMethod"] = $language["Hashing method must be bcrypt or md5"];
	}
	
	// Try and connect to the database.
	$db = @mysqli_connect($_POST["mysqlHost"], $_POST["mysqlUser"], $_POST["mysqlPass"], $_POST["mysqlDB"]);
	if (!$db) $errors["mysql"] = $language["The installer could not connect to the MySQL server. The error returned was"] . "<br/> " . mysqli_connect_error();
	
	// Check to see if there are any conflicting tables already in the database.
	// If there are, show an error with a hidden input. If the form is submitted again with this hidden input,
	// proceed to perform the installation regardless.
	elseif ($_POST["tablePrefix"] != @$_POST["confirmTablePrefix"] and !count($errors)) {
		$theirTables = array();
		$result = $this->query($db, "SHOW TABLES");
		while (list($table) = $this->fetchRow($db, $result)) $theirTables[] = $table;
		$ourTables = array("{$_POST["tablePrefix"]}conversations", "{$_POST["tablePrefix"]}posts", "{$_POST["tablePrefix"]}status", "{$_POST["tablePrefix"]}members", "{$_POST["tablePrefix"]}tags");
		$conflictingTables = array_intersect($ourTables, $theirTables);
		if (count($conflictingTables)) {
			$_POST["showAdvanced"] = true;
			$errors["tablePrefix"] = "The installer has detected that there is another installation of the software in the same MySQL database with the same table prefix. The conflicting tables are: <code>" . implode(", ", $conflictingTables) . "</code>.<br/><br/>To overwrite this installation, click 'Next step' again. <strong>All data will be lost.</strong><br/><br/>If you wish to create another installation alongside the existing one, <strong>change the table prefix</strong>.<input type='hidden' name='confirmTablePrefix' value='{$_POST["tablePrefix"]}'/>";
		}
	}
	
	if (count($errors)) return $errors;
}

// Redirect to a specific step.
function step($step)
{
	header("Location: index.php?step=$step");
	exit;
}

// Check for fatal errors.
function fatalChecks()
{
	$errors = array();
	
	// Make sure the installer is not locked.
	global $language;
	if (@$_GET["step"] != "finish" && file_exists("lock")) {
		// Try to read lock file to verify it's valid.
		$lockContent = @file_get_contents("lock");
		if ($lockContent !== false) {
			$errors[] = $language["Your forum is already installed. To reinstall your forum, you must remove install/lock."];
		}
	}
	
	// Check the PHP version.
	if (!version_compare(PHP_VERSION, "7.2.0", ">=")) $errors[] = "Your server must have <strong>PHP 7.2.0 or greater</strong> installed to run your forum.<br/><small>Please upgrade your PHP installation or request that your host or administrator upgrade the server.</small>";
	
	// Check for the MySQLi extension.
	if (!extension_loaded("mysqli")) $errors[] = "You must have <strong>MySQL 5.7 or greater</strong> installed and the <a href='https://php.net/manual/en/mysqli.installation.php' target='_blank'>MySQLi extension enabled in PHP</a>.<br/><small>Please install/upgrade both of these requirements or request that your host or administrator install them.</small>";
	
	// Check file permissions.
	$fileErrors = array();
	$filesToCheck = array("", "avatars/", "plugins/", "skins/", "config/", "install/", "upgrade/");
	foreach ($filesToCheck as $file) {
		if ((!file_exists("../$file") and !@mkdir("../$file")) or (!is_writable("../$file") and !@chmod("../$file", 0777))) {
			$realPath = realpath("../$file");
			$fileErrors[] = $file ? $file : substr($realPath, strrpos($realPath, "/") + 1) . "/";
		}
	}
	if (count($fileErrors)) $errors[] = "The following files/folders are not writeable: <strong>" . implode("</strong>, <strong>", $fileErrors) . "</strong>.<br/><small>To resolve this, you must navigate to these files/folders in your FTP client and <strong>chmod</strong> them to <strong>777</strong> or <strong>755</strong> (recommended).</small>";
	
	// Check for PCRE UTF-8 support.
	if (!@preg_match("//u", "")) $errors[] = "<strong>PCRE UTF-8 support</strong> is not enabled.<br/><small>Please ensure that your PHP installation has PCRE UTF-8 support compiled into it.</small>";
	
	// Check for the gd extension.
	if (!extension_loaded("gd") and !extension_loaded("gd2")) $errors[] = "The <strong>GD extension</strong> is not enabled.<br/><small>This is required to save avatars and generate captcha images. Get your host or administrator to install/enable it.</small>";
	
	if (count($errors)) return $errors;
}

// Perform checks which will throw a warning.
function warningChecks()
{
	$errors = array();
	
	// We don't like register_globals!
	if (ini_get("register_globals")) $errors[] = "PHP's <strong>register_globals</strong> setting is enabled.<br/><small>While your forum can run with this setting on, it is recommended that it be turned off to increase security and to prevent your forum from having problems.</small>";
	
	// Can we open remote URLs as files?
	if (!ini_get("allow_url_fopen")) $errors[] = "The PHP setting <strong>allow_url_fopen</strong> is not on.<br/><small>Without this, avatars cannot be uploaded directly from remote websites.</small>";
	
	// Check for safe_mode.
	if (ini_get("safe_mode")) $errors[] = "<strong>Safe mode</strong> is enabled.<br/><small>This could potentially cause problems with your forum, but you can still proceed if you cannot turn it off.</small>";
	
	if (count($errors)) return $errors;
}

// Helper function to format validation error messages as HTML.
function htmlMessage($text) {
	return "<div class='msg warning'>" . htmlspecialchars($text, ENT_QUOTES, "UTF-8") . "</div>";
}

// Initialize forum session with proper configuration and required fields.
// Transitions from installer session to forum session for auto-login after installation.
function initSession($config, $user) {
	// Destroy current installer session
	session_destroy();
	
	// Set session name to match forum's cookie name
	session_name("{$config["cookieName"]}_Session");
	
	// Configure session cookie parameters (matching lib/init.php)
	$lifetime = 0; // Session cookie (expires when browser closes)
	$path = "/";
	$domain = $config["cookieDomain"] ? $config["cookieDomain"] : "";
	$secure = !empty($config["https"]);
	$httponly = true;
	
	// session_set_cookie_params() array syntax requires PHP 7.3.0+
	if (PHP_VERSION_ID >= 70300) {
		session_set_cookie_params(array(
			"lifetime" => $lifetime,
			"path" => $path,
			"domain" => $domain,
			"secure" => $secure,
			"httponly" => $httponly,
			"samesite" => "Lax"
		));
	} else {
		// PHP 7.2.x compatibility: use individual parameters
		// Note: SameSite cannot be set for session cookies in PHP 7.2.x via session_set_cookie_params()
		session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
	}
	
	// Start new forum session
	session_start();
	
	// Set user data
	$_SESSION["user"] = $user;
	
	// Set required session fields that the main application expects
	$_SESSION["ip"] = $_SERVER["REMOTE_ADDR"];
	$_SESSION["time"] = time();
	$_SESSION["userAgent"] = md5($_SERVER["HTTP_USER_AGENT"]);
	
	// Generate token if needed (regenerateToken() will also update ip/time/userAgent, but that's fine)
	if (empty($_SESSION["token"])) regenerateToken();
}

// Test database connection with rate limiting.
// Returns array with "validated" (bool) and "message" (string).
// This function is available for later implementation (e.g., optional connection testing).
function testDatabaseConnection($mysqlHost, $mysqlUser, $mysqlPass, $mysqlDB)
{
	global $language;
	
	// Rate limiting: check last validation time.
	$lastCheck = @$_SESSION["install"]["lastDbCheck"];
	$cachedResult = @$_SESSION["install"]["dbCheckResult"];
	
	// If checked within last 5 seconds, return cached result.
	if ($lastCheck && (time() - $lastCheck) < 5 && isset($cachedResult)) {
		return $cachedResult;
	}
	
	// Attempt connection.
	$db = @mysqli_connect($mysqlHost, $mysqlUser, $mysqlPass, $mysqlDB);
	if (!$db) {
		$result = array("validated" => false, "message" => htmlMessage($language["The installer could not connect to the MySQL server. The error returned was"] . " " . mysqli_connect_error()));
	} else {
		$result = array("validated" => true, "message" => "");
		mysqli_close($db);
	}
	
	// Cache result.
	$_SESSION["install"]["lastDbCheck"] = time();
	$_SESSION["install"]["dbCheckResult"] = $result;
	
	return $result;
}

// Handle AJAX validation requests.
function ajax()
{
	global $language;
	
	if (empty($_POST["action"])) {
		return null;
	}
	
	// Handle language change via AJAX.
	if ($_POST["action"] == "changeLanguage") {
		// Validate CSRF token.
		if (!isset($_POST["token"]) || $_POST["token"] !== $_SESSION["token"]) {
			return array("success" => false, "message" => $language["Invalid security token. Please refresh the page and try again."]);
		}
		
		// Get list of available languages.
		$languages = array();
		if ($handle = opendir("../languages")) {
			while (false !== ($v = readdir($handle))) {
				if (!in_array($v, array(".", "..")) and substr($v, -4) == ".php" and $v[0] != ".") {
					$v = substr($v, 0, strrpos($v, "."));
					$languages[] = $v;
				}
			}
			closedir($handle);
		}
		
		// Validate and sanitize language name.
		$selectedLanguage = sanitizeFileName($_POST["language"]);
		if (in_array($selectedLanguage, $languages) && file_exists("../languages/{$selectedLanguage}.php")) {
			$_SESSION["installLanguage"] = $selectedLanguage;
			// Reload language file.
			include "../languages/{$selectedLanguage}.php";
			// Regenerate token after successful change.
			regenerateToken();
			return array("success" => true, "token" => $_SESSION["token"]);
		} else {
			return array("success" => false, "message" => $language["Invalid security token. Please refresh the page and try again."]);
		}
	}
	
	if ($_POST["action"] != "validate") {
		return null;
	}
	
	$field = @$_POST["field"];
	$value = @$_POST["value"];
	
	switch ($field) {
		case "forumTitle":
			if (!strlen($value)) {
				return array("validated" => false, "message" => $this->htmlMessage($language["Your forum title must consist of at least one character"]));
			}
			return array("validated" => true, "message" => "");
			
		case "forumDescription":
			if (!strlen($value)) {
				return array("validated" => false, "message" => $this->htmlMessage($language["Your forum description must consist of at least one character"]));
			}
			return array("validated" => true, "message" => "");
			
		case "adminUser":
			if (!strlen($value)) {
				return array("validated" => false, "message" => $this->htmlMessage($language["You must enter a name"]));
			}
			if (in_array(strtolower($value), array("guest", "member", "members", "moderator", "moderators", "administrator", "administrators", "suspended", "everyone", "myself"))) {
				return array("validated" => false, "message" => $this->htmlMessage($language["The name you have entered is reserved and cannot be used"]));
			}
			if (preg_match("/[" . preg_quote("!/%+-", "/") . "]/", $value)) {
				return array("validated" => false, "message" => $this->htmlMessage($language["You can't use any of these characters in your name: ! / % + -"]));
			}
			return array("validated" => true, "message" => "");
			
		case "adminEmail":
			if (!preg_match("/^[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i", $value)) {
				return array("validated" => false, "message" => $this->htmlMessage($language["You must enter a valid email address"]));
			}
			return array("validated" => true, "message" => "");
			
		case "adminPass":
			if (strlen($value) < 6) {
				return array("validated" => false, "message" => $this->htmlMessage($language["Your password must be at least 6 characters"]));
			}
			return array("validated" => true, "message" => "");
			
		case "adminConfirm":
			// Check for password from JavaScript (sent as "password" parameter) or fallback to "adminPass"
			$adminPass = @$_POST["password"] ?: @$_POST["adminPass"] ?: "";
			if ($value != $adminPass) {
				return array("validated" => false, "message" => $this->htmlMessage($language["Your passwords do not match"]));
			}
			return array("validated" => true, "message" => "");
			
		case "mysqlHost":
		case "mysqlUser":
		case "mysqlDB":
		case "mysqlPass":
			// Basic format check - just ensure it's not empty (mysqlPass can be empty).
			// Note: Database connection testing has been moved to testDatabaseConnection() 
			// for later implementation, so validation only checks if fields have values.
			if ($field != "mysqlPass" && !strlen($value)) {
				return array("validated" => false, "message" => "");
			}
			// Field has a value (or is mysqlPass which can be empty), so it's valid.
			return array("validated" => true, "message" => "");
			
		case "tablePrefix":
			if (!strlen($value)) {
				return array("validated" => false, "message" => $this->htmlMessage($language["Table prefix must be at least 1 character"]));
			}
			if (!preg_match("/^[a-zA-Z0-9_]+$/", $value)) {
				return array("validated" => false, "message" => $this->htmlMessage($language["Table prefix can only contain letters, numbers, and underscores"]));
			}
			if (strlen($value) > 20) {
				return array("validated" => false, "message" => $this->htmlMessage($language["Table prefix must be 20 characters or less"]));
			}
			return array("validated" => true, "message" => "");
			
		case "characterEncoding":
			$allowedEncodings = array("utf8", "utf8mb4", "latin1");
			if (!empty($value) && !in_array(strtolower($value), $allowedEncodings)) {
				return array("validated" => false, "message" => $this->htmlMessage($language["Character encoding must be one of"] . " " . implode(", ", $allowedEncodings)));
			}
			return array("validated" => true, "message" => "");
			
		case "baseURL":
			if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
				return array("validated" => false, "message" => $this->htmlMessage($language["Base URL must be a valid URL"]));
			}
			return array("validated" => true, "message" => "");
			
		case "smtpPort":
			if (!empty($value) && (!is_numeric($value) || $value < 1 || $value > 65535)) {
				return array("validated" => false, "message" => $this->htmlMessage($language["SMTP port must be a number between 1 and 65535"]));
			}
			return array("validated" => true, "message" => "");
	}
	
	return null;
}

}

?>
