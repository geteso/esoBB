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
 * 'English (professional)' language pack: provides standard English
 * responses for your forum.
 */
$language = array(

// Defines the character set that this language uses.
"charset" => "utf-8",

"*" => "*",

// Human-friendly dates and times (e.g. edited by Simon 5 minutes ago).
"dateFormat" => "j F Y, g:i a T", // see https://php.net/manual/en/function.date.php for details
"day ago" => "Yesterday",
"days ago" => "%d days ago",
"hour ago" => "1 hour ago",
"hours ago" => "%d hours ago",
"minute ago" => "about a minute ago",
"minutes ago" => "%d minutes ago",
"month ago" => "1 month ago",
"months ago" => "%d months ago",
"second ago" => "1 second ago",
"seconds ago" => "%d seconds ago",
"week ago" => "Last week",
"weeks ago" => "%d weeks ago",
"year ago" => "Last year",
"years ago" => "%d years ago",

"a private conversation" => "a private conversation",
"Account information" => "Account information",
"Add a new language pack" => "Add a new language pack",
"Add a new plugin" => "Add a new plugin",
"Add a new skin" => "Add a new skin",
"Add language pack" => "Add language pack",
"Add plugin" => "Add plugin",
"Add member" => "Add",
"Add skin" => "Add skin",
"Administrator" => "Administrator",
"Administrator-plural" => "Administrators",
"Advanced settings" => "Advanced settings",
"Allow registration" => "Allow registration",
"Appearance settings" => "Appearance settings",
"Are you human" => "Image verification",
"author" => "by %s",

"Basic settings" => "Basic settings",
"Bold" => "Bold",

"Cancel" => "Cancel",
"Can't make it out" => "If you're having trouble, <a href='%s'>generate a new image.</a>",
"Change avatar" => "Change avatar",
"Change icon" => "Change icon",
"Change logo" => "Change logo",
"Change my password or email" => "Change my password or email",
"Change my username" => "Change my username",
"Change password" => "Change password",
"changeUsername" => "Change usernames",
"changeUsernameInfo" => "Whether or not accounts can change their usernames.",
"changeYourName" => "Once you change your name your old one won't be taken.",
"Check for updates" => "Check for updates",
"checkNewResultsInterval" => "Check for new results interval",
"checkNewResultsInfo" => "Amount of time by which new conversations are checked for in an existing search. (60 seconds.)",
"Confirm password" => "Confirm password",
"Conversation" => "Conversation",
"Conversation-plural" => "Conversations",
"conversations" => "conversations",
"Conversations participated in" => "Conversations participated in",
"Conversations started" => "Conversations started",
"Current icon" => "Current icon",
"Current logo" => "Current logo",

"Dashboard" => "Dashboard",
"Debug information" => "Debug information",
"Default forum language" => "Default forum language",
"delete" => "delete",
"delete forever" => "delete forever",
"Delete conversation" => "Delete conversation",
"deleted by" => "deleted by %s",
"Disable" => "Disable",
"disableJSEffects" => "Disable JavaScript effects and animations",
"disableLinkAlerts" => "Disable alerts when clicking on <a href class='external' onclick='return false'>external links</a>",
"Disable emoticons" => "Disable image emoticons",
"Discard draft" => "Discard draft",
"Display avatars" => "Display avatars",
"do not display avatars" => "do not display avatars",

"edit" => "edit",
"edited by" => "edited by",
"Email" => "Email",
"emailOnPrivateAdd" => "Email me when I'm added to a private conversation",
"emailOnStar" => "Email me when someone posts in a conversation I have starred",
"Enable" => "Enable",
"Enter a conversation title" => "Enter a conversation title",
"Enter the web address of a logo" => "Enter the web address of a logo",
"Enter the web address of an avatar" => "Enter the web address of an avatar",
"Enter the web address of an icon" => "Enter the web address of an icon",
"Enter your email" => "Enter your email",
"Everyone" => "Everyone",
"exampleTags" => "ex. movies, winter olympics, cooking",

"Fatal error" => "Fatal error",
"fatalErrorMessage" => "<p>The forum has encountered an error which is preventing it from functioning properly. To resolve the issue, <strong>try the following</strong>:</p>
<ul>
<li><strong><a href='javascript:window.location.reload()'>Reload the page</a></strong>.</li>
<li>If you are the forum administrator, <strong>get help on <a href='https://geteso.org'>geteso.org</a></strong>.</li>
</ul>",
"First" => "First",
"First posted" => "First posted",
"Fixed" => "Fixed",
"Forgot password" => "Forgot password",
"Forgot your password" => "Forgot your password?",
"Forum description" => "Forum description",
"Forum language" => "Forum language",
"Forum icon" => "Forum icon",
"Forum logo" => "Forum logo",
"Forum settings" => "Forum settings",
"Forum statistics" => "Forum statistics",
"Forum title" => "Forum title",
"Forum version" => "Forum version",
"forumDescription" => "%s is a web-forum discussing %s, and %s.",

"gzipOutput" => "Gzip page output",
"gzipOutputInfo" => "Whether or not to compress the page output. Saves bandwith.",
"go to this post" => "go to this post",

"Header" => "Header",
"hide" => "hide",
"Home" => "Home",
"Hooked functions" => "Hooked functions",
"httpsInfo" => "This will not substitute a web certificate.",
"httpsSetting" => "Enforce HTTPS",

"iconInfo" => "Your forum icon appears next to links and web clips in order to represent your forum. Your icon will be automatically resized to be 256 pixels high.",
"Image" => "Image",
"Installed language packs" => "Installed languages",
"Installed plugins" => "Installed plugins",
"Installed skins" => "Installed skins",
"Italic" => "Italic",

"Join this forum" => "Join this forum",
"Join us" => "Join us",
"Jump to last" => "Jump to last",
"Jump to unread" => "Jump to unread",
"Just now" => "Just now",

"labels" => array(
	"sticky" => "Sticky",
	"private" => "Private",
	"draft" => "Draft",
	"locked" => "Locked"
),
"Labels" => "Labels",
"languagePackInfo" => "Upload languages packs to the <code>languages/</code> folder or the languages dashboard tab to see them here.",
"Languages" => "Languages",
"Last" => "Last",
"Last active" => "Last active",
"Last reply" => "Last reply",
"Link" => "Link",
"Loading" => "Loading...",
"Lock" => "Lock",
"Log in" => "Log in",
"Log out" => "Log out",
"loginsMinuteInfo" => "Amount of login attempts a user is limited to per minute.",
"loginsPerMinute" => "Logins per minute",
"logoInfo" => "On most skins, your forum logo appears near the title in the header of your forum. Your logo will be automatically resized to be 32 pixels high.",

"Mark all conversations as read" => "Mark all conversations as read",
"Member" => "Member",
"member online" => ">member online",
"Member-plural" => "Members",
"Members allowed to view this conversation" => "Members allowed to view this conversation",
"members online" => "members online",
"messageDisplayTime" => "Message display time",
"messageDisplayTimeInfo" => "Amount of time by which most messages floating above the navigation bar disappear. (20 seconds = 20 seconds.)",
"minPasswordLength" => "Minimum password length",
"Moderator" => "Moderator",
"Moderator-plural" => "Moderators",
"My current password" => "My current password",
"My profile" => "My profile",
"My settings" => "My settings",
"MySQL queries" => "MySQL queries",
"MySQL version" => "MySQL version",

"Never" => "Never",
"New conversations in the past week" => "New conversations in the past week",
"New email" => "New email",
"New password" => "New password",
"New posts in the past week" => "New posts in the past week",
"New username" => "New username",
"Next" => "Next &#155;",
"No avatar" => "No avatar",
"No preview" => "No preview",
"Number of characters" => "Number of characters",
"numberMoreResults" => "Number of more results",
"numberMoreResultsInfo" => "Total number of conversations to list per every request for more results.",
"numberResults" => "Number of results",
"numberResultsInfo" => "Number of conversations to list for a normal search.",
"numberTagsInTagCloud" => "Number of tags in tag cloud",
"nonAsciiCharacters" => "Allow non-printable characters",
"nonAsciiInfo" => "Whether or not usernames may contain non-ASCII characters such as <code>¶,Ü,Œ</code>.",

"on alternating sides" => "on alternating sides",
"on the left" => "on the left",
"on the right" => "on the right",
"online" => "online",
"Online members" => "Online members",
"optional" => "(optional)",
"Other settings" => "Other settings",

"Page loaded in" => "Page loaded in just over <strong><span id='debugLoadTime'>%s</span> seconds</strong>",
"Password" => "Password",
"Permalink to this post" => "Permalink to this post",
"PHP version" => "PHP version",
"Plugins" => "Plugins",
"POST + GET + FILES information" => "POST + GET + FILES information",
"Post a reply" => "Post a reply",
"Post count" => "Post count",
"posts" => "posts",
"Posts" => "Posts",
"post per day" => "that's about 1 post per day",
"posts per day" => "that's about %s posts per day",
"Powered by" => "Powered by",
"Preview" => "Preview",
"Previous" => "&#139; Previous",
"profile" => "%s's profile",

"quote" => "quote",
"Quote" => "\"Quote\"",

"Recent posts" => "Recent posts",
"Recover password" => "Recover password",
"Registration settings" => "Registration settings",
"registrationAuto" => "automatically",
"registrationEmail" => "by email verification",
"registrationManual" => "by manual approval",
"Remember me" => "Remember me",
"restore" => "restore",
"Require email approval" => "Require email approval",
"Require manual approval" => "Require manual approval",
"RSS" => "RSS",

"Sample captcha image" => "Sample captcha image",
"Save changes" => "Save changes",
"Save draft" => "Save draft",
"Save post" => "Save post",
"Search" => "Search",
"searchesMinuteInfo" => "Amount of searches a user is limited to per minute.",
"searchesPerMinute" => "Searches per minute",
"See the private conversations I've had" => "See the private conversations I've had with %s",
"Server information" => "Server information",
"SESSION + COOKIE information" => "SESSION + COOKIE information",
"settings" => "settings",
"show" => "show",
"showAvatarThumbnails" => "Show avatar thumbnails",
"Show another one" => "Show another one",
"show conversations participated in" => "view them",
"show conversations started" => "view them",
"Show debug information to non-administrators" => "Show debug information to non-administrators",
"Show forum description" => "Show forum description",
"Skin" => "Skin",
"Skins" => "Skins",
"Starred" => "Starred",
"Start a conversation" => "Start a conversation",
"Start a private conversation" => "Start a private conversation with %s",
"Started by" => "Started by",
"Starting a conversation" => "Starting a conversation",
"Sticky" => "Sticky",
"Strike" => "Strike",
"Submit post" => "Submit post",
"Suspended" => "Suspended",

"Tags" => "Tags",
"Type the letters you see" => "Type the letters you see in the image",

"Unlock" => "Unlock",
"unread" => "unread",
"Unstarred" => "Unstarred",
"Unsticky" => "Unsticky",
"Untitled conversation" => "Untitled conversation",
"Unvalidated" => "Unvalidated",
"Unvalidated members" => "Unvalidated members",
"updateCurrentResultsInterval" => "Update current results interval",
"updateCurrentResultsInfo" => "Amount of time by which conversations are updated in an existing search. (30 = 30 seconds.)",
"Update debug information for background AJAX requests" => "Update debug information for background AJAX requests",
"Upload an avatar" => "Upload an avatar from your computer",
"Upload a language pack" => "Upload a language package",
"Upload a logo from your computer" => "Upload a logo from your computer",
"Upload an icon from your computer" => "Upload an icon from your computer",
"Upload a plugin" => "Upload a plugin package",
"Upload a skin" => "Upload a skin package",
"uploadPackages" => "Upload packages",
"uploadPackagesInfo" => "Whether or not admins can upload plugin and skin packages to your forum. For maximum security, disable this setting.",
"Use default icon" => "Use default icon",
"Use default logo" => "Use default logo",
"Use forum description" => "Use forum description in meta tags",
"Use friendly URLs" => "Use friendly URLs",
"Username" => "Username",
"userOnlineExpire" => "User online expire",
"userOnlineExpireInfo" => "Amount of time a user's last seen time is before the user goes offline. (300 seconds = 5 minutes.)",

"version" => "version %s",
"Video"  =>  "Video",
"Viewing" => "Viewing:",
"viewingPosts" => "<b>%s-%s</b> of %s posts",


// Emails.
"emails" => array(

"forgotPassword" => array(
"subject" => "Did you forget your password, %s?",
"body" => "%s, some one (hopefully you!) has submitted a forgotten password request for your account on %s. If you do not wish to change your password, just ignore this email and nothing will happen.\n\nHowever, if you did forget your password and wish to set a new one, visit the following link:\n%s"),

"join" => array(
"subject" => "%s, please validate your account",
"body" => "%s, someone (hopefully you!) has signed up to %s with this email address.\n\nIf this was you, simply visit the following link and your account will be activated:\n%s"),

"privateAdd" => array(
"subject" => "%s, you have been added to a private conversation",
"body" => "%s, you have been added to a private conversation titled '%s'.\n\nTo view this conversation, please visit the following link:\n%s"),

"newReply" => array(
"subject" => "%s, there is a new reply to '%s'",
"body" => "%s, %s has replied to a conversation which you starred: '%s'.\n\nTo view the new activity, please visit the following link:\n%s")
),

"confirmLeave" => "You have made changes on this page which have not yet been saved. If you navigate away from this page, you wil lose these changes. Would you like to continue regardless?",
"confirmDiscard" => "You have not saved your reply as a draft. Do you wish to discard it?",
"confirmDeleteConversation" => "Are you sure you want to delete this conversation? Once deleted, you will not be able to recover it.",
"confirmDeletePost" => "Are you sure you want to delete this post? Once deleted, you will not be able to recover it.",
"confirmExternalLink" => "Are you sure you want to click this link? It leads to an external website or service, which could be potentially harmful.",
"ajaxRequestPending" => "This page is still processing. If you navigate away from this page you might lose any recent changes you've made. Do you wish to continue?",
"ajaxDisconnected" => "Unable to communicate with the server. Wait a few seconds and <a href='javascript:Ajax.resumeAfterDisconnection()'>try again</a>, or <a href='' onclick='window.location.reload();return false'>refresh the page</a>."
);


$language["gambits"] = array(

// Translating the gambit system can be quite complex, but we'll do our best to get you through it. :)
// Note: Don't use any html entities in these definitions, except for: &lt; &gt; &amp; &#39; 

// Simple gambits
// These gambits are pretty much evaluated as-they-are.
// tag:, author:, contributor:, and quoted: are combined with a value after the colon (:).
// For example: tag:video games, author:myself
"100" => "100",
"tag:" => "tag:",
"author:" => "author:",
"contributor:" => "contributor:",
"member" => "member",
"myself" => "myself",
"draft" => "draft",
"has attachments" => "has attachments",
"limit:" => "limit:",
"locked" => "locked",
"order by newest" => "order by newest",
"order by posts" => "order by posts",
"private" => "private",
"random" => "random",
"reverse" => "reverse",
"starred" => "starred",
"sticky" => "sticky",
"unread" => "unread",
"more results" => "more results",

// Aliases
// These are gambits which tell the gambit system to use another gambit.
// In other words, when you type "active today", the gambit system interprets it as if you typed "active 1 day".
// The first of each pair, the alias, can be anything you want.
// The second, however, must fit with the regular expression pattern defined below (more on that later.)
"active today" => "active today", // what appears in the gambit cloud
"active 1 day" => "active 1 day", // what it actually evaluates to

"has replies" => "has replies",
"has &gt; 1 post" => "has &gt; 1 post",

"has no replies" => "has no replies",
"has 0 posts" => "has 0 posts",

"dead" => "dead",
"active &gt; 30 day" => "active &gt; 30 day",

// Units of time
// These are used in the active gambit.
// ex. "[active] [>|<|>=|<=|last] 180 [second|minute|hour|day|week|month|year]"
"second" => "second",
"minute" => "minute",
"hour" => "hour",
"hours" => "hours",
"day" => "day",
"week" => "week",
"month" => "month",
"year" => "year",
"last" => "last", // as in "active last 180 days"
"active" => "active" // as in "active last 180 days"
);


$language["gambits"] += array(

// Now the hard bit. This is a regular expression to test for the "active" gambit.
// The group (?<a> ... ) is the comparison operator (>, <, >=, <=, or last).
// The group (?<b> ... ) is the number (ex. 24).
// The group (?<c> ... ) is the unit of time.
// The languages of "last" and the units of time are defined above.
// However, if you need to reorder the groups, do so carefully, and make sure spaces are written as " *".
"gambitActive" => "/^{$language["gambits"]["active"]} *(?<a>&gt;|&lt;|&gt;=|&lt;=|{$language["gambits"]["last"]})? *(?<b>\d+) *(?<c>{$language["gambits"]["second"]}|{$language["gambits"]["minute"]}|{$language["gambits"]["hour"]}|{$language["gambits"]["day"]}|{$language["gambits"]["week"]}|{$language["gambits"]["month"]}|{$language["gambits"]["year"]})/",

// These appear in the tag cloud. They must fit the regular expression pattern where the ? is a number.
// If the regular expression pattern has been reordered, these gambits must also be reordered (as well as the ones in aliases.)
"active last ? hours" => "{$language["gambits"]["active"]} {$language["gambits"]["last"]} ? {$language["gambits"]["hour"]}s",
"active last ? days" => "{$language["gambits"]["active"]} {$language["gambits"]["last"]} ? {$language["gambits"]["day"]}s",

// This is similar to the regular expression for the active gambit, but for the "has n post(s)" gambit.
// Usually you just need to change the "has" and "post".
"gambitHasNPosts" => "/^has *(?<a>&gt;|&lt;|&gt;=|&lt;=)? *(?<b>\d+) *post/",

// This goes by the same rules as "active last ? hours" and "active last ? days".
"has &gt;10 posts" => "has &gt;10 posts"

);


// Messages.
$messages=array(
"accountNotYetVerified" => array("class" => "info","message" => "You need to verify your account before you can log in with it. If you didn't receive the verification email, <a href='%s'>click here to get it sent again</a>."),
"accountValidated" => array("class" => "success","message" => "Your account has been validated and you may now start participating in conversations."),
"avatarError" => array("class" => "warning","message" => "There was a problem uploading your avatar. Make sure you are using a valid image type (.jpg, .png, or .gif) and the file does not exceed the maximum upload size of the server."),
"avatarDisabled" => array("class" => "warning", "message" => "Avatar settings have been disabled on this forum, but you can always ask the forum administrator to turn it back on."),
"beenLoggedOut" => array("class" => "warning","message" => "You seem to have been <strong>logged out</strong> since you loaded this page. Please reenter your password below or press <strong>cancel</strong> to ignore this message.<br/><br/>Enter the password for <strong>%s</strong>: %s"),
"cannotViewConversation" => array("class" => "warning","message" => "This conversation cannot be viewed. It may have been deleted or turned into a private conversation."),
"captchaError" => array("class" => "warning","message" => "The characters you entered did not match the characters displayed in the image. Please try again."),
"changesSaved" => array("class" => "success","message" => "Your changes were saved."),
"conversationDeleted" => array("class" => "success","message" => "The conversation was deleted."),
"downloadLanguagePacks" => array("class" => "info", "message" => "You can download more language packages from the <a href='https://geteso.org/languages/'>esoBB website</a>."),
"downloadPlugins" => array("class" => "info","message" => "You can download more plugins from the <a href='https://geteso.org/plugins/'>esoBB website</a>."),
"downloadSkins" => array("class" => "info","message" => "You can download more skins from the <a href='https://geteso.org/skins/'>esoBB website</a>."),
"emailDoesntExist" => array("class" => "warning","message" => "The email address you entered does not match any members in the database. Please check that the spelling is correct."),
"emailInfo" => array("class" => "info","message" => "Used to verify your account and subscribe to conversations"),
"emailTaken" => array("class" => "warning","message" => "There is already a member with this email address."),
"emptyPost" => array("class" => "warning","message" => "Your post cannot be submitted with no content."),
"emptyTitle" => array("class" => "warning","message" => "The title of your conversation may not be blank."),
"forgotPassword" => array("class" => "info","message" => "If you have forgotten your password, we'll send you a link to a page where you can choose a new one. Please enter your email address (and check your spam folder if the email doesn't arrive within the next minute or two)."),
"forumDescriptionError" => array("class" => "warning", "message" => "The description of your forum can't be blank, so make sure it's at least one character."),
"forumTitleError" => array("class" => "warning", "message" => "The title of your forum can't be blank, so make sure it's at least one character."),
"gdNoSupportOpenType" => array("class" => "warning","message" => "Your version of GD does not support OpenType font rendering."),
"gdNotInstalled" => array("class" => "warning","message" => "GD is not installed."),
"incorrectLogin" => array("class" => "warning","message" => "Your login details were incorrect. <a href='".makeLink("forgot-password")."'>Have you forgotten your password?</a>"),
"incorrectPassword" => array("class" => "warning","message" => "Your current password is incorrect"),
"invalidCharacters" => array("class" => "warning","message" => "You can't use invalid characters in your name."),
"invalidConfig" => array("class" => "warning","message" => "The configuration setting you have entered must have a value greater than 0."),
"invalidEmail" => array("class" => "warning","message" => "This email address is not valid."),
"invalidLanguagePack" => array("class" => "warning", "message" => "The language pack you uploaded is not valid."),
"invalidPlugin" => array("class" => "warning","message" => "The plugin you uploaded is not valid."),
"invalidSkin" => array("class" => "warning","message" => "The skin you uploaded is not valid."),
"languagePackAdded" => array("class" => "success", "message" => "The language pack was successfully added!"),
"locked" => array("class" => "warning","message" => "This conversation is <strong>locked</strong>, so you may not reply to it."),
"lockedButCanReply" => array("class" => "info","message" => "This conversation is <strong>locked</strong>, but you can still reply because you are a moderator or administrator."),
"loginRequired" => array("class" => "warning","message" => "You need to <a href='".curLink()."#' onclick='showLogin();return false'>log in</a> or <a href='".makeLink("join")."'>create an account</a> to perform any action on this forum."),
"memberDoesntExist" => array("class" => "warning","message" => "No member with that name exists."),
"nameEmpty" => array("class" => "warning","message" => "You must enter a name"),
"nameTaken" => array("class" => "warning","message" => "The name you have entered is already taken or is a reserved word"),
"newSearchResults" => array("class" => "info","message" => "There has been new activity that has affected your search results. <a href='javascript:Search.showNewActivity()'>Show new activity</a>"),
"noLanguagesInstalled" => array("class" => "warning", "message" => "No language packages are currently installed."),
"noMembersOnline" => array("class" => "warning","message" => "No members are currently online."),
"noMembersUnvalidated" => array("class" => "warning","message" => "No members are currently unvalidated."),
"noPermission" => array("class" => "warning","message" => "You do not have permisssion to perform this action."),
"noPluginsInstalled" => array("class" => "warning","message" => "No plugins are currently installed."),
"noSearchResults" => array("class" => "warning","message" => "No conversations matching your search were found."),
"noSkinsInstalled" => array("class" => "warning","message" => "No skins are currently installed."),
"noUploadingPackages" => array("class" => "warning", "message" => "Uploading plugin and skin packages has been disabled on this forum."),
"notWritable" => array("class" => "warning","message" => "<code>%s</code> is not writeable. Try <code>chmod</code>ing it to <code>777</code>, or if it doesn't exist, <code>chmod</code> the folder it is contained within."),
"passwordChanged" => array("class" => "success","message" => "Your password has been changed. You may now log in with your new password."),
"passwordEmailSent" => array("class" => "success","message" => "You have been sent an email containing a link to reset your password. Please check your spam folder if you don't receive it within the next minute or two."),
"passwordInfo" => array("class" => "info","message" => "Choose a secure password of at least {$config["minPasswordLength"]} characters"),
"passwordUpgraded" => array("class" => "success", "message" => "Your password has been upgraded to be more secure. Don't worry, you'll still be able to log in."),
"passwordsDontMatch" => array("class" => "warning","message" => "Your passwords do not match"),
"passwordTooShort" => array("class" => "warning","message" => "Your password must be at least {$config["minPasswordLength"]} characters"),
"pluginAdded" => array("class" => "success","message" => "The plugin was successfully added."),
"pluginCannotBeEnabled" => array("class" => "warning","message" => "The plugin <em>%s</em> cannot be enabled: %s"),
"postTooLong" => array("class" => "warning","message" => "Your post is too long. The maximum number of characters allowed is ".number_format($config["maxCharsPerPost"]).". Please shorten your post and re-submit."),
"reenterInformation" => array("class" => "info","message" => "Please reenter this information."),
"registrationClosed" => array("class" => "info", "message" => "User registration has been disabled on this forum."),
"removeDirectoryWarning" => array("class" => "warning", "message" => "You haven't deleted the <code>%s</code> directory as we recommended. Please do so, as it'll help ensure your forum remains secure."),
"sendEmailDisabled" => array("class" => "warning", "message" => "Email sending has been disabled on this forum. To reset your password, contact the forum administrator."),
"setNewPassword" => array("class" => "info","message" => "Please choose a new password."),
"skinAdded" => array("class" => "success","message" => "The skin was successfully added."),
"suspended" => array("class" => "warning","message" => "A forum moderator has <strong>suspended</strong> your account. Until the suspension is lifted you will not be able to perform many actions on this forum."),
"updatesAvailable" => array("class" => "info","message" => "A new version of the forum (<strong>%s</strong>) is available for download. You have version {$versions["eso"]}. You may <strong><a href='https://geteso.org/download'>download it now</a></strong> or <a href='' onclick='Messages.hide();return false'>dismiss this message</a>."),
"verifyEmail" => array("class" => "success","message" => "Before you can start using your newly-created account, you'll need to verify your email address. Within the next minute or two you should receive an email from us containing a link to activate your account. <strong>Check your spam folder</strong> if you don't receive this email shortly."),
"viewMore" => array("class" => "info","message" => "Your search found more than {$config["results"]} conversations. <a href='%s' onclick='Search.viewMore();return false'>View more</a>"),
"waitForApproval" => array("class" => "success", "message" => "Before you can start using your newly-created account, it'll need to be manually approved. Within the next couple of hours or so your account should be able to post."),
"waitToLogin" => array("class" => "warning", "message" => "You have performed too many login attempts in the last minute. Please wait %s seconds and try again."),
"waitToReply" => array("class" => "warning","message" => "To prevent flooding, you must wait at least {$config["timeBetweenPosts"]} seconds between starting or replying to conversations."),
"waitToSearch" => array("class" => "warning","message" => "You have performed too many searches in the last minute. Please wait %s seconds and try again."),

);

?>
