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
 * Search controller: performs a search with gambits, and gets the tag
 * cloud.
 */
class search extends Controller {

var $view = "search.view.php";
var $tagCloud = array();
var $searchString = "";

// Fields to select, conditions, orders, and more.
var $select = array();
var $from = array();
var $conditions = array();
var $orderBy = array();
var $limit;
var $reverse = false;

// Gambit arrays.
var $gambitCloud = array();
var $aliases = array();
var $gambits = array();

// Results.
var $results = array();
var $resultsTable = array();
var $numberOfConversations = 0;

function __construct()
{
	if (isset($_POST["search"])) redirect("search", "?q2=" . urlencode(desanitize($_POST["search"])));
}

// Initialise: define the gambits, set up some of the search components, set up the page, and perform the search.
function init()
{
	global $config, $language;
	
	// Add the default gambits to the collection.
	// Each gambit is made up of a function and a callback condition that determines if a search "term" matches the gambit.
	// Using callbacks instead of eval() for security - maintains backward compatibility with legacy string conditions
	$searchInstance = &$this; // Reference for closures that need to set $this->matches
	$this->gambits = array_merge($this->gambits, array(
		array(array($this, "gambitStarred"), function($term) use ($language) { return $term == strtolower($language["gambits"]["starred"]); }),
		array(array($this, "gambitDraft"), function($term) use ($language) { return $term == strtolower($language["gambits"]["draft"]); }),
		array(array($this, "gambitTag"), function($term) use ($language) { return strpos($term, strtolower($language["gambits"]["tag:"])) === 0; }),
		array(array($this, "gambitPrivate"), function($term) use ($language) { return $term == strtolower($language["gambits"]["private"]); }),
		array(array($this, "gambitSticky"), function($term) use ($language) { return $term == strtolower($language["gambits"]["sticky"]); }),
		array(array($this, "gambitLocked"), function($term) use ($language) { return $term == strtolower($language["gambits"]["locked"]); }),
		array(array($this, "gambitAuthor"), function($term) use ($language) { return strpos($term, strtolower($language["gambits"]["author:"])) === 0; }),
		array(array($this, "gambitContributor"), function($term) use ($language) { return strpos($term, strtolower($language["gambits"]["contributor:"])) === 0; }),
		array(array($this, "gambitActive"), function($term) use ($language, &$searchInstance) { 
			if (!isset($searchInstance->matches)) $searchInstance->matches = array();
			$result = preg_match($language["gambits"]["gambitActive"], $term, $searchInstance->matches);
			return $result;
		}),
		array(array($this, "gambitHasNPosts"), function($term) use ($language, &$searchInstance) { 
			if (!isset($searchInstance->matches)) $searchInstance->matches = array();
			$result = preg_match($language["gambits"]["gambitHasNPosts"], $term, $searchInstance->matches);
			return $result;
		}),
		array(array($this, "gambitOrderByPosts"), function($term) use ($language) { return $term == strtolower($language["gambits"]["order by posts"]); }),
		array(array($this, "gambitOrderByNewest"), function($term) use ($language) { return $term == strtolower($language["gambits"]["order by newest"]); }),
		array(array($this, "gambitUnread"), function($term) use ($language) { return $term == strtolower($language["gambits"]["unread"]); }),
		array(array($this, "gambitRandom"), function($term) use ($language) { return $term == strtolower($language["gambits"]["random"]); }),
		array(array($this, "gambitReverse"), function($term) use ($language) { return $term == strtolower($language["gambits"]["reverse"]); }),
		array(array($this, "gambitMoreResults"), function($term) use ($language) { return $term == strtolower($language["gambits"]["more results"]); }),
		array(array($this, "gambitLimit"), function($term) use ($language) { return strpos($term, strtolower($language["gambits"]["limit:"])) === 0; }),
		array(array($this, "fulltext"), function($term) { return $term; }),
	));
	
	// Add the default gambits to the gambit cloud: gambit text => css class to apply.
	$this->gambitCloud += array(
		$language["gambits"]["active last ? hours"] => "s4",
		$language["gambits"]["active last ? days"] => "s5",
		$language["gambits"]["active today"] => "s2",
		$language["gambits"]["author:"] . $language["gambits"]["member"] => "s5",
		$language["gambits"]["contributor:"] . $language["gambits"]["member"] => "s5",
		$language["gambits"]["dead"] => "s4",
		$language["gambits"]["has replies"] => "s2",
		$language["gambits"]["has &gt;10 posts"] => "s4",
		$language["gambits"]["limit:"] . $language["gambits"]["100"] => "s2",
		$language["gambits"]["locked"] => "s4 lockedText",
//		$language["gambits"]["more results"] => "s2",
		$language["gambits"]["order by newest"] => "s4",
		$language["gambits"]["order by posts"] => "s2",
		$language["gambits"]["random"] => "s5",
		$language["gambits"]["reverse"] => "s4",
		$language["gambits"]["sticky"] => "s2 stickyText",
	);
	// Only show the contributor:myself and author:myself gambits if there is a user logged in.
	if ($this->eso->user) {
		$this->gambitCloud += array(
			$language["gambits"]["contributor:"] . $language["gambits"]["myself"] => "s4",
			$language["gambits"]["author:"] . $language["gambits"]["myself"] => "s2",
			$language["gambits"]["draft"] => "s1 draftText",
			$language["gambits"]["private"] => "s1 privateText",
			$language["gambits"]["starred"] => "s1 starredText",
			$language["gambits"]["unread"] => "s1"
		);
	}
	
	// Add default aliases. An alias is a string of text which is just shorthand for a more complex gambit.
	$this->aliases += array(
		$language["gambits"]["active today"] => $language["gambits"]["active 1 day"],
		$language["gambits"]["has replies"] => $language["gambits"]["has &gt; 1 post"],
		$language["gambits"]["has no replies"] => $language["gambits"]["has 0 posts"],
		$language["gambits"]["dead"] => $language["gambits"]["active &gt; 30 day"]
	);
	
	// Define the columns of the search results table.
	if ($this->eso->user) $this->resultsTable[] = array("class" => "star", "content" => "columnStar");
	if (!empty($config["showAvatarThumbnails"]) and (isset($this->eso->user) and $this->eso->user["avatarAlignment"] != "none") or ($_SESSION["avatarAlignment"] != "none")) $this->resultsTable[] = array("class" => "avatar", "content" => "columnAvatar");
	$this->resultsTable[] = array("title" => $language["Conversation"], "class" => "conversation", "content" => "columnConversation");
	$this->resultsTable[] = array("title" => $language["Posts"], "class" => "posts", "content" => "columnPosts");
	$this->resultsTable[] = array("title" => $language["Started by"], "class" => "author", "content" => "columnAuthor");
	$this->resultsTable[] = array("title" => $language["Last reply"], "class" => "lastPost", "content" => "columnLastReply");
	
	// Mark all conversations as read if requested.
	if (isset($_GET["markAsRead"]) and $this->eso->user and !defined("AJAX_REQUEST"))
	 	$this->markAllConversationsAsRead();
	
	// Construct the SELECT and FROM parts of the final query that gets the result details.
	$markedAsRead = !empty($this->eso->user["markedAsRead"]) ? $this->eso->user["markedAsRead"] : "0";
	$memberId = $this->eso->user ? $this->eso->user["memberId"] : 0;
	$this->select = array("c.conversationId AS id", "c.title AS title", "c.slug AS slug", "c.sticky AS sticky", "c.private AS private", "c.locked AS locked", "c.posts AS posts", "sm.name AS startMember", "c.startMember AS startMemberId", "sm.avatarFormat AS avatarFormat", "c.startTime AS startTime", "lpm.name AS lastPostMember", "c.lastPostMember AS lastPostMemberId", "c.lastPostTime AS lastPostTime", "GROUP_CONCAT(t.tag ORDER BY t.tag ASC SEPARATOR ', ') AS tags", "(IF(c.lastPostTime IS NOT NULL,c.lastPostTime,c.startTime)>$markedAsRead AND (s.lastRead IS NULL OR s.lastRead<c.posts)) AS unread", "s.starred AS starred", "sm.color AS color", "CONCAT(" . implode(",',',", $this->eso->labels) . ") AS labels");
	$this->from = array(
		"{$config["tablePrefix"]}conversations c",
		"LEFT JOIN {$config["tablePrefix"]}tags t USING (conversationId)",
		"LEFT JOIN {$config["tablePrefix"]}status s ON (s.conversationId=c.conversationId AND s.memberId=$memberId)",
		"INNER JOIN {$config["tablePrefix"]}members sm ON (c.startMember=sm.memberId)",
		"LEFT JOIN {$config["tablePrefix"]}members lpm ON (c.lastPostMember=lpm.memberId)"
	);
		
	if (!defined("AJAX_REQUEST")) {
	
		// Assign the latest search to a session variable.
		if (isset($_GET["q"])) $this->searchString = $_GET["q"];
 		elseif (@$_GET["q1"] == "search") $this->searchString = $_GET["q2"];
 		$_SESSION["search"] = $this->searchString;
		
		// Add JavaScript language definitions and variables.
		$this->eso->addLanguageToJS("Starred", "Unstarred", array("gambits", "member"), array("gambits", "tag:"), array("gambits", "more results"));
		$this->eso->addVarToJS("updateCurrentResultsInterval", $config["updateCurrentResultsInterval"]);
		$this->eso->addVarToJS("checkForNewResultsInterval", $config["checkForNewResultsInterval"]);
		
		// Add a link to the RSS feed in the bar.
		$this->eso->addToBar("right", "<a href='" . makeLink("feed") . "' id='rss' class='vl'><span class='button buttonSmall'><input type='submit' value='{$language["RSS"]}'></span></a>", 500);
		
		// Update the user's last action.
		$this->eso->updateLastAction("");
		
		// Get the most common tags from the tags table and assign them a text-size class based upon their frequency.
		$result = $this->eso->db->query("SELECT t.tag, COUNT(t.tag) AS count FROM {$config["tablePrefix"]}tags t LEFT JOIN {$config["tablePrefix"]}conversations c ON (t.conversationId=c.conversationId) WHERE c.private=0 AND c.posts>=1 GROUP BY t.tag ORDER BY count DESC LIMIT {$config["numberOfTagsInTagCloud"]}");
		$tags = array();
		if ($rows = $this->eso->db->numRows($result)) {
			for ($i = 1; list($tag) = $this->eso->db->fetchRow($result); $i++) {
				$this->tagCloud[$tag] = "s" . ceil($i * (5 / $rows));
				if ($i < 10) $tags[] = $tag;
			}
		}
		
		// Add meta tags to the header, the "Mark all conversations as read" link to the footer, and a "Start a conversation" link.
		if (!empty($config["metaKeywords"])) {
			$this->eso->addToHead("<meta name='keywords' content='" . implode(",", $config["metaKeywords"]) . "'/>");
		} else {
			$this->eso->addToHead("<meta name='keywords' content='" . implode(",", $tags) . "'/>");
		}
		list($lastTag) = array_splice($tags, count($tags) - 1, 1);
		if (!empty($config["metaDescription"])) {
			$this->eso->addToHead("<meta name='description' content='" . sanitizeHTML($config["forumDescription"]) . "'/>");
			$this->eso->addToHead("<meta property='og:description' content='" . sanitizeHTML($config["forumDescription"]) . "'/>");
			$this->eso->addToHead("<meta name='twitter:description' content='" . sanitizeHTML($config["forumDescription"]) . "'/>");
		} else {
			$this->eso->addToHead("<meta name='description' content='" . sprintf($language["forumDescription"], $config["forumTitle"], implode(", ", $tags), $lastTag) . "'/>");
			$this->eso->addToHead("<meta property='og:description' content='" . sprintf($language["forumDescription"], $config["forumTitle"], implode(", ", $tags), $lastTag) . "'/>");
			$this->eso->addToHead("<meta name='twitter:description' content='" . sprintf($language["forumDescription"], $config["forumTitle"], implode(", ", $tags), $lastTag) . "'/>");
		}
		if ($this->eso->user) $this->eso->addToFooter("<a href='" . makeLink("?markAsRead") . "' id='markAsRead'><span class='button buttonSmall'><input type='submit' value='{$language["Mark all conversations as read"]}'></span></a>", 200);
		if ($this->eso->user) $this->eso->addToFooter("<a href='" . makeLink("conversation/new") . "' id='startConversation'><span class='button buttonSmall'><input type='submit' value='{$language["Start a conversation"]}'></span></a>", 300);
		
		// If this is not technically the homepage (if it's a search page) the we don't want it to be indexed.
		if (@$_GET["q1"] == "search") $this->eso->addToHead("<meta name='robots' content='noindex, noarchive'/>");
		elseif (@$_GET["q1"]) redirect("search", "?q2=" . urlencode(desanitize(@$_GET["q1"])));
				
	}
	
	$this->callHook("init");
	
	// Last, but definitely not least... perform the search!
	if (!defined("AJAX_REQUEST")) $this->results = $this->doSearch($this->searchString);
}

// Update the "markedAsRead" field in the user's database row to the current time.
// Any conversations with last activity before this time will be regarded as "read".
function markAllConversationsAsRead()
{
	global $config;
	$this->eso->db->query("UPDATE {$config["tablePrefix"]}members SET markedAsRead=" . time() . " WHERE memberId={$this->eso->user["memberId"]}");
	$this->eso->user["markedAsRead"] = $_SESSION["user"]["markedAsRead"] = time();
}

// Register a custom gambit:
// $text is the text that will appear in the gambit cloud.
// $class is the CSS className that will be applied to the text.
// $function is the function to be called if the gambit is detected
// 		(called with call_user_func($function, $gambit, $negate))
// $condition is either a callback function that takes $term and returns bool, or a string containing eval() code (deprecated)
// 		For new gambits, use a callback: function($term) { return $term == "sticky"; }
// 		Legacy string conditions are still supported but should be migrated to callbacks
function registerGambit($text, $class, $function, $condition)
{
	$this->gambitCloud[$text] = $class;
	// If condition is a callable, use it directly; otherwise store as string for backward compatibility
	$this->gambits[] = array($function, is_callable($condition) ? $condition : $condition);
}

// Apply a condition to the search results.
// This takes effect when collecting conversation IDs through the following query:
// SELECT DISTINCT conversationId FROM $table WHERE $condition
function condition($table, $condition, $negate = false)
{
	$condition = "($condition)";
	if (in_array(array($table, $condition, $negate), $this->conditions)) return;
	$this->conditions[] = array($table, $condition, $negate);
}

// Apply an order to the search results.
function orderBy($order)
{
	$this->orderBy[] = $order;
}

// Apply a limit to the search results.
function limit($limit)
{
	$this->limit = $limit;
}

// Add an expression to the "select" part of the query which gets conversation details.
function select($expression)
{
	$this->select[] = $expression;
}

// Add a table or a JOIN clause to the "from" part of the query which gets conversation details.
function addTable($table)
{
	$this->from[] = $table;
}

// Add an array of words to be highlighted in the search results and also after clicking through to a conversations.
function highlight($wordList)
{
	foreach ($wordList as $k => $v) {
		if (!$v or in_array($v, $_SESSION["highlight"])) continue;
		$_SESSION["highlight"][] = $v;
	}
}

// Deconstruct a search query and construct a list of conversation IDs that fulfill it.
function getConversationIDs($search = "")
{
	global $config, $language;
	
	// Add some preliminary conditions to the search results.
	// These make sure conversations that the user isn't allowed to see are filtered out.
	if (!$this->eso->user) $this->condition("conversations", "c.posts!=0 AND c.private=0");
	else $this->condition("conversations", "c.startMember={$this->eso->user["memberId"]} OR (c.posts>0 AND (c.private=0 OR EXISTS (SELECT allowed FROM {$config["tablePrefix"]}status WHERE conversationId=c.conversationId AND memberId IN ('{$this->eso->user["account"]}',{$this->eso->user["memberId"]}) AND allowed=1)))");
	
	// Process the search string into individial terms.
	// Replace all "-" signs with "+!", and then split the string by "+".  Negated terms will then be prefixed with "!".
	// Only keep the first 5 terms, just to keep the load on the database down!
	$terms = !empty($search) ? explode("+", strtolower(str_replace("-", "+!", trim($search, " +-")))) : array();
//	$terms = array_slice($terms, 0, 10);
	$terms = array_slice(array_filter($terms), 0, 5);
	
	// Take each term, match it with a gambit, and execute the gambit's function.
	foreach ($terms as $term) {

		// Are we dealing with a negated search term, ie. prefixed with a "!"?
		$term = trim($term);
		if ($negate = ($term[0] == "!")) $term = trim($term, "! ");

		// If the term is an alias, translate it into the appropriate gambit.
		if (array_key_exists($term, $this->aliases)) $term = $this->aliases[$term];

		// Find a matching gambit by evaluating each gambit's condition.
		foreach ($this->gambits as $gambit) {
			list($function, $condition) = $gambit;
			// If condition is a callable, use it directly; otherwise evaluate as legacy string (deprecated)
			if (is_callable($condition)) {
				$matches = call_user_func($condition, $term);
			} elseif (is_string($condition) && !empty(trim($condition))) {
				// Legacy eval() support - deprecated but maintained for backward compatibility
				// $term is sanitized user input, but eval() is still dangerous
				// This should be migrated to callbacks
				$matches = @eval($condition);
			} else {
				$matches = false;
			}
			if ($matches) {
				call_user_func_array($function, array(&$this, $term, $negate));
				break;
			}
		}
	}
	
	// If an order for the search results has not been specified, apply a default.
	// For guests, order by sticky and then last post time.
	// For members, order by sticky+unread and then last post time.
	if (!count($this->orderBy)) {
		if (!$this->eso->user) {
			$this->orderBy("c.sticky DESC");
			$this->orderBy("c.lastPostTime DESC");
		} else {
			$this->orderBy("IF(c.sticky AND ((SELECT lastRead FROM {$config["tablePrefix"]}status s WHERE conversationId=c.conversationId AND s.memberId={$this->eso->user["memberId"]}) IS NULL OR (SELECT lastRead FROM {$config["tablePrefix"]}status s WHERE conversationId=c.conversationId AND s.memberId={$this->eso->user["memberId"]})<c.posts),1,0) DESC");
			$this->orderBy("c.lastPostTime DESC");
		}
	}
	
	// Now we need to loop through the conditions and run them as queries one-by-one. When a query returns a selection
	// of conversation IDs, subsequent queries are restricted to filtering those conversation IDs.
	$goodConversationIds = $badConversationIds = array();
	$conversationConditions = array();
	$idCondition = "";
	foreach ($this->conditions as $v) {
		list($table, $condition, $negate) = $v;
		
		// If the condition is based on the conversations table, we'll save it for inclusion in the final query.
		if ($table == "conversations") {
			$conversationConditions[] = $condition;
			continue;
		}
		
		// Construct a query that will find conversation IDs that meet the condition.
		$prefix = strpos($table, "conversations c") !== false ? "c." : "";
		$query = "SELECT DISTINCT {$prefix}conversationId FROM {$config["tablePrefix"]}{$v[0]} WHERE $condition $idCondition";
	
		// Get the list of conversation IDs so that the next condition can use it in its query.
		$result = $this->eso->db->query($query);
		$ids = array();
		while (list($conversationId) = $this->eso->db->fetchRow($result)) $ids[] = $conversationId;
		
		// If this condition is negated, then add the IDs to the list of bad conversations.
		// If the condition is not negated, set the list of good conversations to the IDs, provided there are some.
		if ($negate) $badConversationIds = array_merge($badConversationIds, $ids);
		elseif (count($ids)) $goodConversationIds = $ids;
		else return false;
		
		// Strip bad conversation IDs from the list of good conversation IDs.
		if (count($goodConversationIds)) {
			$goodConversationIds = array_diff($goodConversationIds, $badConversationIds);
			if (!count($goodConversationIds)) return false;
		}
		
		// This will be the condition for the next query that restricts or eliminates conversation IDs.
		if (count($goodConversationIds))
			$idCondition = " AND conversationId IN (" . implode(",", $goodConversationIds) . ")";
		elseif (count($badConversationIds))
			$idCondition = " AND conversationId NOT IN (" . implode(",", $badConversationIds) . ")";
	}
	
	// Reverse the order if necessary - swap DESC and ASC.
	if ($this->reverse) {
		foreach ($this->orderBy as $k => $v)
			$this->orderBy[$k] = strtr($this->orderBy[$k], array("DESC" => "ASC", "ASC" => "DESC"));
	}
	
	// Set a default limit if none has previously been set.
	if (!$this->limit) $this->limit = $config["results"] + 1;
	
	// Collect the query components...
	$conditions = $idCondition ? array_merge($conversationConditions, array(substr($idCondition, 5))) : $conversationConditions;
	$components = array(
		"select" => array("c.conversationId"),
		"from" => array("{$config["tablePrefix"]}conversations c"),
		"where" => $conditions,
		"orderBy" => $this->orderBy,
		"limit" => $this->limit
	);
	
	$this->callHook("getConversationIds", array(&$components));
	
	// ...and construct and execute the query!
	$query = $this->eso->db->constructSelectQuery($components);
	$result = $this->eso->db->query($query);
	
	// Collect the final set of conversation IDs and return it.
	$conversationIds = array();
	while (list($conversationId) = $this->eso->db->fetchRow($result)) $conversationIds[] = $conversationId;
	return count($conversationIds) ? $conversationIds : false;
}

// Perform a search and return results.
function doSearch($search = "")
{
	global $config;
	
	// Reset highlighted keywords.
	$_SESSION["highlight"] = array();
	
	// If they are searching for something, take some flood control measures.
	if ($search and $config["searchesPerMinute"] > 0) {
	
		// If we have a record of their searches in the session, check how many searches they've performed in the last 
		// minute.
		if (!empty($_SESSION["searches"])) {
			// Clean anything older than 60 seconds out of the searches array.
			foreach ($_SESSION["searches"] as $k => $v) {
				if ($v < time() - 60) unset($_SESSION["searches"][$k]);
			}
			// Have they performed >= $config["searchesPerMinute"] searches in the last minute? If so, don't continue.
			if (count($_SESSION["searches"]) >= $config["searchesPerMinute"]) {
				$this->eso->message("waitToSearch", true, array(60 - time() + min($_SESSION["searches"])));
				return;
			}
		}
		
		// However, if we don't have a record in the session, use the MySQL actions table.
		else {
			// Get the user's IP address.
			$ip = cookieIp();
			$memberId = $this->eso->user ? $this->eso->user["memberId"] : 0;
			// Have they performed >= $config["searchesPerMinute"] searches in the last minute?
			if ($this->eso->db->result("SELECT COUNT(*) FROM {$config["tablePrefix"]}actions WHERE ip=$ip AND action='search' AND time>UNIX_TIMESTAMP()-60", 0) >= $config["searchesPerMinute"]) {
				$this->eso->message("waitToSearch", true, 60);
				return;
			}
			// Log this search in the actions table.
			$this->eso->db->query("INSERT INTO {$config["tablePrefix"]}actions (ip, memberId, action, time) VALUES ($ip, $memberId, 'search', UNIX_TIMESTAMP())");
			// Proactively clean the actions table of searches older than 60 seconds.
			$this->eso->db->query("DELETE FROM {$config["tablePrefix"]}actions WHERE action='search' AND time<UNIX_TIMESTAMP()-60");
		}
		
		// Log this search in the session array.
		if (!isset($_SESSION["searches"]) or !is_array($_SESSION["searches"])) $_SESSION["searches"] = array();
		$_SESSION["searches"][] = time();
		
	}
	
	// Get the conversation IDs that match the search terms.
	if (!$conversationIds = $this->getConversationIDs($search)) return;
	$conversationIds = implode(",", $conversationIds);
	
	// Construct a query to get details for all of the specified conversations.
	$components = array(
		"select" => $this->select,
		"from" => $this->from,
		"where" => "c.conversationId IN ($conversationIds)",
		"groupBy" => "c.conversationId",
		"orderBy" => "FIELD(c.conversationId,$conversationIds)"
	);
	
	$this->callHook("beforeGetResults", array(&$components));
	
	// Put the query together and execute it.
	$query = $this->eso->db->constructSelectQuery($components);
	$result = $this->eso->db->query($query);
	
	// Put the details of the conversations into an array to be displayed in the view.
	$results = array();
	$conversationsToDisplay = $this->limit == ($config["results"] + 1) ? $config["results"] : $config["moreResults"];
	if ($this->numberOfConversations = $this->eso->db->numRows($result)) {
		for ($i = 0; $i < $conversationsToDisplay and ($conversation = $this->eso->db->fetchAssoc($result)); $i++)
			$results[] = $conversation;
	}
	
	$this->callHook("afterGetResults", array(&$results));
	
	return $results;
}

// Run AJAX actions.
function ajax()
{
	global $config, $language;
	
	if ($return = $this->callHook("ajax", null, true)) return $return;
	
	switch (@$_POST["action"]) {
		
		// Perform a search and return the results HTML.
		case "search":
			$this->view = "searchResults.inc.php";
			$this->searchString = $_SESSION["search"] = $_POST["query"];
			$this->results = $this->doSearch($this->searchString);
			ob_start();
			$this->render();
			return ob_get_clean();
			break;
		
		// Update the current resultset details (unread, last post details, post count.)
		case "updateCurrentResults":
		
			// Work out which conversations we need to get details for (according to $_POST["conversationIds"].)
			$conversationIds = explode(",", $_POST["conversationIds"]);
			foreach ($conversationIds as $k => $v) if (!($conversationIds[$k] = (int)$v)) unset($conversationIds[$k]);
			$conversationIds = implode(",", array_unique($conversationIds));
			if (!$conversationIds) return;
			
			// We're going to run a query to get the details of all specified conversations.
			$markedAsRead = !empty($this->eso->user["markedAsRead"]) ? $this->eso->user["markedAsRead"] : "0";
			$memberId = $this->eso->user ? $this->eso->user["memberId"] : 0;
			$allowedPredicate = !$this->eso->user ? "c.posts>0 AND c.private=0" : "c.startMember={$this->eso->user["memberId"]} OR (c.posts>0 AND (c.private=0 OR s.allowed OR (SELECT allowed FROM {$config["tablePrefix"]}status WHERE conversationId=c.conversationId AND memberId='{$this->eso->user["account"]}')))";
			$query = "SELECT c.conversationId, sm.color AS color, (IF(c.lastPostTime IS NOT NULL,c.lastPostTime,c.startTime)>$markedAsRead AND (s.lastRead IS NULL OR s.lastRead<c.posts)) AS unread, lpm.name AS lastPostMember, c.lastPostMember AS lastPostMemberId, c.lastPostTime AS lastPostTime, c.posts AS posts, s.starred AS starred
				FROM {$config["tablePrefix"]}conversations c
				LEFT JOIN {$config["tablePrefix"]}status s ON (s.conversationId=c.conversationId AND s.memberId=$memberId)
				LEFT JOIN {$config["tablePrefix"]}members sm ON (c.startMember=sm.memberId)
				LEFT JOIN {$config["tablePrefix"]}members lpm ON (c.lastPostMember=lpm.memberId)
				WHERE c.conversationId IN ($conversationIds) AND ($allowedPredicate)";
			$result = $this->eso->db->query($query);
			
			// Loop through these conversations and construct an array of details to return in JSON format.
			$conversations = array();
			while (list($id, $color, $unread, $lastPostMember, $lastPostMemberId, $lastPostTime, $postCount, $starred) = $this->eso->db->fetchRow($result)) {
				$conversations[$id] = array(
					"color" => $color,
					"unread" => !$this->eso->user or $unread,
					"lastPostMember" => "<a href='" . makeLink("profile", $lastPostMemberId) . "'>$lastPostMember</a>",
					"lastPostTime" => relativeTime($lastPostTime),
					"postCount" => $postCount,
					"starred" => (int)$starred
				);
			}
			
			return array("conversations" => $conversations, "statistics" => $this->eso->getStatistics());
			break;
		
		// Check for differing results to the current resultset (i.e. new conversations) and notify the user if there is
		// new activity.
		case "checkForNewResults":
			
			$this->searchString = $_POST["query"];
			
			// If the "random" gambit is in the search string, then don't go any further (because the results will 
			// obviously differ!)
			$terms = $this->searchString ? explode("+", strtolower(str_replace("-", "+!", trim($this->searchString, " +-")))) : array();
			foreach ($terms as $v) {
				if (trim($v) == $language["gambits"]["random"]) return array("newActivity" => false);
			}
			
			// Search flood control - if the user has performed >= $config["searchesPerMinute"] searches in the last 
			// minute, don't bother checking for new results.
			if ($this->searchString and $config["searchesPerMinute"] > 0) {
				// Check the session record of searches if it exists.
				if (!empty($_SESSION["searches"])) {
					foreach ($_SESSION["searches"] as $k => $v) {
						if ($v < time() - 60) unset($_SESSION["searches"][$k]);
					}
					if (count($_SESSION["searches"]) >= $config["searchesPerMinute"]) return array("newActivity" => false);
				// Otherwise, check the database.
				} else {
					$ip = cookieIp();
					if ($this->eso->db->result("SELECT COUNT(*) FROM {$config["tablePrefix"]}actions WHERE ip=$ip AND action='search' AND time>UNIX_TIMESTAMP()-60", 0) >= $config["searchesPerMinute"]) return array("newActivity" => false);
				}
			}
			
			// Get a list of conversation IDs that match the search string.
			$this->limit($config["results"]);
			$newConversationIds = $this->getConversationIDs($this->searchString);
			
			// Get an array of conversationId's are in the current resultset.
			$conversationIds = explode(",", $_POST["conversationIds"]);
			foreach ($conversationIds as $k => $v) if (!($conversationIds[$k] = (int)$v)) unset($conversationIds[$k]);
			$conversationIds = array_unique($conversationIds);

			// Get the difference of the two sets of conversationId's.
			if (!is_array($newConversationIds) or !is_array($conversationIds)) return array("newActivity" => false);
			$diff = array_diff($newConversationIds, $conversationIds);
			return array("newActivity" => count($diff));
	}
	
}

// Gambit functions.

// Unread gambit: get conversations that are unread.
function gambitUnread(&$search, $term, $negate)
{
	global $config;
	if (!$this->eso->user) return false;
	$markedAsRead = !empty($this->eso->user["markedAsRead"]) ? $this->eso->user["markedAsRead"] : "NULL";
	$lastRead = "(SELECT lastRead FROM {$config["tablePrefix"]}status s WHERE conversationId=c.conversationId AND s.memberId={$this->eso->user["memberId"]})";
	$search->condition("conversations", ($negate ? "NOT " : "") . "(IF(c.lastPostTime IS NOT NULL,c.lastPostTime,c.startTime)>$markedAsRead AND ($lastRead IS NULL OR $lastRead<c.posts))");
}

// Private gambit: get private conversations.
function gambitPrivate(&$search, $term, $negate)
{
	$search->condition("conversations", "c.private=" . ($negate ? "0" : "1"));
}

// Starred gambit: get starred conversations.
function gambitStarred(&$search, $term, $negate)
{
	$id = $this->eso->user ? $this->eso->user["memberId"] : "0";
	$search->condition("status", "memberId=$id AND starred=1", $negate);
}

// Tag gambit: get conversations with a specific tag.
function gambitTag(&$search, $term, $negate)
{
	global $language;
	$term = trim(substr($term, strlen($language["gambits"]["tag:"])));
	$search->condition("tags", "tag='$term'", $negate);
}

// Active gambit: get conversations active in the specified time period.
function gambitActive(&$search, $term, $negate)
{
	global $language;
	switch ($search->matches["c"]) {
		case $language["gambits"]["minute"]: $search->matches["b"] *= 60; break;
		case $language["gambits"]["hour"]: $search->matches["b"] *= 3600; break;
		case $language["gambits"]["day"]: $search->matches["b"] *= 86400; break;
		case $language["gambits"]["week"]: $search->matches["b"] *= 604800; break;
		case $language["gambits"]["month"]: $search->matches["b"] *= 2626560; break;
		case $language["gambits"]["year"]: $search->matches["b"] *= 31536000;
	}
	$search->matches["a"] = (!$search->matches["a"] or $search->matches["a"] == $language["gambits"]["last"]) ? "<=" : str_replace(array("&gt;", "&lt;"), array(">", "<"), $search->matches["a"]);
	if ($negate) {
		switch ($search->matches["a"]) {
			case "<": $search->matches["a"] = ">="; break;
			case "<=": $search->matches["a"] = ">"; break;
			case ">": $search->matches["a"] = "<="; break;
			case ">=": $search->matches["a"] = "<";
		}
	}
	$search->condition("conversations", "UNIX_TIMESTAMP() - {$search->matches["b"]} {$search->matches["a"]} IF(c.lastPostTime IS NOT NULL,c.lastPostTime,c.startTime)");
}

// Author gambit: get conversations with a particular author.
function gambitAuthor(&$search, $term, $negate)
{
	global $config, $language;
	$term = trim(substr($term, strlen($language["gambits"]["author:"])));
	if ($term == $language["gambits"]["myself"]) $term = $search->eso->user["name"];
	$search->condition("conversations", "c.startMember" . ($negate ? "!" : "") . "=(SELECT memberId FROM {$config["tablePrefix"]}members WHERE name='$term')");
}

// Contributor gambit: get conversations which contain posts by a particular member.
function gambitContributor(&$search, $term, $negate)
{
	global $config, $language;
	$term = trim(substr($term, strlen($language["gambits"]["contributor:"])));
	if ($term == $language["gambits"]["myself"]) $term = $search->eso->user["name"];
	$search->condition("posts", "memberId=(SELECT memberId FROM {$config["tablePrefix"]}members WHERE name='$term')", $negate);
}

// More results gambit: bump up the limit to display more results.
function gambitMoreResults(&$search, $term, $negate)
{
	global $config;
	if (!$negate) $search->limit($config["moreResults"]);
}

// Limit gambit: display a particular number of conversations.
function gambitLimit(&$search, $term, $negate)
{
	global $language;
	$term = trim(substr($term, strlen($language["gambits"]["limit:"])));
	if (!$negate && is_numeric($term)) $search->limit($term);
}

// Draft gambit: get conversations which are drafts or contain a draft for the logged in user.
function gambitDraft(&$search, $term, $negate)
{
	$id = $this->eso->user ? $this->eso->user["memberId"] : "0";
	$search->condition("status", "memberId=$id AND draft IS NOT NULL", $negate);
}

// Posts gambit: get conversations with a particular number of posts.
function gambitHasNPosts(&$search, $term, $negate)
{
	$search->matches["a"] = (!$search->matches["a"]) ? "=" : desanitize($search->matches["a"]);
	if ($negate) {
		switch ($search->matches["a"]) {
			case "<": $search->matches["a"] = ">="; break;
			case "<=": $search->matches["a"] = ">"; break;
			case ">": $search->matches["a"] = "<="; break;
			case ">=": $search->matches["a"] = "<"; break;
			case "=": $search->matches["a"] = "!=";
		}
	}
	$search->condition("conversations", "posts {$search->matches["a"]} {$search->matches["b"]}");
}

// Order by posts gambit: order the conversations by the number of posts.
function gambitOrderByPosts(&$search, $term, $negate)
{
	$search->orderBy("c.posts " . ($negate ? "ASC" : "DESC"));
}

// Order by newest gambit: order the conversations by their creation time.
function gambitOrderByNewest(&$search, $term, $negate)
{
	$search->orderBy("c.startTime " . ($negate ? "ASC" : "DESC"));
}

// Sticky gambit: get conversations which are stickied.
function gambitSticky(&$search, $term, $negate)
{
	$search->condition("conversations", "sticky=" . ($negate ? "0" : "1"));
}

// Random gambit: order the conversations randomly.
function gambitRandom(&$search, $term, $negate)
{
	if (!$negate) $search->orderBy("RAND()");
}

// Reverse gambit: reverse the order of the conversations.
function gambitReverse(&$search, $term, $negate)
{
	if (!$negate) $search->reverse = true;
	$search->condition("conversations", "(c.lastPostTime) IS NOT NULL", $negate);
}

// Locked gambit: get conversations which are locked.
function gambitLocked(&$search, $term, $negate)
{
	$search->condition("conversations", "locked=" . ($negate ? "0" : "1"));
}

// Fulltext gambit: get conversations which contain posts containing particular keywords.
function fulltext(&$search, $term, $negate)
{
	$term = str_replace("&quot;", '"', $term);
	$search->condition("posts", "MATCH (title, content) AGAINST ('$term' IN BOOLEAN MODE)", $negate);
	
	// Add the keywords in $term to be highlighted. Make sure we keep ones "in quotes" together.
	$words = array();
	if (preg_match_all('/"(.+?)"/', $term, $matches)) {
		$words += $matches[1];
		$term = preg_replace('/".+?"/', '', $term);
	}
	$words = array_unique(array_merge($words, explode(" ", $term)));
	$search->highlight($words);
}

}

?>
