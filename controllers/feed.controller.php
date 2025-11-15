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
 * Feed controller: builds a list of items to be outputted as an Atom
 * feed.
 */
class feed extends Controller {

// Feed data variables, outputted in the view.
var $items = array();
var $updated = "";
var $title = "";
var $subtitle = "";
var $link = "";
var $id = "";

function init()
{
	global $language, $config, $messages;
	
	// Change the root view so that the wrapper is not outputted.
	// Set both the eso view and the controller view to bypass the wrapper
	$this->eso->view = "feed.view.php";
	$this->view = "feed.view.php";
	header("Content-Type: application/xml; charset=" . $language["charset"]);
	
	if ($return = $this->callHook("init")) return;
	
	// Work out what type of feed we're doing, based on the URL: conversation/[id] -> fetch the posts in conversation [id].
	// default -> fetch the most recent posts over the whole forum.
	switch (@$_GET["q2"]) {
	
		// Fetch the posts in a specific conversation.
		case "conversation":
		
			// Get the conversation details.
			$conversationId = (int)$_GET["q3"];
			if (!$conversationId or !($conversation = $this->eso->db->fetchAssoc("SELECT c.conversationId AS id, c.title AS title, c.slug AS slug, c.private AS private, c.posts AS posts, c.startMember AS startMember, c.lastActionTime AS lastActionTime, GROUP_CONCAT(t.tag ORDER BY t.tag ASC SEPARATOR ', ') AS tags FROM {$config["tablePrefix"]}conversations c LEFT JOIN {$config["tablePrefix"]}tags t USING (conversationId) WHERE c.conversationId=$conversationId GROUP BY c.conversationId")))
				$this->eso->fatalError($messages["cannotViewConversation"]["message"]);
							
			// Do we need authentication to view this conversation (ie. is it private or a draft)?
			if ($conversation["private"] or $conversation["posts"] == 0) {
				
				// Try to login with provided credentials.
				if (isset($_SERVER["PHP_AUTH_USER"])) $this->eso->login($_SERVER["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"]);
				
				// Still not logged in?  Ask them again.
				if (!$this->eso->user) {
					header('WWW-Authenticate: Basic realm="esoBB Atom feed"');
				    header('HTTP/1.0 401 Unauthorized');
					$this->eso->fatalError($messages["cannotViewConversation"]["message"]);
				}
				
				// We're logged in now.  So, is this member actually allowed in this conversation?
				if (!($conversation["startMember"] == $this->eso->user["memberId"]
					or ($conversation["posts"] > 0 and (!$conversation["private"] or $this->eso->db->result("SELECT allowed FROM {$config["tablePrefix"]}status WHERE conversationId=$conversationId AND (memberId={$this->eso->user["memberId"]} OR memberId='{$this->eso->user["account"]}')", 0))))) {
					// Nuh-uh. Get OUT!!!
					$this->eso->fatalError($messages["cannotViewConversation"]["message"]);
				}
			}
			
			// Past this point, the user is allowed to view the conversation.
			// Set the title, link, description, etc.
			$this->title = "{$conversation["title"]} - {$config["forumTitle"]}";
			$this->link = $config["baseURL"] . makeLink($conversation["id"], $conversation["slug"]);
			$this->subtitle = $conversation["tags"];
			$this->id = $config["baseURL"] . makeLink("feed", "conversation", $conversation["id"]);
			$this->updated = date("Y-m-d\TH:i:s\Z", $conversation["lastActionTime"]);
			
			// Fetch the 20 most recent posts in the conversation.
			$result = $this->eso->db->query("SELECT postId, name, content, time FROM {$config["tablePrefix"]}posts INNER JOIN {$config["tablePrefix"]}members USING (memberId) WHERE conversationId={$conversation["id"]} AND deleteMember IS NULL ORDER BY time DESC LIMIT 20");
			while (list($id, $member, $content, $time) = $this->eso->db->fetchRow($result)) {
				$this->items[] = array(
					"title" => $member,
					"content" => $this->format($content),
					"link" => $config["baseURL"] . makeLink("post", $id),
					"id" => $config["baseURL"] . makeLink("post", $id),
					"author" => $member,
					"updated" => date("Y-m-d\TH:i:s\Z", $time)
				);
			}
		
			break;
		
		// Fetch the most recent posts over the whole forum.
		default:
		
			// It doesn't matter whether we're logged in or not - just get non-deleted posts from conversations
			// that aren't private!
			$result = $this->eso->db->query("SELECT p.postId, c.title, m.name, p.content, p.time FROM {$config["tablePrefix"]}posts p LEFT JOIN {$config["tablePrefix"]}conversations c USING (conversationId) INNER JOIN {$config["tablePrefix"]}members m ON (m.memberId=p.memberId) WHERE c.private=0 AND c.posts>0 AND p.deleteMember IS NULL ORDER BY p.time DESC LIMIT 20");
			while (list($postId, $title, $member, $content, $time) = $this->eso->db->fetchRow($result)) {
				$this->items[] = array(
					"title" => "$member - $title",
					"content" => $this->format($content),
					"link" => $config["baseURL"] . makeLink("post", $postId),
					"id" => $config["baseURL"] . makeLink("post", $postId),
					"author" => $member,
					"updated" => date("Y-m-d\TH:i:s\Z", $time)
				);
			}
			
			// Set the title, link, description, etc.
			$this->title = "{$language["Recent posts"]} - {$config["forumTitle"]}";
			$this->link = $config["baseURL"];
			$this->id = $config["baseURL"] . makeLink("feed");
			$this->updated = !empty($this->items[0]) ? $this->items[0]["updated"] : date("Y-m-d\TH:i:s\Z");
	}
}

// Format post content to be outputted in the feed.
function format($post)
{
	global $config, $language;
	
	$this->callHook("formatPost", array(&$post));
	
	// Replace empty post links with "go to this post" links.
	$post = preg_replace("`(<a href='" . str_replace("?", "\?", makeLink("post", "(\d+)")) . "'[^>]*>)<\/a>`", "$1{$language["go to this post"]}</a>", $post);
	
	// Convert relative URLs to absolute URLs.
	$post = preg_replace("/<a([^>]*) href='(?!http|ftp|mailto)([^']*)'/i", "<a$1 href='{$config["baseURL"]}$2'", $post);
	$post = preg_replace("/<img([^>]*) src='(?!http|ftp|mailto)([^']*)'/i", "<img$1 src='{$config["baseURL"]}$2'", $post);

	// Remove zero-width joiners.
//	$post = preg_replace("/[\x{200B}-\x{200D}\x{FEFF}]/u", "", $post);
	// For Atom with type="html" in CDATA, we only need to escape the CDATA end marker
	// The HTML content can remain as-is since CDATA will protect it
	$post = str_replace(']]>', ']]&gt;', $post);

	return $post;
}

}

?>
