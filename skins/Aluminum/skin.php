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
 * Aluminum skin: alternate skin inspired by Mac OS X design elements
 * with dark gradients and push buttons.
 */
class Aluminum extends Skin {

public ?string $name = "Aluminum";
public ?string $version = "2.0";
public ?string $author = "the esoBB team";
public int $numberOfColors = 27;

// Define name and file type of resources.
public string $logo = "logo.svg";
public string $icon = "icon.png";
public string $favicon = "favicon.ico";
public string $avatarLeft = "avatarLeft.svg";
public string $avatarRight = "avatarRight.svg";
public string $avatarThumb = "avatarThumb.svg";

// Add stylesheets and a favicon to the page header.
function init()
{
	global $config;
	$this->eso->addCSS("skins/base.css");
	$this->eso->addCSS("skins/{$config["skin"]}/styles.css");
	$this->eso->addCSS("skins/ie6.css", "ie6");
	$this->eso->addCSS("skins/ie7.css", "ie7");
}

// Generate button HTML.
function button($attributes)
{
	$class = $id = $style = ""; $attr = " type='submit'";
	foreach ($attributes as $k => $v) {
		if ($k == "class") $class = " $v";
		elseif ($k == "id") $id = " id='$v'";
		elseif ($k == "style") $style = " style='$v'";
		else $attr .= " $k='$v'";
	}
	return "<span class='button$class'$id$style><input$attr/></span>";
}

}

?>
