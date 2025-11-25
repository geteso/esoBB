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
$welcomeTitle = "Welcome to {$_SESSION["install"]["forumTitle"]}!";
$welcomeSlug = slug($welcomeTitle);
// Store first conversation as prepared statement query (user-provided data: forumTitle)
$preparedQueries[] = array(
	"query" => "INSERT INTO {$config["tablePrefix"]}conversations (conversationId, title, slug, sticky, posts, startMember, startTime, lastActionTime, private) VALUES (1, ?, ?, 1, 1, 1, $time, $time, 0)",
	"types" => "ss",
	"params" => array($welcomeTitle, $welcomeSlug)
);
// Other conversations don't contain user data, use regular query
$queries[] = "INSERT INTO {$config["tablePrefix"]}conversations (conversationId, title, slug, sticky, posts, startMember, startTime, lastActionTime, private) VALUES
(2, 'How to use your forum', '" . slug("How to use your forum") . "', 1, 1, 1, $time, $time, 0),
(3, 'How to customize your forum', '" . slug("How to customize your forum") . "', 0, 1, 1, $time, $time, 1)";

// Insert default posts.
$forumTitle = $_SESSION["install"]["forumTitle"];
$adminUser = $_SESSION["install"]["adminUser"];
$time = time();
$post1Title = "Welcome to $forumTitle!";
$post1Content = "<p><b>Welcome to $forumTitle!</b></p><p>$forumTitle is powered by <a href='https://geteso.org/'>esoBB</a>, which is a little bit different to some of the other forums you may have previously used. <a href='" . makeLink(2) . "'>Check out this topic</a> for a basic tutorial.</p><p>Otherwise, it&#39;s time to get posting!</p><p>Currently the tag cloud on the index view is going to be full of the tags from these instruction posts. As you start new conversations you&#39;ll notice that the tag cloud adapts to the new content of the forum. In time, it&#39;ll become very distinct and will give visitors a good idea of what your forum is about.</p><p>Your forum has been designed to be completely customizable. The forum administrator will be able to choose from a bunch of plugins and skins, and adapt this forum to the needs of its community.</p><p>Anyway, good luck <i>$forumTitle</i>, and we hope you enjoy using your &#39;esoteric&#39; forum!</p>";
$post3Content = "<h3>Hey $adminUser, congrats on getting <b>eso</b> installed!</h3><p>Cool! Your forum is now good-to-go, but you might want to customize it with your own logo, design, and configuration settings - so here&#39;s how. Remember, some of this stuff can be a bit complex, especially if you don&#39;t have much experience with this sorta stuff, so feel free to ask for help at <a href='https://geteso.org/'>geteso.org</a>.</p><h3>Adding your logo</h3><ol><li>Get your custom logo graphic file and use <a href='http://www.gimp.org/downloads/'>an image editor</a> to cut it down to size. If you&#39;re not interested in making more advanced design changes then make sure the logo isn&#39;t higher than ~30px.</li><li>Upload the logo to your webserver. If you&#39;re not sure where to put it, just stick it in the same place you put the <code>eso</code> files.</li><li>Edit the file <code>config/config.php</code>.</li><li>Somewhere <b>after</b> <code>\$config = array(</code> and <b>before</b> <code>);</code>, add the following line: <code>&quot;forumLogo&quot; =&gt; &quot;http://your.server.com/logo.jpg&quot;,</code> (make sure the <a href='http://your.server.com'>your.server.com</a> path points to the place were you uploaded your logo).</li><li>If your logo graphic already contains your forum name, you can hide the text title by editing <code>config/custom.css</code> and adding the following code: <code>#forumTitle {display:none}</code></li><li>Refresh your forum index and check it out!</li></ol><h3>Choosing your skin</h3><ol><li>Log into your forum and click <code>Skins</code>.</li><li>In the <code>Add a new skin</code> section, click <code>Browse</code> to select the skin archive you previously downloaded, then click <code>Add skin</code>.</li><li>Click on the picture of the newly-added skin to activate it.</li></ol><h3>Plugins</h3><p>Plugins are these nifty little things that add extra functionality to your forum. There are a few installed by default, such as emoticons. You can download new plugins and install them by logging in to your forum, clicking <code>Plugins</code>, browsing for the new plugin archive, then clicking <code>Add plugin</code>.</p><p>Tick the checkbox near a plugin name to enable it. Some plugins have settings which you can change by clicking <code>settings</code> to the right of the plugin name.</p><h3>Advanced configuration settings</h3><p>See all the configuration variables in <code>config.default.php</code>? If you find one you want to change, copy the line into <code>config/config.php</code> and modify the value.</p><h3>Languages</h3><p>Your forum is available in a number of languages</a>. You can download language packs and install them by uploading them into the <code>languages</code> of your forum&#39;s installation.</p><p>Each user can select from any of the language packs you have installed, but you can set a default by editing <code>language</code> entry in your advanced configuration settings. See the <i>Advanced configuration settings</i> section above for details of how to do this.</p><p>If you&#39;d like to make a custom change to the language of your forum (ex. changing the footer text), find the appropriate language entry in the language file and override it in <code>config/custom.php</code>.</p><h3>We hope you enjoy using your forum!</h3><p>There are a whole bunch of other things you can do to customize your forum, but that sorta stuff is getting a bit too advanced for this tutorial. You can check out <a href='https://geteso.org/'>geteso.org</a> for further information and/or assistance. Or just drop by and say &quot;Hi&quot;! (We love to hear feedback!)</p><p>Good luck with your forum, $adminUser! <img src='js/x.gif' style='background-position:0 -40px' alt='^_^' class='emoticon'/></p>";
// Store post 1 as prepared statement (user-provided data: forumTitle)
$preparedQueries[] = array(
	"query" => "INSERT INTO {$config["tablePrefix"]}posts (conversationId, memberId, time, title, content) VALUES (1, 1, $time, ?, ?)",
	"types" => "ss",
	"params" => array($post1Title, $post1Content)
);
// Post 2 has no user data, use regular query
$queries[] = "INSERT INTO {$config["tablePrefix"]}posts (conversationId, memberId, time, title, content) VALUES
(2, 1, $time, 'How to use your forum', '<h3>Welcome to your forum, let&#39;s get started!</h3><p>The first thing you may notice about your forum is that the index view, in which conversations are listed, is a little different to the other web forums out there. We&#39;ve chosen to move away from the traditional way of organizing posts; instead of using sub-forums we allow you to quickly find what you want by using search terms that we call &#39;gambits&#39;.</p><p>Gambits are simply phrases that describe what you are looking for. Here are some examples.</p><p>If you want to list only the conversations that contain unread posts, double-click on <b>unread</b> in the bunch of words towards the top of the page.</p><p>To view the conversations that have been active during the last day, double-click <b>active today</b>.</p><p>Or to list new replies to your posts, click on <b>unread</b> and <b>contributor:myself</b>, then click the <b>search</b> button.</p><p>Experiment! Gambit searching is actually pretty powerful and we hope that, once you get used to it, it will make your time spent on forums less about clicking and more about talking. <img src=\'js/x.gif\' style=\'background-position:0 -40px\' alt=\'^_^\' class=\'emoticon\'/></p><h3>Tags</h3><p>Gambits aren&#39;t the only way to locate conversations. You can also search by <i>tags</i>, which are snippets of text that describe the topic of a conversation. To search by a tag simply click on the desired phrase in the <i>tag cloud</i>, which is the collection of words above the gambits.</p><p>Thirdly, you can always enter normal search terms into the search bar, just as you would when using Google or Yahoo. Note that unless the forum is configured otherwise search terms must be at least four characters long.</p><p>So now you know how to find the conversations you are interested in. What other things can your forum do?</p><h3>Having a conversation</h3><p>Forums are all about talking! To get started, click on a conversation and try typing a reply. You may be asked to log in before you are allowed to post. If this is the case, type your username and password into the input fields just under the header and click <i>login</i>, or click <i>join this forum</i> if you are not a registered member.</p><p>You may style your reply using some basic formatting options, such as <b>bold</b> and <i>italic</i>, or you may even add a hyperlink. Try experimenting with different formatting options to see what you can do. To see what your reply will look like once it is posted, simply check the <i>preview</i> box.</p><p>Once you&#39;re ready, click <i>submit post</i> and your reply will be added to the conversation. It&#39;s that simple.</p><h3>Starting a new conversation</h3><p>If there are no conversations that catch your fancy why not make one of your own? It&#39;s exactly the same as replying to an existing conversation but with a few added extras. Click the <i>start a conversation</i> button and you will be asked to enter a conversation title and specify some <i>tags</i> to describe the conversation. For example, if you choose to start a conversation about the movie <i>Ocean&#39;s Eleven</i> you might choose the tags &quot;movie&quot;, &quot;Ocean&#39;s Eleven&quot;, and &quot;George Clooney&quot;. Remember: you can have as many or as few tags as you want! If you can&#39;t be bothered thinking of tags for a topic then you may always add them later, or a moderator may add some on your behalf. Conversations do not need tags to be listed.</p><h3>Changing your avatar and color</h3><p>When people are reading a conversation full of posts they may often overlook your username. To help distinguish your posts from those of other members you may specify a profile image, called an <i>avatar</i>, and choose a color for your posts.</p><p>Simply log in and click <i>my settings</i> in your control bar. On this page you will find a section called <i>appearance settings</i> in which you can upload an avatar and select your favorite color.</p><p>While you&#39;re here you can also choose whether you&#39;d like to be emailed whenever someone invites you into a private conversation and whether to receive email notifications of new replies within starred topics. We&#39;ll talk about these a bit more.</p><h3>Private conversations</h3><p>If you&#39;ve used other web forums before you will probably be familiar with the concept of <i>private messages</i>. Your forum expands on this functionality with <i>private conversations</i>, which are simply conversations that may only be viewed and replied to by certain members. A private conversation may consist of just you and a friend, or it may allow access by an entire group, ex. all of the moderators.</p><p>Apart from allowing only certain members to participate, private conversations are identical to normal conversations. To start a new private conversation click <i>start a conversation</i> and locate the <i>members allowed to view this conversation</i> section. By default it will say &quot;everyone&quot;, but you may change this by adding in a member. Try typing the name of your friend and clicking <i>add</i>. You will notice that it now lists your friend as the only other member allowed to view the conversation, and a <i>private</i> label will appear beneath the conversation title.</p><p>To list all your private conversations from the index, use the <i>private</i> gambit. You can refine your search by using other gambits too, for example <i>private + unread</i> will list only your private messages that contain unread replies.</p><h3>Starred conversations</h3><p>You may occasionally find a conversation that you want to pay close attention to. Rather than memorize the search string you used to find the conversation, you can <i>mark it with a star</i> by clicking on the star icon next to the conversation title. Then, simply use the <i>star</i> gambit in the search to bring up a list of all the conversations that you have starred.</p><p>Another benefit of starring conversations is that you can choose to receive email notifications whenever someone posts a new reply in the starred conversation. Check the appropriate box in <i>my settings</i> and your forum will handle the rest.</p><h3>Draft conversations</h3><p>We know that posting long replies can take a while. Sometimes you may be interrupted or need to resume later. When this is the case, click <i>save draft</i> and your reply or conversation will be saved in a way that is only visible to you. To list conversations that contain one of your draft replies, use the <i>draft</i> gambit.</p><h3>My favorite gambit searches</h3><p>As mentioned previously, gambits are powerful both for forum regulars and guests searching for a specific piece of information on a newly-found forum. Here are some of the gambit combinations that I find useful or interesting! <img src=\'js/x.gif\' style=\'background-position:0 -100px\' alt=\';)\' class=\'emoticon\'/></p><ul><li><b>My new private messages:</b> private + unread</li><li><b>New replies to my posts:</b> contributor:myself + unread - private</li><li><b>Hot topics of the last week:</b> active last 7 days + order by posts</li><li><b>Replies to my recent conversations:</b> author:myself + active last 2 weeks + has replies</li><li><b>All-time most popular conversations:</b> order by posts</li><li><b>My failed conversations:</b> author:myself + !has replies</li><li><b>Random recent conversations:</b> random + active last 1 weeks - private - sticky</li></ul><p>Experiment to find your own favorites!</p><p>The quickest way to become familiar with your forum is to start using it, so get posting!</p><p>We hope you enjoy using our software. <img src=\'js/x.gif\' style=\'background-position:0 0\' alt=\':)\' class=\'emoticon\'/></p><p>- <a href=\'https://github.com/geteso\'>esoBB team</a></p>')";
// Store post 3 as prepared statement (user-provided data: adminUser)
$preparedQueries[] = array(
	"query" => "INSERT INTO {$config["tablePrefix"]}posts (conversationId, memberId, time, title, content) VALUES (3, 1, $time, 'How to customize your forum', ?)",
	"types" => "s",
	"params" => array($post3Content)
);

// Make the "How to customize your forum" conversation only viewable by the administrator.
$queries[] = "INSERT INTO {$config["tablePrefix"]}status (conversationId, memberId, allowed) VALUES (3, 1, 1)";

// Add tags for the default conversations.
$queries[] = "INSERT INTO {$config["tablePrefix"]}tags (conversationId, tag) VALUES (1, 'welcome'), (1, 'introduction'), (2, 'esobb'), (2, 'tutorial'), (2, 'faq'), (2, 'howto'), (3, 'esobb'), (3, 'customization'), (3, 'tutorial'), (3, 'administration')";

?>
