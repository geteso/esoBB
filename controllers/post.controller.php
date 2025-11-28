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
 * Post controller: finds the conversation and position of a given post,
 * and redirects there.
 */
class post extends Controller {

function init()
{
	// Permanently redirected to the conversation.
	header("HTTP/1.1 301 Moved Permanently");
	
	if (!empty($_GET["q2"]) and $postId = (int)$_GET["q2"]) {
		
		global $config;
		
		// Get the conversationId, slug, and the number of posts in the conversation before the post we're redirecting to.
		$result = $this->eso->db->query("SELECT c.conversationId, c.slug,
			(SELECT COUNT(*) FROM {$config["tablePrefix"]}posts p2 WHERE p2.conversationId=c.conversationId AND time<p.time)
			FROM {$config["tablePrefix"]}posts p LEFT JOIN {$config["tablePrefix"]}conversations c USING (conversationId)
			WHERE p.postId=$postId");
		if (!$this->eso->db->numRows($result)) redirect("");
		list($conversationId, $slug, $startFrom) = $this->eso->db->fetchRow($result);
		$startFrom = max(0, floor($startFrom - $config["postsPerPage"] / 2));
					
		// Redirect.
		redirect($conversationId, $slug, "?start=$startFrom", "#p$postId");
	}
	
	// No post ID given?  Back home we go.
	redirect("");
}

}

