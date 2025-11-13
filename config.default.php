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
 * Default configuration: don't edit this.  If you wish to change a
 * config setting, copy it into config/config.php and change it there.
 */

// The version of the code.
define("ESO_VERSION", "1.0.0d3");

$defaultConfig = array(
// This following block is filled out by the installer in config/config.php.
"mysqlHost" => "",
"mysqlUser" => "",
"mysqlPass" => "",
"mysqlDB" => "",
"tablePrefix" => "",
"characterEncoding" => "utf8mb4",
"connectionOptions" => "",
"storageEngine" => "MyISAM",
"hashingMethod" => "md5",

// Basic forum details.
"forumTitle" => "",
"forumDescription" => "",
"language" => "English (casual)",
"baseURL" => "",
"resourceURL" => "",
"rootAdmin" => 1, // The member ID of the root administrator.
"emailFrom" => "", // The email address to send forum emails (notifications etc.) from.
"gzipOutput" => true, // Whether or not to compress the page output.  Saves bandwith.
"https" => false, // Whether or not to force HTTPS.

// This following block may be filled out manually.
// Be careful when editing this.  You could break your forum.
"forumLogo" => false, // Path to an image file to replace the logo.  False for skin default.
"forumIcon" => false, // Same thing as before, but for the icon.
"shortcutIcon" => false, // Same thing as before, but for the favicon (shortcut icon).
"showDescription" => true, // Whether or not to display the forum description on the homepage.
"sitemapCacheTime" => 3600, // Amount of time by which sitemaps are kept in cache.  (3600 seconds = 1 hour.)
"manifestCacheTime" => 3600, // Same thing as before, but for the web app manifest.
"manifestDisplay" => "browser", // The preferred way to display your forum in or outside of a browser.  Fullscreen, standalone, minimal-ui, or browser.
// see https://www.w3.org/TR/mediaqueries-5/#display-mode for an explanation
"verboseFatalErrors" => false, // Dumps SQL information in fatal errors.  Don't keep this enabled for production.

// Meta information.
"metaDescription" => false, // Whether or not to use the forum description instead of the forumDescription language string in meta tags.
"metaKeywords" => false, // Meta keywords to be used by search engines.  False for tags to be used as keywords.
// ex. "metaKeywords" => array("esotalk", "faq", "howto", "tutorial"),

// Email settings.
// WARNING: Email sending is disabled by default and should be configured first!
// Read the guide on setting up email:
"sendEmail" => false, // Whether outgoing emails may be sent. Required for any email-based features.
// The following isn't necessary unless you're planning to use SMTP email.
"smtpAuth" => false, // false | "ssl" | "tls"
"smtpHost" => "",
"smtpPort" => "",
"smtpUser" => "",
"smtpPass" => "",

// Skins and plugins.
"skin" => "Plastic", // The default skin.  (This is overridden by config/skin.php.)
"plugins" => array("Captcha"), // A list of enabled plugins.  (This is overridden by config/plugins.php.)
"uploadPackages" => true, // Whether or not admins can upload plugin and skin packages to your forum.  Don't keep this enabled if you don't trust your admins.
"themeColor" => false, // The suggested hex color that may accent the UI of a browser viewing your forum.  (ex. 4285f4)
// see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/meta/name/theme-color for an explanation

// Login and registration settings.
"changeUsername" => true, // Whether or not accounts can change their usernames.
"loginsPerMinute" => "10", // Amount of login attempts a user is limited to per minute.
"minPasswordLength" => 6,
"nonAsciiCharacters" => true, // Whether or not usernames may contain non-printable characters (includes things like umlauts).
"registrationOpen" => true, // Whether or not new accounts can be made on your forum.
"requireEmailApproval" => true, // Requires sendMail to be true. Whether or not new accounts will be required to have a verified email address before logging in.
"requireManualApproval" => false, // Whether or not new accounts must be approved by an administrator before logging in.  (They will remain unvalidated.)
// If manual and email validation are both enabled, the user will have to verify their email address AND be manually validated before being able to log in.
"registrationsPerMinute" => "5", // Amount of registration attempts a user is limited to per minute.
"reservedNames" => array("guest", "member", "members", "moderator", "moderators", "administrator", "administrators", "admin", "suspended", "eso", "name", "password", "everyone", "myself"), // Reserved user names which cannot be used.

// Cookie settings.
"cookieName" => "",
"cookieDomain" => "", // Sets a custom cookie domain. Set it to .yourdomain.com to have the cookie set across all subdomains. Keep blank to use baseURL.
"cookieExpire" => 2592000, // Amount of time by which cookies are kept. (2592000 seconds = 30 days.)
"sessionExpire" => 604800, // Amount of time by which sessions are kept. (604800 seconds = 1 week.)
"validateSessionIp" => false, // Whether to validate session IP address. Disabled by default as it breaks for users behind proxies/VPNs/load balancers.

// URL settings.
"useFriendlyURLs" => true, // ex. example.com/index.php/conversation/1
"usePrettyURLs" => false, // ex. example.com/conversation/1/welcome-to-simon-s-test-forum
"useModRewrite" => true, // ex. example.com/conversation/1 (requires mod_rewrite and a .htaccess file!)

// Search view settings.
"results" => 20, // Number of conversations to list for a normal search.
"messageDisplayTime" => 20, // Amount of time by which most messages floating above the navigation bar disappear. (20 seconds = 20 seconds. lol.)
"moreResults" => 100, // Total number of conversations to list per every request for more results.
"numberOfTagsInTagCloud" => 40, // Number of tags to show in the tag cloud.
"updateCurrentResultsInterval" => 30, // Amount of time by which conversations are updated (post information) in an existing search. (30 seconds.)
"checkForNewResultsInterval" => 60, // Amount of time by which new conversations are checked for in an existing search. (60 seconds.)
"searchesPerMinute" => 10, // Amount of searches a user is limited to per minute.

// Conversation view settings.
"postsPerPage" => 20, // Maximum number of posts to display on each page of a conversation.
"timeBetweenPosts" => 10, // Minimum number of seconds between a user's post.
"maxCharsPerPost" => 50000, // Maximum number of characters per post.
"autoReloadIntervalStart" => 4, // Initial number of seconds before checking for new posts on the conversation view.
"autoReloadIntervalMultiplier" => 1.5, // Each time we check for new posts and there are none, multiply the number of seconds by this.
// ex. after 4 seconds, check for new posts.  If there are none: after 4*1.5 = 6 seconds check for new posts. If there are none: after 6*1.5 = 9 seconds check for new posts.
"autoReloadIntervalLimit" => 512, // Maximum number of seconds between checking for new posts. 

// Online settings.
"onlineMembers" => true, // Whether or not to show a list of online members.  true | false | "login" = users only
"onlineShowSuspended" => false, // Whether or not to include suspended accounts in the list.
"userOnlineExpire" => 4500, // Amount of time by which a user's last seen time is before the user goes offline. (300 seconds = 5 minutes.)

// Default user preferences.
"avatarAlignment" => "alternate", // Default alignment preference for avatars. Alternate, left, right, or none. Individual users can override this.
"showAvatarThumbnails" => true, // Whether or not to show avatar thumbnails next to each conversation.
"emailPrivateAdd" => true, // Email when added to a private conversation?
"emailStarReply" => true, // Email when someone posts in a private conversation that's starred?
"rememberMe" => true, // Whether or not the user's login information will be cached (stored as a cookie).
"showOnline" => true, // Whether or not to display when the user is online.

// Avatar dimensions in pixels.
"avatarMaxWidth" => 200,
"avatarMaxHeight" => 200,
"avatarThumbWidth" => 64,
"avatarThumbHeight" => 64,
"changeAvatar" => true, // Whether or not to let users change their avatar. Useful if your forum is tight on space or can't host images.
);

?>
