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
 * Upgrade controller: performs necessary upgrade(s) and displays the
 * upgrade interface.
 */
class Upgrade extends Database {

function __construct()
{
	// Connect to the database.
	global $config;
	parent::__construct($config["mysqlHost"], $config["mysqlUser"], $config["mysqlPass"], $config["mysqlDB"]);
	if ($this->connectError())
		$this->fatalError($this->connectError());
}

function init()
{
	// Make sure the versions file is writable.
	if (!is_writeable("../config/versions.php"))
		$this->fatalError("<code>config/versions.php</code> is not writeable. Try <code>chmod</code>ing it to <code>777</code>, or if it doesn't exist, <code>chmod</code> the folder it is contained within.");
	
	// Perform the upgrade, depending on what version the user is currently at.
	global $config, $versions;

	// esoTalk 1.0.0 alpha 5 -> esoTalk 1.0.0 beta 1
	if ($versions["eso"] == "1.0.0a5") {
		$this->upgrade_100b1();
		$versions["eso"] = "1.0.0b1";
		writeConfigFile("../config/versions.php", '$versions', $versions);
	}
	
	// esoTalk 1.0.0 beta 1 -> esoTalk 1.0.0 beta 2
	if ($versions["eso"] == "1.0.0b1") {
		$this->upgrade_100b2();
		$versions["eso"] = "1.0.0b2";
		writeConfigFile("../config/versions.php", '$versions', $versions);
	}
	
	// esoTalk 1.0.0 beta 2 -> esoTalk 1.0.0 beta 3
	if ($versions["eso"] == "1.0.0b2") {
		$this->upgrade_100b3();
		$versions["eso"] = "1.0.0b3";
		writeConfigFile("../config/versions.php", '$versions', $versions);
	}

	// esoTalk 1.0.0 beta 3 -> esoBB 1.0.0 pre 1 (referred to as "beta-1.0")
	if ($versions["eso"] == "1.0.0b3") {
		$versions["eso"] = "beta-1.0";
		writeConfigFile("../config/versions.php", '$versions', $versions);
	}

	// esoBB 1.0.0 pre 1 -> esoBB 1.0.0 delta 1
	if ($versions["eso"] == "beta-1.0") {
		$this->upgrade_100d1();
		$versions["eso"] = "1.0.0d1";
		writeConfigFile("../config/versions.php", '$versions', $versions);
	}

	// esoBB 1.0.0 delta 1 -> esoBB 1.0.0 delta 2
	if ($versions["eso"] == "1.0.0d1") {
		$this->upgrade_100d2();
		$versions["eso"] = "1.0.0d2";
		writeConfigFile("../config/versions.php", '$versions', $versions);
	}
	
	// esoBB 1.0.0 delta 2 -> esoBB 1.0.0 delta 3
	if ($versions["eso"] == "1.0.0d2") {
		$this->upgrade_100d3();
		$versions["eso"] = "1.0.0d3";
		writeConfigFile("../config/versions.php", '$versions', $versions);
	}
	
	// Write the program version to the versions.php file.
	if ($versions["eso"] != ESO_VERSION) {
		$versions["eso"] = ESO_VERSION;
		writeConfigFile("../config/versions.php", '$versions', $versions);
	}
	
	// Now, prepare a success message to be displayed!
	$messageHead = "<script type='text/javascript' src='js/eso.js'></script>";	
	$messageTitle = "You're good to go!";
	$messageBody = "<p>Your forum has successfully been upgraded. Here's some stuff you should do now:</p>
	<ul>
	<li><strong>Delete the <code>upgrade</code> directory</strong> to prevent your forum from being hacked!</li>
	<li><a href='{$config["baseURL"]}'>Visit your forum</a> and make sure everything is working - if not, <a href='https://forum.geteso.org'>get help</a>.</li>
	<li>If you're interested, <a href='javascript:toggleAdvanced()'>see advanced information</a> about what happened during the upgrade process.</li>
	</ul>
	<div class='info' id='advanced'>";
	
	// Advanced information...
	// Warnings.
	if (isset($_SESSION["warnings"]) and is_array($_SESSION["warnings"])) {
		$messageBody .= "<strong>Warnings</strong><ul>";
		foreach ($_SESSION["warnings"] as $msg) $messageBody .= "<li>$msg</li>";
		$_SESSION["warnings"] = array();
		$messageBody .= "</ul>";
	}
	
	// Queries run.
	if (isset($_SESSION["queries"]) and is_array($_SESSION["queries"])) {
		$messageBody .= "<strong>Queries run</strong><pre style='overflow:auto'>";
		foreach ($_SESSION["queries"] as $query) $messageBody .= sanitizeHTML($query) . ";<br/><br/>";
		$_SESSION["queries"] = array();
		$messageBody .= "</pre>";
	}
	
	$messageBody .= "</div>
	<script type='text/javascript'>
	// <![CDATA[
	function toggleAdvanced() {
		toggle(getById(\"advanced\"), {animation:'verticalSlide'});
	}
	hide(getById(\"advanced\"));
	// ]]>
	</script>";
	
	// Display the message.
	include "../views/message.php";
	exit;
}

// Perform a MySQL query.
function query($query, $fatal = true)
{
	// Log the query.
	if (!isset($_SESSION["queries"]) or !is_array($_SESSION["queries"])) $_SESSION["queries"] = array();
	$_SESSION["queries"][] = $query;
	
	// Perform the query and return its result if successful.
	$result = mysqli_query($this->link, $query);
	if ($result) return $result;
	
	// Otherwise, show a fatal error.
	else $this->fatalError($this->error() . "<p style='font:100% monospace; overflow:auto'>" . $this->highlightQueryErrors($query, $this->error()) . "</p>");
}

// Display a fatal error message with a 'Try again' link.
function fatalError($message)
{
	$messageTitle = "Uh oh! It's a fatal error...";
	$messageBody = "<p>Your forum has encountered a nasty error which is making it impossible to upgrade your esoBB installation. But don't feel down - <strong>here are a few things you can try</strong>:</p><ul>
	<li><strong><a href=''>Try again</a></strong>. Everyone makes mistakes - maybe the computer made one this time!</li>
	<li><strong>Get help.</strong> Go on the <a href='https://forum.geteso.org' title='Don&#039;t worry, we won&#039;t bite!'>esoBB support forum</a> and <a href='https://forum.geteso.org/search/tag:upgrade'>search</a> to see if anyone else is having the same problem as you are. If not, start a new conversation about your problem, including the error details below.</li>
	<li>Try hitting the computer - that sometimes works for me.</li>
	</ul>
	<div class='info'>$message</div>";
	include "../views/message.php";
	exit;
}

// Write a file - on failure, trigger a fatal error.
function writeFile($file, $contents)
{
	writeFile($file, $contents) or $this->fatalError("<code>$file</code> is not writeable. Try <code>chmod</code>ing it to <code>777</code>, or if it doesn't exist, <code>chmod</code> the folder it is contained within.");
}

// Store a warning message for display in the advanced information section at the end of the upgrade.
function warning($msg)
{
	if (!isset($_SESSION["warnings"]) or !is_array($_SESSION["warnings"])) $_SESSION["warnings"] = array();
	$_SESSION["warnings"][] = $msg;	
}

// 1.0.0 delta 2 -> 1.0.0 delta 3
function upgrade_100d3()
{
	global $config;
	
	// Restructure the logins table to support session management.
	// First, check if the table needs restructuring (if it doesn't have loginId column).
	if (!$this->numRows("SHOW COLUMNS FROM {$config["tablePrefix"]}logins LIKE 'loginId'")) {
		// Drop the old logins table and recreate it with the new structure.
		$this->query("DROP TABLE IF EXISTS {$config["tablePrefix"]}logins");
		$this->query("CREATE TABLE {$config["tablePrefix"]}logins (
			loginId int unsigned NOT NULL AUTO_INCREMENT,
			cookie char(32) default NULL,
			ip int unsigned NOT NULL,
			userAgent varchar(191) default NULL,
			memberId int unsigned NOT NULL,
			action varchar(63) NOT NULL default 'login',
			firstTime int unsigned default NULL,
			lastTime int unsigned default NULL,
			loginTime int unsigned default NULL,
			PRIMARY KEY  (loginId),
			KEY memberId_lastTime (memberId, lastTime),
			KEY cookie (cookie)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8");
	} else {
		// Table exists but may need column updates.
		// Change IP back to int unsigned if it's still char(32).
		$columnInfo = $this->query("SHOW COLUMNS FROM {$config["tablePrefix"]}logins LIKE 'ip'");
		if ($this->numRows($columnInfo)) {
			$column = $this->fetchAssoc($columnInfo);
			if (isset($column["Type"]) && strpos($column["Type"], "char") !== false) {
				$this->query("ALTER TABLE {$config["tablePrefix"]}logins MODIFY COLUMN ip int unsigned NOT NULL");
			}
		}
		
		// Add missing columns if they don't exist.
		if (!$this->numRows("SHOW COLUMNS FROM {$config["tablePrefix"]}logins LIKE 'cookie'"))
			$this->query("ALTER TABLE {$config["tablePrefix"]}logins ADD COLUMN cookie char(32) default NULL AFTER loginId");
		if (!$this->numRows("SHOW COLUMNS FROM {$config["tablePrefix"]}logins LIKE 'userAgent'"))
			$this->query("ALTER TABLE {$config["tablePrefix"]}logins ADD COLUMN userAgent varchar(191) default NULL AFTER ip");
		if (!$this->numRows("SHOW COLUMNS FROM {$config["tablePrefix"]}logins LIKE 'memberId'"))
			$this->query("ALTER TABLE {$config["tablePrefix"]}logins ADD COLUMN memberId int unsigned NOT NULL AFTER userAgent");
		if (!$this->numRows("SHOW COLUMNS FROM {$config["tablePrefix"]}logins LIKE 'action'"))
			$this->query("ALTER TABLE {$config["tablePrefix"]}logins ADD COLUMN action varchar(63) NOT NULL default 'login' AFTER memberId");
		if (!$this->numRows("SHOW COLUMNS FROM {$config["tablePrefix"]}logins LIKE 'firstTime'"))
			$this->query("ALTER TABLE {$config["tablePrefix"]}logins ADD COLUMN firstTime int unsigned default NULL AFTER action");
		if (!$this->numRows("SHOW COLUMNS FROM {$config["tablePrefix"]}logins LIKE 'lastTime'"))
			$this->query("ALTER TABLE {$config["tablePrefix"]}logins ADD COLUMN lastTime int unsigned default NULL AFTER firstTime");
		
		// Add indexes if they don't exist.
		if (!$this->numRows("SHOW INDEX FROM {$config["tablePrefix"]}logins WHERE Key_name='memberId_lastTime'"))
			$this->query("CREATE INDEX memberId_lastTime ON {$config["tablePrefix"]}logins (memberId, lastTime)");
		if (!$this->numRows("SHOW INDEX FROM {$config["tablePrefix"]}logins WHERE Key_name='cookie'"))
			$this->query("CREATE INDEX cookie ON {$config["tablePrefix"]}logins (cookie)");
	}
	
	// Create the actions table (replaces deprecated searches table).
	if (!$this->numRows("SHOW TABLES LIKE '{$config["tablePrefix"]}actions'"))
		$this->query("CREATE TABLE {$config["tablePrefix"]}actions (
			ip int unsigned NOT NULL,
			memberId int unsigned default NULL,
			action varchar(63) NOT NULL,
			time int unsigned NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8");
	
	// Add WebP support to avatarFormat enum.
	$columnInfo = $this->query("SHOW COLUMNS FROM {$config["tablePrefix"]}members LIKE 'avatarFormat'");
	if ($this->numRows($columnInfo)) {
		$column = $this->fetchAssoc($columnInfo);
		// Check if the enum definition includes 'webp'.
		if (isset($column["Type"]) && strpos($column["Type"], "'webp'") === false) {
			// Modify the enum to include 'webp'.
			$this->query("ALTER TABLE {$config["tablePrefix"]}members MODIFY COLUMN avatarFormat enum('jpg','png','gif','webp') default NULL");
		}
	}
	
	// Update salt column size from char(64) to char(32) if needed.
	$columnInfo = $this->query("SHOW COLUMNS FROM {$config["tablePrefix"]}members LIKE 'salt'");
	if ($this->numRows($columnInfo)) {
		$column = $this->fetchAssoc($columnInfo);
		if (isset($column["Type"]) && strpos($column["Type"], "char(64)") !== false) {
			$this->query("ALTER TABLE {$config["tablePrefix"]}members MODIFY COLUMN salt char(32) NOT NULL");
		}
	}
}

// 1.0.0 delta 1 -> 1.0.0 delta 2
function upgrade_100d2()
{
	global $config;

	// Change the default values of a couple columns in the members table.
	$this->query("ALTER TABLE {$config["tablePrefix"]}members
		MODIFY COLUMN password char(60) NOT NULL,
		MODIFY COLUMN cookieIP char(32) default NULL");
	// Change the default value of the IP field in the searches table.
	$this->query("ALTER TABLE {$config["tablePrefix"]}searches MODIFY COLUMN ip char(32) NOT NULL");
	// ...and the logins table!
	$this->query("ALTER TABLE {$config["tablePrefix"]}logins MODIFY COLUMN ip char(32) NOT NULL");
}

// 1.0.0 pre 1 -> 1.0.0 delta 1
function upgrade_100d1()
{
	global $config;

	// Add the salt column to the members table and populate it with the configured salt.
	if (!$this->numRows("SHOW COLUMNS FROM {$config["tablePrefix"]}members LIKE 'salt'")) {
		$this->query("ALTER TABLE {$config["tablePrefix"]}members ADD COLUMN salt char(64) NOT NULL AFTER password");
		$this->query("UPDATE {$config["tablePrefix"]}members SET salt='{$config["salt"]}'");
	}

	// Add a constraint to salt in the members table.
	if (!$this->numRows("SHOW INDEX FROM {$config["tablePrefix"]}members WHERE Key_name='members_salt'"))
		$this->query("CREATE INDEX members_salt ON {$config["tablePrefix"]}members (salt)");

	// Create the logins table, used for search flood control.
	if (!$this->numRows("SHOW TABLES LIKE '{$config["tablePrefix"]}logins'"))
		$this->query("CREATE TABLE {$config["tablePrefix"]}logins (
			ip int unsigned NOT NULL,
			loginTime int unsigned NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");
	
	// Convert a few tables to InnoDB.
	$this->query("ALTER TABLE {$config["tablePrefix"]}status ENGINE=InnoDB");
	$this->query("ALTER TABLE {$config["tablePrefix"]}members ENGINE=InnoDB");
	$this->query("ALTER TABLE {$config["tablePrefix"]}tags ENGINE=InnoDB");

	$file = "service-worker.js";
	if (file_exists("../$file")) @unlink("../$file") or $this->warning("Your forum could not delete <code>/$file</code>. Please delete it manually.");
}

// 1.0.0 beta 2 -> 1.0.0 beta 3
function upgrade_100b3()
{
	global $config;
	
	// Change the default values of a few columns in the members table.
	$this->query("ALTER TABLE {$config["tablePrefix"]}members
		MODIFY COLUMN emailOnPrivateAdd tinyint(1) NOT NULL default '1',
		MODIFY COLUMN emailOnStar tinyint(1) NOT NULL default '1',
		MODIFY COLUMN language varchar(31) default ''");
	
	// Add the joinTime column to the members table.
	if (!$this->numRows("SHOW COLUMNS FROM {$config["tablePrefix"]}members LIKE 'joinTime'"))
		$this->query("ALTER TABLE {$config["tablePrefix"]}members ADD COLUMN joinTime int unsigned NOT NULL AFTER account");
	
	// Make the slug column in the conversations table default to NULL, and get rid of blank hyphen slugs.
	$this->query("ALTER TABLE {$config["tablePrefix"]}conversations MODIFY COLUMN slug varchar(63) default NULL");
	$this->query("UPDATE {$config["tablePrefix"]}conversations SET slug=NULL WHERE slug='-'");
		
	$file = "config/lastUpdateCheck.php";
	if (file_exists("../$file")) @unlink("../$file") or $this->warning("Your forum could not delete <code>/$file</code>. Please delete it manually.");
}

// 1.0.0 beta 1 -> 1.0.0 beta 2
function upgrade_100b2()
{
	global $config;

	// Make the cookieIP field in the members table an unsigned INT (rather than a signed one.)
	$this->query("ALTER TABLE {$config["tablePrefix"]}members MODIFY COLUMN cookieIP int unsigned default NULL");	
}

// 1.0.0 alpha 5 -> 1.0.0 beta 1
function upgrade_100b1()
{
	global $config;
	
	// Rewrite robots.txt (change forgotPassword to forgot-password).
	$this->writeFile(PATH_ROOT."/robots.txt", "User-agent: *
Disallow: /search/
Disallow: /online/
Disallow: /join/
Disallow: /forgot-password/
Disallow: /conversation/new/
Sitemap: {$config["baseURL"]}sitemap.php");
	
	// Add the markedAsRead field to the members table, used for the new 'Mark all conversations as read' feature.
	if (!$this->numRows("SHOW COLUMNS FROM {$config["tablePrefix"]}members LIKE 'markedAsRead'"))
		$this->query("ALTER TABLE {$config["tablePrefix"]}members ADD COLUMN markedAsRead int unsigned default NULL AFTER disableJSEffects");

	// Add the cookieIP field to the members table, used for extra security when logging in with cookies.
	if (!$this->numRows("SHOW COLUMNS FROM {$config["tablePrefix"]}members LIKE 'cookieIP'"))
		$this->query("ALTER TABLE {$config["tablePrefix"]}members ADD COLUMN cookieIP int default NULL AFTER resetPassword");	
		
	// Rename avatarExtension to avatarFormat in the members table.
	if ($this->numRows("SHOW COLUMNS FROM {$config["tablePrefix"]}members LIKE 'avatarExtension'"))
		$this->query("ALTER TABLE {$config["tablePrefix"]}members CHANGE COLUMN avatarExtension avatarFormat enum('jpg','png','gif') default NULL");
	
	// Add a unique constraint to email in the members table.
	if (!$this->numRows("SHOW INDEX FROM {$config["tablePrefix"]}members WHERE Key_name='members_email'"))
		$this->query("CREATE UNIQUE INDEX members_email ON {$config["tablePrefix"]}members (email)");
		
	// Fix NOT NULL bugs from 1.0.0a4 (oops!)
	$this->query("ALTER TABLE {$config["tablePrefix"]}conversations MODIFY lastPostMember int unsigned default NULL");
	$this->query("ALTER TABLE {$config["tablePrefix"]}posts MODIFY editMember int unsigned default NULL, MODIFY deleteMember int unsigned default NULL");
	
	// Create the searches table, used for search flood control.
	if (!$this->numRows("SHOW TABLES LIKE '{$config["tablePrefix"]}searches'"))
		$this->query("CREATE TABLE {$config["tablePrefix"]}searches (
			ip int unsigned NOT NULL,
			searchTime int unsigned NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");
	
	// Add an index to the sticky column in the conversations table.
	if (!$this->numRows("SHOW INDEX FROM {$config["tablePrefix"]}conversations WHERE Key_name='conversations_sticky'"))		
		$this->query("CREATE INDEX conversations_sticky ON {$config["tablePrefix"]}conversations (sticky, lastPostTime)");
	if (!$this->numRows("SHOW INDEX FROM {$config["tablePrefix"]}conversations WHERE Key_name='conversations_startTime'"))		
		$this->query("CREATE INDEX conversations_startTime ON {$config["tablePrefix"]}conversations (startTime)");
		
	// Update posts with quote syntax changes.
	$this->query("UPDATE {$config["tablePrefix"]}posts SET content=REPLACE(content,'</cite>','</cite></p><p>')");
					
	// Delete init.php, classes.php, database.php, formatter.php, and functions.php from the root directory.
	$filesToDelete = array("init.php", "classes.php", "database.php", "functions.php");
	foreach ($filesToDelete as $file) {
		@unlink("../$file") or $this->warning("eso could not delete <code>/$file</code>. Please delete it manually.");
	}
}

}

?>
