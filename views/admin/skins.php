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
 * Skins view: displays a list of installed skins.
 */
if (!defined("IN_ESO")) exit;
?>

<?php // If there are installed skins to display...
if (count($this->skins)): ?>

<fieldset id='skins'>
<legend><?php echo $language["Installed skins"]; ?> (<span id='skinCount'><?php echo count($this->skins); ?></span>)</legend>

<script type='text/javascript'>
// <![CDATA[
// Message to confirm whether to delete a skin.
var skinPrompts = {};
function confirmDeleteSkin(id) {
	Dropdown.closeAll();
	Messages.showMessage("deleteSkin", "<?php echo $messages["deleteSkin"]["class"]; ?>", skinPrompts[id]);
	return false;
}

// Delete a skin, and take it off the list.
function deleteSkin(id) {
	Messages.hideMessage("deleteSkin");
	Ajax.request({
		"url": eso.baseURL + "ajax.php?controller=admin&section=skins",
		"post": "action=delete&id=" + encodeURIComponent(id),
		"success": function() {
			if (this.messages || !this.result) return;
			hide(getById("skin-" + id), {animation: "fade"});
			var count = getById("skinCount");
			count.innerHTML = parseInt(count.innerHTML) - 1;
		}
	});
	return false;
}
// ]]>
</script>

<ul class='list grid'>

<?php // Loop through each skin and output its preview/information.
foreach ($this->skins as $k => $skin):

	// Controls
	$version = htmlspecialchars(json_encode((string)$skin["version"]), ENT_QUOTES);
	$menu = array(100 => "<a href='#' onclick='return copyText($version)'>{$language["copy version"]}</a>");
	if ($skin["deletable"]) $menu[200] = "<a href='#' onclick='return confirmDeleteSkin(\"$k\")'>{$language["delete skin"]}</a>";
	if ($skin["selected"]) $controls = array("actions" => $menu);
	else $controls = array(100 => "<a href='" . makeLink("admin", "skins", $k, "?token={$_SESSION["token"]}") . "' class='enable'>{$language["enable"]}</a>", "more" => $menu);
?>
<li id='skin-<?php echo $k; ?>'<?php if ($skin["selected"]): ?> class='enabled'<?php endif; ?>>
<span class='preview'<?php if ($skin["livePreview"] and file_exists("skins/$k/styles.css")): ?> data-src='<?php echo makeLink("admin", "skins", "?preview=" . urlencode($k)); ?>' data-title='<?php echo sanitizeHTML($skin["name"]); ?>'<?php endif; ?>>
<?php if ($skin["preview"]): ?><img src='skins/<?php echo $k; ?>/<?php echo sanitizeHTML($skin["preview"]); ?>' alt='<?php echo sanitizeHTML($skin["name"]); ?>'/>
<?php else: ?><span><?php echo $language["No preview"]; ?></span>
<?php endif; ?>
</span>
<div class='hdr'>
<div class='controls'><?php echo $this->eso->htmlControls($controls, array("align" => "right")); ?></div>
<strong><?php echo sanitizeHTML($skin["name"]); ?></strong> <small><?php printf($language["version"], sanitizeHTML($skin["version"])); ?> <?php printf($language["author"], sanitizeHTML($skin["author"])); ?></small>
</div>
<?php if ($skin["deletable"]):
	$prompt = sprintf($messages["deleteSkin"]["message"], sanitizeHTML($skin["name"]), "<input type='button' value='{$language["Delete skin"]}' onclick='return deleteSkin(\"$k\")'/> <input type='button' value='{$language["Cancel"]}' onclick='Messages.hideMessage(\"deleteSkin\")'/>");
?><script type='text/javascript'>skinPrompts["<?php echo $k; ?>"] = "<?php echo escapeDoubleQuotes($prompt); ?>";</script>
<?php endif; ?>
</li>
<?php endforeach; ?>

</ul>
</fieldset>

<?php // Otherwise if there are no plugins installed, show a message.
else: ?>
<?php echo $this->eso->htmlMessage("noSkinsInstalled"); ?>
<?php endif; ?>

<?php // If it's okay to upload skin packages, add a new skin form.
if (!empty($config["uploadPackages"])): ?>
<fieldset id='addSkin'>
<legend><?php echo $language["Add a new skin"]; ?></legend>
<?php echo $this->eso->htmlMessage("downloadSkins", "https://geteso.org/skins"); ?>
<form action='<?php echo makeLink("admin", "skins"); ?>' method='post' enctype='multipart/form-data'>
<input type='hidden' name='token' value='<?php echo $_SESSION["token"]; ?>'/>
<ul class='form'>
<li><label><?php echo $language["Upload a skin"]; ?></label> <input name='installSkin' type='file' class='text' size='20'/></li>
<li><label></label> <?php echo $this->eso->skin->button(array("value" => $language["Add skin"])); ?></li>
</ul>
</form>
</fieldset>

<?php // Otherwise if uploading packages is disabled, show a message.
else: ?>
<fieldset id='addSkin'>
<legend><?php echo $language["Add a new skin"]; ?></legend>
<?php echo $this->eso->htmlMessage("noUploadingPackages"); ?>
</fieldset>
<?php endif; ?>

<script>
// Scale the skin preview iframe: adjust CSS transform value on resize
(function() {
	if (!window.ResizeObserver) return;
	var width = 650, height = 390; //px
	var fit = function(p) {
		var f = p.querySelector('iframe');
		if (f) f.style.transform = 'scale(' + (p.clientWidth / width) + ',' + (p.clientHeight / height) + ')';
	};
	var ro = new ResizeObserver(function(es) { es.forEach(function(e) { fit(e.target); }); });
	document.querySelectorAll('#skins .preview[data-src]').forEach(function(p) {
		var f = document.createElement('iframe');
		f.title = p.getAttribute('data-title');
		f.setAttribute('loading', 'lazy');
		f.setAttribute('scrolling', 'no');
		f.setAttribute('tabindex', '-1');
		f.setAttribute('aria-hidden', 'true');
		f.onload = function() { p.classList.add('loaded'); };
		f.src = p.getAttribute('data-src');
		p.appendChild(f);
		ro.observe(p);
		fit(p);
	});
})();
</script>
