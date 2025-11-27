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
 * Installer queries: contains all the queries to create the forum's
 * tables and insert default data.
 */
if (!defined("IN_ESO")) exit;

// Load language file.
$installLanguage = (!empty($config["language"])) ? $config["language"] : "English (casual)";
$installLanguage = sanitizeFileName($installLanguage);
if (file_exists("../languages/{$installLanguage}.php")) {
	include "../languages/{$installLanguage}.php";
} else {
	// Fallback to "English (casual)" if file doesn't exist.
	$installLanguage = "English (casual)";
	include "../languages/{$installLanguage}.php";
}

$queries = array();
$preparedQueries = array();

// Create the conversations table.
$queries[] = "DROP TABLE IF EXISTS {$config["tablePrefix"]}conversations";
$queries[] = "CREATE TABLE {$config["tablePrefix"]}conversations (
	conversationId int unsigned NOT NULL auto_increment,
	title varchar(63) NOT NULL,
	slug varchar(63) default NULL,
	sticky tinyint(1) NOT NULL default '0',
	locked tinyint(1) NOT NULL default '0',
	private tinyint(1) NOT NULL default '0',
	posts smallint(5) unsigned NOT NULL default '0',
	startMember int unsigned NOT NULL,
	startTime int unsigned NOT NULL,
	lastPostMember int unsigned default NULL,
	lastPostTime int unsigned default NULL,
	lastActionTime int unsigned default NULL,
	PRIMARY KEY  (conversationId),
	KEY conversations_startMember (startMember),
	KEY conversations_startTime (startTime),
	KEY conversations_lastPostTime (lastPostTime),
	KEY conversations_posts (posts),
	KEY conversations_sticky (sticky, lastPostTime)
) ENGINE={$config["storageEngine"]} DEFAULT CHARSET={$config["characterEncoding"]}";

// Create the posts table.
$queries[] = "DROP TABLE IF EXISTS {$config["tablePrefix"]}posts";
$queries[] = "CREATE TABLE {$config["tablePrefix"]}posts (
	postId int unsigned NOT NULL auto_increment,
	conversationId int unsigned NOT NULL,
	memberId int unsigned NOT NULL,
	time int unsigned NOT NULL,
	editMember int unsigned default NULL,
	editTime int unsigned default NULL,
	deleteMember int unsigned default NULL,
	title varchar(63) NOT NULL,
	content text NOT NULL,
	PRIMARY KEY  (postId),
	KEY posts_memberId (memberId),
	KEY posts_conversationId (conversationId),
	KEY posts_time (time),
	FULLTEXT KEY posts_fulltext (title, content)
) ENGINE={$config["storageEngine"]} DEFAULT CHARSET={$config["characterEncoding"]}";

// Create the status table.
$queries[] = "DROP TABLE IF EXISTS {$config["tablePrefix"]}status";
$queries[] = "CREATE TABLE {$config["tablePrefix"]}status (
	conversationId int unsigned NOT NULL,
	memberId varchar(31) NOT NULL,
	allowed tinyint(1) NOT NULL default '0',
	starred tinyint(1) NOT NULL default '0',
	lastRead smallint unsigned NOT NULL default '0',
	draft text,
	PRIMARY KEY  (conversationId, memberId)
) ENGINE={$config["storageEngine"]} DEFAULT CHARSET={$config["characterEncoding"]}";

// Create the members table.
$queries[] = "DROP TABLE IF EXISTS {$config["tablePrefix"]}members";
$queries[] = "CREATE TABLE {$config["tablePrefix"]}members (
	memberId int unsigned NOT NULL auto_increment,
	name varchar(31) NOT NULL,
	email varchar(63) NOT NULL,
	password char(60) NOT NULL,
	salt char(32) NOT NULL,
	color tinyint unsigned NOT NULL default '1',
	account enum('Administrator','Moderator','Member','Suspended','Unvalidated') NOT NULL default 'Unvalidated',
	language varchar(31) default '',
	avatarAlignment enum('alternate','right','left','none') NOT NULL default 'alternate',
	avatarFormat enum('jpg','png','gif','webp') default NULL,
	emailVerified tinyint(1) NOT NULL default '0',
	emailOnPrivateAdd tinyint(1) NOT NULL default '1',
	emailOnStar tinyint(1) NOT NULL default '1',
	disableJSEffects tinyint(1) NOT NULL default '0',
	disableLinkAlerts tinyint(1) NOT NULL default '1',
	markedAsRead int unsigned default NULL,
	lastSeen int unsigned default NULL,
	lastAction varchar(191) default NULL,
	resetPassword char(32) default NULL,
	PRIMARY KEY  (memberId),
	UNIQUE KEY members_name (name),
	UNIQUE KEY members_email (email),
	KEY members_password (password),
	KEY members_salt (salt)
) ENGINE={$config["storageEngine"]} DEFAULT CHARSET={$config["characterEncoding"]}";

// Create the tags table.
$queries[] = "DROP TABLE IF EXISTS {$config["tablePrefix"]}tags";
$queries[] = "CREATE TABLE {$config["tablePrefix"]}tags (
	tag varchar(31) NOT NULL,
	conversationId int unsigned NOT NULL,
	PRIMARY KEY  (conversationId, tag)
) ENGINE={$config["storageEngine"]} DEFAULT CHARSET={$config["characterEncoding"]}";

// Create the actions table.
$queries[] = "DROP TABLE IF EXISTS {$config["tablePrefix"]}actions";
$queries[] = "CREATE TABLE {$config["tablePrefix"]}actions (
	ip int unsigned NOT NULL,
	memberId int unsigned default NULL,
	action varchar(63) NOT NULL,
	time int unsigned NOT NULL
) ENGINE={$config["storageEngine"]} DEFAULT CHARSET={$config["characterEncoding"]}";

// Create the logins table.
$queries[] = "DROP TABLE IF EXISTS {$config["tablePrefix"]}logins";
$queries[] = "CREATE TABLE {$config["tablePrefix"]}logins (
	loginId int unsigned NOT NULL AUTO_INCREMENT,
	cookie char(32) default NULL,
	ip int unsigned NOT NULL,
	userAgent varchar(191) default NULL,
	memberId int unsigned NOT NULL,
	action varchar(63) NOT NULL default 'login',
	firstTime int unsigned default NULL,
	lastTime int unsigned default NULL,
	PRIMARY KEY  (loginId),
	KEY memberId_lastTime (memberId, lastTime),
	KEY cookie (cookie)
) ENGINE={$config["storageEngine"]} DEFAULT CHARSET={$config["characterEncoding"]}";

// Create the account for the administrator.
$salt = generateRandomString(32);
if ($config["hashingMethod"] == "bcrypt") {
	$password = password_hash($_SESSION["install"]["adminPass"], PASSWORD_DEFAULT);
} else {
	$password = md5($salt . $_SESSION["install"]["adminPass"]);
}
$color = random_int(1, 27);
// Store as prepared statement query (user-provided data: adminUser, adminEmail)
$preparedQueries[] = array(
	"query" => "INSERT INTO {$config["tablePrefix"]}members (memberId, name, email, password, salt, color, account) VALUES (1, ?, ?, ?, ?, ?, 'Administrator')",
	"types" => "ssssi",
	"params" => array($_SESSION["install"]["adminUser"], $_SESSION["install"]["adminEmail"], $password, $salt, $color)
);

// Create default conversations.
$time = time();
$forumTitle = $_SESSION["install"]["forumTitle"];
$welcomeTitle = sprintf($language["install"]["defaultWelcomeTitle"], $forumTitle);
$welcomeSlug = slug($welcomeTitle);
// Store first conversation as prepared statement query (user-provided data: forumTitle)
$preparedQueries[] = array(
	"query" => "INSERT INTO {$config["tablePrefix"]}conversations (conversationId, title, slug, sticky, posts, startMember, startTime, lastActionTime, private) VALUES (1, ?, ?, 1, 1, 1, $time, $time, 0)",
	"types" => "ss",
	"params" => array($welcomeTitle, $welcomeSlug)
);
// Other conversations don't contain user data, use regular query
$howToUseTitle = $language["install"]["conversationHowToUse"];
$howToCustomizeTitle = $language["install"]["conversationHowToCustomize"];
$queries[] = "INSERT INTO {$config["tablePrefix"]}conversations (conversationId, title, slug, sticky, posts, startMember, startTime, lastActionTime, private) VALUES
(2, '" . mysqli_real_escape_string($db, $howToUseTitle) . "', '" . mysqli_real_escape_string($db, slug($howToUseTitle)) . "', 1, 1, 1, $time, $time, 0),
(3, '" . mysqli_real_escape_string($db, $howToCustomizeTitle) . "', '" . mysqli_real_escape_string($db, slug($howToCustomizeTitle)) . "', 0, 1, 1, $time, $time, 1)";

// Insert default posts.
$forumTitle = $_SESSION["install"]["forumTitle"];
$adminUser = $_SESSION["install"]["adminUser"];
$time = time();
$post1Title = sprintf($language["install"]["defaultWelcomeTitle"], $forumTitle);
$post1Content = sprintf($language["install"]["postWelcomeContent"], $forumTitle, $forumTitle, makeLink(2), $forumTitle);
// Store post 1 as prepared statement (user-provided data: forumTitle)
$preparedQueries[] = array(
	"query" => "INSERT INTO {$config["tablePrefix"]}posts (conversationId, memberId, time, title, content) VALUES (1, 1, $time, ?, ?)",
	"types" => "ss",
	"params" => array($post1Title, $post1Content)
);
// Post 2 has no user data, use regular query
$post2Title = $language["install"]["conversationHowToUse"];
$post2Content = $language["install"]["postHowToUseContent"];
$queries[] = "INSERT INTO {$config["tablePrefix"]}posts (conversationId, memberId, time, title, content) VALUES
(2, 1, $time, '" . mysqli_real_escape_string($db, $post2Title) . "', '" . mysqli_real_escape_string($db, $post2Content) . "')";
// Store post 3 as prepared statement (user-provided data: adminUser)
$post3Title = $language["install"]["conversationHowToCustomize"];
$post3Content = sprintf($language["install"]["postHowToCustomizeContent"], $adminUser, $adminUser);
$preparedQueries[] = array(
	"query" => "INSERT INTO {$config["tablePrefix"]}posts (conversationId, memberId, time, title, content) VALUES (3, 1, $time, ?, ?)",
	"types" => "ss",
	"params" => array($post3Title, $post3Content)
);

// Make the "How to customize your forum" conversation only viewable by the administrator.
$queries[] = "INSERT INTO {$config["tablePrefix"]}status (conversationId, memberId, allowed) VALUES (3, 1, 1)";

// Add tags for the default conversations.
$tags = $language["install"]["defaultTags"];
$tagValues = array();
$tagValues[] = "(1, '" . mysqli_real_escape_string($db, $tags["welcome"]) . "')";
$tagValues[] = "(1, '" . mysqli_real_escape_string($db, $tags["introduction"]) . "')";
$tagValues[] = "(2, '" . mysqli_real_escape_string($db, $tags["esobb"]) . "')";
$tagValues[] = "(2, '" . mysqli_real_escape_string($db, $tags["tutorial"]) . "')";
$tagValues[] = "(2, '" . mysqli_real_escape_string($db, $tags["faq"]) . "')";
$tagValues[] = "(2, '" . mysqli_real_escape_string($db, $tags["howto"]) . "')";
$tagValues[] = "(3, '" . mysqli_real_escape_string($db, $tags["esobb"]) . "')";
$tagValues[] = "(3, '" . mysqli_real_escape_string($db, $tags["customization"]) . "')";
$tagValues[] = "(3, '" . mysqli_real_escape_string($db, $tags["tutorial"]) . "')";
$tagValues[] = "(3, '" . mysqli_real_escape_string($db, $tags["administration"]) . "')";
$queries[] = "INSERT INTO {$config["tablePrefix"]}tags (conversationId, tag) VALUES " . implode(", ", $tagValues);

// Create login record for root user.
$ip = cookieIp();
$userAgent = md5($_SERVER["HTTP_USER_AGENT"]);
$preparedQueries[] = array(
	"query" => "INSERT INTO {$config["tablePrefix"]}logins (cookie, ip, userAgent, memberId, firstTime, lastTime) VALUES (NULL, ?, ?, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())",
	"types" => "is",
	"params" => array($ip, $userAgent)
);

?>
