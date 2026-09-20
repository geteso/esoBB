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
<legend><?php echo $language["Installed skins"]; ?></legend>
<ul>

<?php // Loop through each skin and output its preview/information.
foreach ($this->skins as $k => $skin): ?>
<li<?php if ($skin["selected"]): ?> class='enabled'<?php endif; ?>>
<a href='<?php echo makeLink("admin", "skins", $k, "?token={$_SESSION["token"]}"); ?>'>
<span class='preview'>
<?php if (file_exists("skins/$k/styles.css")): ?><iframe src='<?php echo makeLink("admin", "skins", "?preview=" . urlencode($k)); ?>' title='<?php echo $skin["name"]; ?>' loading='lazy' scrolling='no' tabindex='-1' aria-hidden='true'></iframe>
<?php elseif ($skin["preview"]): ?><img src='skins/<?php echo $k; ?>/<?php echo $skin["preview"]; ?>' alt='<?php echo $skin["name"]; ?>'/>
<?php else: ?><span><?php echo $language["No preview"]; ?></span>
<?php endif; ?>
</span>
<strong><?php echo $skin["name"]; ?></strong> <small><?php printf($language["version"], $skin["version"]); ?> <?php printf($language["author"], $skin["author"]); ?></small>
</a>
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
	var width = 650; //px
	var fit = function(p) {
		var f = p.querySelector('iframe');
		if (f) f.style.transform = 'scale(' + (p.clientWidth / width) + ')';
	};
	var element = document.querySelectorAll('#skins .preview');
	if (window.ResizeObserver) {
		var ro = new ResizeObserver(function(es) { es.forEach(function(e) { fit(e.target); }); });
		element.forEach(function(p) { ro.observe(p); fit(p); });
	} else {
		var all = function() { element.forEach(fit); };
		window.addEventListener('resize', all);
		all();
	}
})();
</script>
