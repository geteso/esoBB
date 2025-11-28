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
 * Emoticons plugin: converts emoticon text entities into graphic
 * emoticons.
 */
class Emoticons extends Plugin {

public ?string $id = "Emoticons";
public ?string $name = "Emoticons";
public ?string $version = "1.1";
public ?string $description = "Converts emoticon text entities into graphic emoticons";
public ?string $author = "the esoBB team";

public string $emoticonDir = "plugins/Emoticons/";
public array $emoticons = [];

function init()
{
    global $config, $language;

	parent::init();
	
	// Add the emoticon CSS style to the head.
	$this->eso->addToHead("<style type='text/css'>.emoticon {width:16px!important; height:16px; background:url({$this->emoticonDir}emoticons.svg); background-repeat:no-repeat} li .emoticon {margin-bottom:-4px}</style>");
	
	// Add a hook to convert emoticons to text in the feed.
	if ($this->eso->action == "feed")
		$this->eso->controller->addHook("formatPost", array($this, "revertEmoticons"));

	// Language definitions.
	$this->eso->addLanguage("Disable emoticons", "Disable image emoticons");

	// If we're on the settings view, add the emoticon setting!
	if ($this->eso->action == "settings") {
		$this->eso->controller->addHook("init", array($this, "addEmoticonSettings"));
	}
	
	$this->eso->addHook("init", array($this, "addEmoticonFormatter"));
}

// Add the emoticon formatter that will parse and unparse emoticons.
// Only add the formatter if the current user hasn't opted out of graphical emoticons.
function addEmoticonFormatter()
{
	if (empty($this->eso->user["emoticons"])) $this->eso->formatter->addFormatter("emoticons", "Formatter_Emoticons");
}

function revertEmoticons($controller, &$post)
{
	// Only revert emoticons if the formatter mode exists (user has emoticons enabled)
	if (isset($this->eso->formatter->modes["emoticons"])) {
		$post = $this->eso->formatter->modes["emoticons"]->revert($post);
	}
}

// This is the part where we add the setting to the settings screen. Fun!
function addEmoticonSettings(&$settings)
{
	global $language;

	$settings->addToForm("settingsOther", array(
		"id" => "emoticons",
		"html" => "<label for='emoticons' class='checkbox'>{$language["Disable emoticons"]} " . (empty($this->eso->user["emoticons"]) ? "<img src='js/x.gif' style='background-position:0 0' alt=':)' class='emoticon'/>" : ":)") . "</label> <input id='emoticons' type='checkbox' class='checkbox' name='emoticons' value='1' " .  (!empty($this->eso->user["emoticons"]) ? "checked='checked' " : "") . "/>",
		"databaseField" => "emoticons",
		"checkbox" => true
	), 600);
}

// Add the table to the database.
function upgrade($oldVersion)
{
	global $config;
	
	if (!$this->eso->db->numRows("SHOW COLUMNS FROM {$config["tablePrefix"]}members LIKE 'emoticons'")) {
		$this->eso->db->query("ALTER TABLE {$config["tablePrefix"]}members ADD COLUMN emoticons tinyint(1) NOT NULL default '0'");
	}
}

}

class Formatter_Emoticons {
	
var $formatter;
var $emoticons = [];
	
function __construct(&$formatter)
{
	$this->formatter = &$formatter;
	
	// Define the emoticons.
	$this->emoticons[":)"] = "<img src='js/x.gif' style='background-position:0 0' alt=':)' class='emoticon'/>";
	$this->emoticons[":]"] = "<img src='js/x.gif' style='background-position:0 0' alt=':]' class='emoticon'/>";
	$this->emoticons["=)"] = "<img src='js/x.gif' style='background-position:0 0' alt='=)' class='emoticon'/>";
	$this->emoticons["=]"] = "<img src='js/x.gif' style='background-position:0 0' alt='=]' class='emoticon'/>";
	$this->emoticons[":D"] = "<img src='js/x.gif' style='background-position:0 -20px' alt=':D' class='emoticon'/>";
	$this->emoticons["=D"] = "<img src='js/x.gif' style='background-position:0 -20px' alt='=D' class='emoticon'/>";
	$this->emoticons["^_^"] = "<img src='js/x.gif' style='background-position:0 -40px' alt='^_^' class='emoticon'/>";
	$this->emoticons["^^"] = "<img src='js/x.gif' style='background-position:0 -40px' alt='^^' class='emoticon'/>";
	$this->emoticons[":("] = "<img src='js/x.gif' style='background-position:0 -60px' alt=':(' class='emoticon'/>";
	$this->emoticons["=("] = "<img src='js/x.gif' style='background-position:0 -60px' alt='=(' class='emoticon'/>";
	$this->emoticons["-_-"] = "<img src='js/x.gif' style='background-position:0 -80px' alt='-_-' class='emoticon'/>";
	$this->emoticons[";)"] = "<img src='js/x.gif' style='background-position:0 -100px' alt=';)' class='emoticon'/>";
	$this->emoticons[";]"] = "<img src='js/x.gif' style='background-position:0 -100px' alt=';]' class='emoticon'/>";
	$this->emoticons["^_-"] = "<img src='js/x.gif' style='background-position:0 -100px' alt='^_-' class='emoticon'/>";
	$this->emoticons["~_-"] = "<img src='js/x.gif' style='background-position:0 -100px' alt='~_-' class='emoticon'/>";
	$this->emoticons["-_^"] = "<img src='js/x.gif' style='background-position:0 -100px' alt='-_^' class='emoticon'/>";
	$this->emoticons["-_~"] = "<img src='js/x.gif' style='background-position:0 -100px' alt='-_~' class='emoticon'/>";
	$this->emoticons["^_^;"] = "<img src='js/x.gif' style='background-position:0 -120px; width:18px' alt='^_^;' class='emoticon'/>";
	$this->emoticons["^^;"] = "<img src='js/x.gif' style='background-position:0 -120px; width:18px' alt='^^;' class='emoticon'/>";
	$this->emoticons[">_<"] = "<img src='js/x.gif' style='background-position:0 -140px' alt='&gt;_&lt;' class='emoticon'/>";
	$this->emoticons[":/"] = "<img src='js/x.gif' style='background-position:0 -160px' alt=':/' class='emoticon'/>";
	$this->emoticons["=/"] = "<img src='js/x.gif' style='background-position:0 -160px' alt='=/' class='emoticon'/>";
	$this->emoticons[":\\"] = "<img src='js/x.gif' style='background-position:0 -160px' alt=':&#92;' class='emoticon'/>";
	$this->emoticons["=\\"] = "<img src='js/x.gif' style='background-position:0 -160px' alt='=&#92;' class='emoticon'/>";
	$this->emoticons[":x"] = "<img src='js/x.gif' style='background-position:0 -180px' alt=':x' class='emoticon'/>";
	$this->emoticons["=x"] = "<img src='js/x.gif' style='background-position:0 -180px' alt='=x' class='emoticon'/>";
	$this->emoticons[":|"] = "<img src='js/x.gif' style='background-position:0 -180px' alt=':|' class='emoticon'/>";
	$this->emoticons[":L"] = "<img src='js/x.gif' style='background-position:0 -180px' alt=':L' class='emoticon'/>";
	$this->emoticons[":l"] = "<img src='js/x.gif' style='background-position:0 -180px' alt=':l' class='emoticon'/>";
	$this->emoticons["=|"] = "<img src='js/x.gif' style='background-position:0 -180px' alt='=|' class='emoticon'/>";
	$this->emoticons["=L"] = "<img src='js/x.gif' style='background-position:0 -180px' alt='=L' class='emoticon'/>";
	$this->emoticons["=l"] = "<img src='js/x.gif' style='background-position:0 -180px' alt='=l' class='emoticon'/>";
	$this->emoticons["'_'"] = "<img src='js/x.gif' style='background-position:0 -180px' alt='&#39;_&#39;' class='emoticon'/>";
	$this->emoticons["<_<"] = "<img src='js/x.gif' style='background-position:0 -200px' alt='&lt;_&lt;' class='emoticon'/>";
	$this->emoticons[">_>"] = "<img src='js/x.gif' style='background-position:0 -220px' alt='&gt;_&gt;' class='emoticon'/>";
	$this->emoticons["x_x"] = "<img src='js/x.gif' style='background-position:0 -240px' alt='x_x' class='emoticon'/>";
	$this->emoticons["o_O"] = "<img src='js/x.gif' style='background-position:0 -260px' alt='o_O' class='emoticon'/>";
	$this->emoticons["O_o"] = "<img src='js/x.gif' style='background-position:0 -260px' alt='O_o' class='emoticon'/>";
	$this->emoticons["o_0"] = "<img src='js/x.gif' style='background-position:0 -260px' alt='o_0' class='emoticon'/>";
	$this->emoticons["0_o"] = "<img src='js/x.gif' style='background-position:0 -260px' alt='0_o' class='emoticon'/>";
	$this->emoticons[";_;"] = "<img src='js/x.gif' style='background-position:0 -280px' alt=';_;' class='emoticon'/>";
	$this->emoticons[":'("] = "<img src='js/x.gif' style='background-position:0 -280px' alt=':&#39;(' class='emoticon'/>";
	$this->emoticons[":O"] = "<img src='js/x.gif' style='background-position:0 -300px' alt=':O' class='emoticon'/>";
	$this->emoticons["=O"] = "<img src='js/x.gif' style='background-position:0 -300px' alt='=O' class='emoticon'/>";
	$this->emoticons[":o"] = "<img src='js/x.gif' style='background-position:0 -300px' alt=':o' class='emoticon'/>";
	$this->emoticons["=o"] = "<img src='js/x.gif' style='background-position:0 -300px' alt='=o' class='emoticon'/>";
	$this->emoticons[":P"] = "<img src='js/x.gif' style='background-position:0 -320px' alt=':P' class='emoticon'/>";
	$this->emoticons["=P"] = "<img src='js/x.gif' style='background-position:0 -320px' alt='=P' class='emoticon'/>";
	$this->emoticons[";P"] = "<img src='js/x.gif' style='background-position:0 -320px' alt=';P' class='emoticon'/>";
	$this->emoticons[":["] = "<img src='js/x.gif' style='background-position:0 -340px' alt=':[' class='emoticon'/>";
	$this->emoticons["=["] = "<img src='js/x.gif' style='background-position:0 -340px' alt='=[' class='emoticon'/>";
	$this->emoticons[":3"] = "<img src='js/x.gif' style='background-position:0 -360px' alt=':3' class='emoticon'/>";
	$this->emoticons["=3"] = "<img src='js/x.gif' style='background-position:0 -360px' alt='=3' class='emoticon'/>";
	$this->emoticons["._.;"] = "<img src='js/x.gif' style='background-position:0 -380px; width:18px' alt='._.;' class='emoticon'/>";
	$this->emoticons["<(^.^)>"] = "<img src='js/x.gif' style='background-position:0 -400px; width:19px' alt='&lt;(^.^)&gt;' class='emoticon'/>";
	$this->emoticons["(>'.')>"] = "<img src='js/x.gif' style='background-position:0 -400px; width:19px' alt='(&gt;&#39;.&#39;)&gt;' class='emoticon'/>";
	$this->emoticons["(>^.^)>"] = "<img src='js/x.gif' style='background-position:0 -400px; width:19px' alt='(&gt;^.^)&gt;' class='emoticon'/>";
	$this->emoticons["-_-;"] = "<img src='js/x.gif' style='background-position:0 -420px; width:18px' alt='-_-;' class='emoticon'/>";
	$this->emoticons["(o^_^o)"] = "<img src='js/x.gif' style='background-position:0 -440px' alt='(o^_^o)' class='emoticon'/>";
	$this->emoticons["(^_^)/"] = "<img src='js/x.gif' style='background-position:0 -460px; width:19px' alt='(^_^)/' class='emoticon'/>";
	$this->emoticons[">:("] = "<img src='js/x.gif' style='background-position:0 -480px' alt='&gt;:(' class='emoticon'/>";
	$this->emoticons[">:["] = "<img src='js/x.gif' style='background-position:0 -480px' alt='&gt;:[' class='emoticon'/>";
	$this->emoticons["._."] = "<img src='js/x.gif' style='background-position:0 -500px' alt='._.' class='emoticon'/>";
	$this->emoticons["T_T"] = "<img src='js/x.gif' style='background-position:0 -520px' alt='T_T' class='emoticon'/>";
	$this->emoticons["XD"] = "<img src='js/x.gif' style='background-position:0 -540px' alt='XD' class='emoticon'/>";
	$this->emoticons["('<"] = "<img src='js/x.gif' style='background-position:0 -560px' alt='(&#39;&lt;' class='emoticon'/>";
	$this->emoticons["B)"] = "<img src='js/x.gif' style='background-position:0 -580px' alt='B)' class='emoticon'/>";
	$this->emoticons["XP"] = "<img src='js/x.gif' style='background-position:0 -600px' alt='XP' class='emoticon'/>";
	$this->emoticons[":S"] = "<img src='js/x.gif' style='background-position:0 -620px' alt=':S' class='emoticon'/>";
	$this->emoticons["=S"] = "<img src='js/x.gif' style='background-position:0 -620px' alt='=S' class='emoticon'/>";
	$this->emoticons[">:)"] = "<img src='js/x.gif' style='background-position:0 -640px' alt='&gt;:)' class='emoticon'/>";
	$this->emoticons[">:D"] = "<img src='js/x.gif' style='background-position:0 -640px' alt='&gt;:D' class='emoticon'/>";
}

// Add an emoticon to the output.
function emoticon($match, $state)
{
	$this->formatter->output .= $this->emoticons[desanitize($match)];
	return true;
}

// Set up the lexer to parse emoticons.
function format()
{
	// Make an array of regular-expression-safe emoticon patterns.
	$patterns = [];
	foreach ($this->emoticons as $k => $v) $patterns[] = preg_quote(sanitizeHTML($k), "/");
	
	// Map a function to handle emoticons.
	$this->formatter->lexer->mapFunction("emoticon", array($this, "emoticon"));
	
	// Add the emoticon mode to the lexer - emoticons are allowed in all modes.
	$allowedModes = $this->formatter->getModes($this->formatter->allowedModes["inline"]);
	foreach ($allowedModes as $mode) {
		$this->formatter->lexer->addSpecialPattern('(?<=^|[\s.,!<>])(?:' . implode("|", $patterns) . ')(?=[\s.,!<>)]|$)', $mode, "emoticon");
	}
}

// Convert emoticons back into their corresponding text entity.
function revert($string)
{
	return strtr($string, array_flip($this->emoticons));
}

}

