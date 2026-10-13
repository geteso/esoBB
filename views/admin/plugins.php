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
 * Plugins view: displays a list of plugins and their settings.
 */
if (!defined("IN_ESO")) exit;
?>

<?php // If it's okay to upload plugin packages, add a new plugin form.
if (!empty($config["uploadPackages"])): ?>
<fieldset id='addPlugin'>
<legend><?php echo $language["Add a new plugin"]; ?></legend>
<?php echo $this->eso->htmlMessage("downloadPlugins", "https://geteso.org/plugins"); ?>
<form action='<?php echo makeLink("admin", "plugins"); ?>' method='post' enctype='multipart/form-data'>
<input type='hidden' name='token' value='<?php echo $_SESSION["token"]; ?>'/>
<ul class='form'>
<li><label><?php echo $language["Upload a plugin"]; ?></label> <input name='installPlugin' type='file' class='text' size='20'/></li>
<li><label></label> <?php echo $this->eso->skin->button(array("value" => $language["Add plugin"])); ?></li>
</ul>
</form>
</fieldset>

<?php // Otherwise if uploading packages is disabled, show a message.
else: ?>
<fieldset id='addPlugin'>
<legend><?php echo $language["Add a new plugin"]; ?></legend>
<?php echo $this->eso->htmlMessage("noUploadingPackages"); ?>
</fieldset>
<?php endif; ?>

<?php // If there are installed plugins to display.
if (count($this->plugins)): ?>

<fieldset id='plugins'>
<legend><?php echo $language["Installed plugins"]; ?></legend>

<script type='text/javascript'>
// <![CDATA[
function toggleSettings(id) {
	for (var i in plugins) {
		var other = getById("plugin-" + plugins[i] + "-settings");
		if (plugins[i] != id && other && other.showing) {
			hide(other, {animation: "verticalSlide"});
			getById("plugin-" + plugins[i] + "-toggle").innerHTML = eso.language["show"];
		}
	}
	var settings = getById("plugin-" + id + "-settings");
	toggle(settings, {animation: "verticalSlide"});
	getById("plugin-" + id + "-toggle").innerHTML = eso.language[settings.showing ? "hide" : "show"];
	return false;
}
var plugins = [];
// ]]>
</script>

<ul class='list'>

<?php // Loop through each plugin and output its information.
foreach ($this->plugins as $k => $plugin):

	$version = htmlspecialchars(json_encode((string)$plugin["version"]), ENT_QUOTES);
	$controls = array(
		200 => "<a href='" . makeLink("admin", "plugins", "?toggle=$k", "&token={$_SESSION["token"]}") . "' class='" . ($plugin["loaded"] ? "disable" : "enable") . "'>" . ($plugin["loaded"] ? $language["disable"] : $language["enable"]) . "</a>",
		"more" => array(100 => "<a href='#' onclick='return copyText($version)'>{$language["copy version"]}</a>")
	);
	if (!empty($plugin["settings"])) $controls[100] = "<a href='#' id='plugin-$k-toggle' onclick='return toggleSettings(\"$k\")'>" . (@$_POST["plugin"] == $k ? $language["hide"] : $language["show"]) . "</a>";
?>
<li id='plugin-<?php echo $k; ?>' class='plugin<?php if ($plugin["loaded"]): ?> enabled<?php endif; ?>'>
<div class='hdr'>
<div class='controls'><?php echo $this->eso->htmlControls($controls, array("align" => "right")); ?></div>
<strong><?php echo $plugin["name"]; ?></strong>
<small><?php printf($language["version"], $plugin["version"]); ?> <?php printf($language["author"], $plugin["author"]); ?></small> <small><?php echo $plugin["description"]; ?></small>
</div>

<?php // Output plugin settings.
if (!empty($plugin["settings"])): ?>
<div id='plugin-<?php echo $k; ?>-settings' class='details'>
<form action='<?php echo makeLink("admin", "plugins"); ?>' method='post'>
<input type='hidden' name='plugin' value='<?php echo $k; ?>'/>
<input type='hidden' name='token' value='<?php echo $_SESSION["token"]; ?>'/>
<?php echo $plugin["settings"]; ?>
</form>
</div>
<?php endif; ?>
<script type='text/javascript'>// <![CDATA[
plugins.push("<?php echo $k; ?>");
<?php if (!empty($plugin["settings"])):
	if (@$_POST["plugin"] != $k): ?>hide(getById("plugin-<?php echo $k; ?>-settings"));<?php
	else: ?>getById("plugin-<?php echo $k; ?>-settings").showing = true;<?php endif;
endif; ?> 
// ]]></script>
</li>
<?php endforeach; ?>

</ul>
</fieldset>

<?php // Otherwise if there are no plugins installed, show a message.
else: ?>
<fieldset id='addPlugin'>
<legend><?php echo $language["Add a new plugin"]; ?></legend>
<?php echo $this->eso->htmlMessage("noPluginsInstalled"); ?>
</fieldset>
<?php endif; ?>
