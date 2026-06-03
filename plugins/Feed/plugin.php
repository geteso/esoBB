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
 * Feed plugin: serves a feed in the Atom format containing recent posts.
 */
class Feed extends Plugin {

var $id = "Feed";
var $name = "Feed";
var $version = "1.0";
var $description = "Provides an Atom/RSS feed containing recent posts";
var $author = "the esoBB team";

function init()
{
	parent::init();

	$this->eso->skin->registerView("feed.view.php", PATH_PLUGINS."/Feed/feed.view.php");

	// Add a language string and stylesheet.
	$this->eso->addLanguage("RSS", "RSS");
	$this->eso->addCSS(PATH_PLUGINS."/Debug/debug.css");

	// Add a hook to include autodiscovery <link> tags at render time via the head.
	$this->eso->addHook("head", array($this, "addFeedTags"));

	if (@$_GET["q1"] == "feed") {
		$this->eso->registerController("feed", PATH_PLUGINS."/Feed/feed.controller.php");
		return;
	}

	// Add the RSS button to the search and conversation pages.
	if ($this->eso->action == "conversation" or $this->eso->action == "search")
		$this->eso->controller->addHook("init", array($this, "addFeedButton"));
}

function addFeedTags($eso, &$head)
{
	global $config, $language;
	$head .= "\n<link href='{$config["baseURL"]}" . makeLink("feed") . "' rel='alternate' type='application/atom+xml' title='{$language["Recent posts"]}'/>";
	if ($eso->action == "conversation" and !empty($eso->controller->conversation["id"]))
		$head .= "\n<link href='{$config["baseURL"]}" . makeLink("feed", "conversation", $eso->controller->conversation["id"]) . "' rel='alternate' type='application/atom+xml' title='\"{$eso->controller->conversation["title"]}\"'/>";
}

function addFeedButton($controller)
{
	global $language;
	if ($this->eso->action == "conversation") {
		if (empty($controller->conversation["id"])) return;
		$link = makeLink("feed", "conversation", $controller->conversation["id"]);
	} else {
		$link = makeLink("feed");
	}
	$this->eso->addToBar("right", "<a href='$link' id='rss' class='vl'><span class='button buttonSmall'><input type='submit' value='{$language["RSS"]}'></span></a>", 500);
}

}

?>
