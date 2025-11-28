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
 * Profile controller: fetches member information to be displayed on the
 * profile view.
 */
class profile extends Controller {

public ?string $view = "profile.view.php";
public array $member = [];
public array $sections = [];

// Set up the page and fetch the member data.
function init()
{
	if (defined("AJAX_REQUEST")) return;
	
	// If there's a member ID specified in the URL, use that.  Otherwise, use the currently logged in user.
	if (!empty($_GET["q2"])) $memberId = (int)$_GET["q2"];
	elseif ($this->eso->user) $memberId = $this->eso->user["memberId"];
	else $memberId = false;
	
	// Get the member data: if the member doesn't exist, redirect to the index.
	if (!$memberId or !($this->member = $this->getMember($memberId))) {
		$this->eso->message("memberDoesntExist", false);
		redirect("");
	}
	
	// Set the title.
	global $language;
	$this->title = sprintf($language["profile"], $this->member["name"]);
	$this->updateLastAction($memberId);
	
	$this->callHook("init");
}

// Run AJAX actions.
function ajax()
{
	if ($return = $this->callHook("ajax", null, true)) return $return;
}

// Add a section to the profile page.
function addSection($sectionHTML, $position = false)
{
	addToArray($this->sections, $sectionHTML, $position);
}

// Fetch a member's details from the database.
function getMember($memberId)
{
	global $config;
	if (empty($memberId)) return false;

	// Construct the query components.
	$select = array("m.memberId AS memberId", "m.name AS name", "IF(m.color>{$this->eso->skin->numberOfColors},{$this->eso->skin->numberOfColors},m.color) AS color", "m.account AS account", "m.lastSeen AS lastSeen", "IF(" . (time() - $config["userOnlineExpire"]) . "<m.lastSeen,m.lastAction,'') AS lastAction", "m.avatarFormat AS avatarFormat",
		"(SELECT MIN(time) FROM {$config["tablePrefix"]}posts p WHERE p.memberId=m.memberId) AS firstPosted",
		"(SELECT COUNT(*) FROM {$config["tablePrefix"]}conversations c WHERE c.startMember=m.memberId) AS conversationsStarted",
		"(SELECT COUNT(DISTINCT conversationId) FROM {$config["tablePrefix"]}posts p WHERE p.memberId=m.memberId) AS conversationsParticipated",
		"(SELECT COUNT(*) FROM {$config["tablePrefix"]}posts p WHERE p.memberId=m.memberId) AS postCount"
	);
	$from = array("{$config["tablePrefix"]}members m");
	$memberId = (int)$memberId;
	$where = array("m.memberId=$memberId");

	// Put together the query components.
	$components = array("select" => $select, "from" => $from, "where" => $where);	
	
	$this->callHook("beforeGetMember", array(&$components));
	
	// Construct and run the query!
	$query = $this->eso->db->constructSelectQuery($components);
	$result = $this->eso->db->query($query);
	if (!$this->eso->db->numRows($result)) return false;

	// Get all the details from the query into an array.
	$member = $this->eso->db->fetchAssoc($result);
	
	$this->callHook("afterGetMember", array(&$member));
	
	return $member;
}

// Update the user's last action according to the profile they are currently viewing.
function updateLastAction($memberId)
{
	// Store action key with parameters: viewing_profile|{memberId}|{title}
	$memberId = (int)$memberId;
	$title = $this->eso->db->escape($this->title);
	$this->eso->updateLastAction("viewing_profile|$memberId|$title");
}

}

