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
 * Online controller: fetches a list of members currently online, ready
 * to be displayed in the view.
 */
class online extends Controller {
	
var $view = "online.view.php";

function init()
{
	global $language, $config;

	if (!$config["onlineMembers"]) redirect("");

	// Set the title and make sure this page isn't indexed.
	$this->title = $language["Online members"];
	$this->eso->addToHead("<meta name='robots' content='noindex, noarchive'/>");
	
	// Fetch online members.
	$this->online = $this->getOnlineMembers();
	$this->numberOnline = $this->eso->db->numRows($this->online);
	
	// Add JavaScript variable for auto-refresh interval (if enabled).
	if (!empty($config["onlineRefreshInterval"])) {
		$this->eso->addVarToJS("onlineRefreshInterval", (int)$config["onlineRefreshInterval"]);
	}
}

function ajax()
{
	global $config;
	
	switch (@$_POST["action"]) {
		case "getOnlineMembers":
			$result = $this->getOnlineMembers();
			$memberIds = [];
			$members = [];
			
			// Collect member data for JSON response.
			while ($row = $this->eso->db->fetchAssoc($result)) {
				$memberId = (int)$row["memberId"];
				$memberIds[] = $memberId;
				$members[$memberId] = [
					"id" => $memberId,
					"name" => $row["name"],
					"avatarFormat" => $row["avatarFormat"],
					"color" => min((int)$row["color"], $this->eso->skin->numberOfColors),
					"account" => $row["account"],
					"lastSeen" => (int)$row["lastSeen"],
					"lastAction" => $row["lastAction"],
					"avatar" => $this->eso->getAvatar($memberId, $row["avatarFormat"], "thumb"),
					"profileLink" => makeLink("profile", $memberId),
					"lastActionText" => translateLastAction($row["lastAction"]),
					"lastSeenText" => relativeTime($row["lastSeen"])
				];
			}
			
			return [
				"memberIds" => $memberIds,
				"members" => $members,
				"count" => count($memberIds)
			];
	}
}

// Fetch a list of members who have been online in the last $config["userOnlineExpire"] seconds.
function getOnlineMembers()
{
	global $config;
	return $this->eso->db->query("SELECT memberId, name, avatarFormat, IF(color>{$this->eso->skin->numberOfColors},{$this->eso->skin->numberOfColors},color) AS color, account, lastSeen, lastAction FROM {$config["tablePrefix"]}members WHERE UNIX_TIMESTAMP()-{$config["userOnlineExpire"]}<lastSeen ORDER BY lastSeen DESC");
}

}
