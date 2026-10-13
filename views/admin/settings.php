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
 * Forum settings view: displays forum settings and an interface where an
 * admin can change them, along with the forum logo and icon.
 */
if (!defined("IN_ESO")) exit;
?>

<fieldset id='adminbasic'>
<legend><?php echo $language["Basic settings"];?></legend>

<form action='<?php echo makeLink("admin", "settings"); ?>' id='basicSettings' method='post'>
<input type='hidden' name='token' value='<?php echo $_SESSION["token"]; ?>'/>

<ul class='form settingsForm'>

<li><label><?php echo $language["Forum title"]; ?></label>
<input type='text' class='text' name='forumTitle' value='<?php echo $config["forumTitle"]; ?>'/></li>

<li><label><?php echo $language["Forum description"]; ?></label>
<input type='text' class='text' name='forumDescription' value='<?php echo $config["forumDescription"]; ?>'/></li>

<li><label class='checkbox'><?php echo $language["Use friendly URLs"]; ?></label>
<input type='checkbox' class='checkbox' name='useFriendlyURLs' value='1'<?php echo !empty($config["useFriendlyURLs"]) ? " checked='checked'" : ""; ?>/></li>

<li><label class='checkbox'><?php echo $language["Show forum description"]; ?></label>
<input type='checkbox' class='checkbox' name='showDescription' value='1'<?php echo !empty($config["showDescription"]) ? " checked='checked'" : ""; ?>/></li>

<li><label class='checkbox'><?php echo $language["Use forum description"]; ?></label>
<input type='checkbox' class='checkbox' name='metaDescription' value='1'<?php echo !empty($config["metaDescription"]) ? " checked='checked'" : ""; ?>/></li>

<li><label></label> <span class='button'><input type='submit' name='saveSettings' value='<?php echo $language["Save changes"]; ?>'/></span></li>

</ul>

</form>
</fieldset>

<?php
if ($this->eso->user["memberId"] == $config["rootAdmin"]): ?>
<fieldset>
<legend><?php echo $language["Advanced settings"];?></legend>

<form action='<?php echo makeLink("admin", "settings"); ?>' id='advancedSettings' method='post'>
<input type='hidden' name='token' value='<?php echo $_SESSION["token"]; ?>'/>

<ul class='form settingsForm advanced'>

<li><label class='checkbox'><?php echo $language["gzipOutput"]; ?><!-- <br/><small><?php echo $language["gzipOutputInfo"]; ?> --></small></label>
<input type='checkbox' class='checkbox' name='gzipOutput' value='1'<?php echo !empty($config["gzipOutput"]) ? " checked='checked'" : ""; ?>/></li>

<li><label class='checkbox'><?php echo $language["httpsSetting"]; ?><br/><small><?php echo $language["httpsInfo"]; ?></small></label>
<input type='checkbox' class='checkbox' name='https' value='1'<?php echo !empty($config["https"]) ? " checked='checked'" : ""; ?>/></li>

<li><label class='checkbox'><?php echo $language["uploadPackages"]; ?><br/><small><?php echo $language["uploadPackagesInfo"]; ?></small></label>
<input type='checkbox' class='checkbox' name='uploadPackages' value='1'<?php echo !empty($config["uploadPackages"]) ? " checked='checked'" : ""; ?>/></li>

<hr/>

<li><label class='checkbox'><?php echo $language["nonAsciiCharacters"]; ?><br/><small><?php echo $language["nonAsciiInfo"]; ?></small></label>
<input type='checkbox' class='checkbox' name='nonAsciiCharacters' value='1'<?php echo !empty($config["nonAsciiCharacters"]) ? " checked='checked'" : ""; ?>/></li>

<li><label><?php echo $language["loginsPerMinute"]; ?></label>
<div><input type='text' class='text' name='loginsPerMinute' value='<?php echo $config["loginsPerMinute"]; ?>'/>
<br/><small><?php echo $language["loginsMinuteInfo"]; ?></small></div></li>

<li><label><?php echo $language["minPasswordLength"]; ?></label>
<input type='text' class='text' name='minPasswordLength' value='<?php echo $config["minPasswordLength"]; ?>'/></li>

<hr/>

<li><label><?php echo $language["userOnlineExpire"]; ?></label>
<div><input type='text' class='text' name='userOnlineExpire' value='<?php echo $config["userOnlineExpire"]; ?>'/>
<br/><small><?php echo $language["userOnlineExpireInfo"]; ?></small></div></li>

<li><label><?php echo $language["messageDisplayTime"]; ?></label>
<div><input type='text' class='text' name='messageDisplayTime' value='<?php echo $config["messageDisplayTime"]; ?>'/>
<br/><small><?php echo $language["messageDisplayTimeInfo"]; ?></small></div></li>

<li><label><?php echo $language["numberResults"]; ?></label>
<div><input type='text' class='text' name='results' value='<?php echo $config["results"]; ?>'/>
<br/><small><?php echo $language["numberResultsInfo"]; ?></small></div></li>

<li><label><?php echo $language["numberMoreResults"]; ?></label>
<div><input type='text' class='text' name='moreResults' value='<?php echo $config["moreResults"]; ?>'/>
<br/><small><?php echo $language["numberMoreResultsInfo"]; ?></small></div></li>

<li><label><?php echo $language["numberTagsInTagCloud"]; ?></label>
<input type='text' class='text' name='numberOfTagsInTagCloud' value='<?php echo $config["numberOfTagsInTagCloud"]; ?>'/></li>

<li><label class='checkbox'><?php echo $language["showAvatarThumbnails"]; ?></label>
<input type='checkbox' class='checkbox' name='showAvatarThumbnails' value='1'<?php echo !empty($config["showAvatarThumbnails"]) ? " checked='checked'" : ""; ?>/></li>

<li><label><?php echo $language["updateCurrentResultsInterval"]; ?></label>
<div><input type='text' class='text' name='updateCurrentResultsInterval' value='<?php echo $config["updateCurrentResultsInterval"]; ?>'/>
<br/><small><?php echo $language["updateCurrentResultsInfo"]; ?></small></div></li>

<li><label><?php echo $language["checkNewResultsInterval"]; ?></label>
<div><input type='text' class='text' name='checkForNewResultsInterval' value='<?php echo $config["checkForNewResultsInterval"]; ?>'/>
<br/><small><?php echo $language["checkNewResultsInfo"]; ?></small></div></li>

<li><label><?php echo $language["searchesPerMinute"]; ?></label>
<div><input type='text' class='text' name='searchesPerMinute' value='<?php echo $config["searchesPerMinute"]; ?>'/>
<br/><small><?php echo $language["searchesMinuteInfo"]; ?></small></div></li>

<li><label></label> <span class='button'><input type='submit' name='saveAdvancedSettings' value='<?php echo $language["Save changes"]; ?>'/></span></li>

</ul>

</form>
</fieldset>
<?php endif; ?>

<fieldset>
<legend><?php echo $language["Forum logo"]; ?></legend>

<div class='msg info'><?php echo $language["logoInfo"]; ?></div>

<form action='<?php echo makeLink("admin", "settings"); ?>' id='settingsLogo' method='post' enctype='multipart/form-data'>
<input type='hidden' name='token' value='<?php echo $_SESSION["token"]; ?>'/>

<ul class='form logoForm'>

<li><label><?php echo $language["Current logo"]; ?></label>
<div><img src='<?php echo $this->eso->skin->getForumLogo();?>'/></div></li>

<li><label for='logoUpload' class='radio'>
<input type='radio' class='radio' value='upload' name='logo[type]' id='logoUpload'/>
<?php echo $language["Upload a logo from your computer"]; ?></label>
<input name='logoUpload' type='file' class='text' size='20' onchange='getById("upload").checked="true"'/></li>

<li><label for='logoUrl' class='radio'>
<input type='radio' class='radio' value='url' name='logo[type]' id='logoUrl'/>
<?php echo $language["Enter the web address of a logo"]; ?></label>
<input name='logo[url]' type='text' class='text' onkeypress='getById("url").checked="true"' value=''/></li>

<li><label for='logoNone' class='radio'>
<input type='radio' class='radio' value='none' name='logo[type]' id='logoNone'/>
<?php echo $language["Use default logo"]; ?></label></li>

<li><label></label><span class='button'>
<input type='submit' name='changeLogo' value='<?php echo $language["Change logo"]; ?>'/></span></li>

</ul>

</form>
</fieldset>

<fieldset>
<legend><?php echo $language["Forum icon"]; ?></legend>

<div class='msg info'><?php echo $language["iconInfo"]; ?></div>

<form action='<?php echo makeLink("admin", "settings"); ?>' id='settingsIcon' method='post' enctype='multipart/form-data'>
<input type='hidden' name='token' value='<?php echo $_SESSION["token"]; ?>'/>

<ul class='form iconForm'>

<li><label><?php echo $language["Current icon"]; ?></label>
<div><img src='<?php echo $this->eso->skin->getForumIcon(); ?>'/></div></li>

<li><label for='iconUpload' class='radio'>
<input type='radio' class='radio' value='upload' name='icon[type]' id='iconUpload'/>
<?php echo $language["Upload an icon from your computer"]; ?></label>
<input name='iconUpload' type='file' class='text' size='20' onchange='getById("upload").checked="true"'/></li>

<li><label for='iconUrl' class='radio'>
<input type='radio' class='radio' value='url' name='icon[type]' id='iconUrl'/>
<?php echo $language["Enter the web address of an icon"]; ?></label>
<input name='icon[url]' type='text' class='text' onkeypress='getById("url").checked="true"' value=''/></li>

<li><label for='iconNone' class='radio'>
<input type='radio' class='radio' value='none' name='icon[type]' id='iconNone'/>
<?php echo $language["Use default icon"]; ?></label></li>

<li><label></label><span class='button'>
<input type='submit' name='changeIcon' value='<?php echo $language["Change icon"]; ?>'/></span></li>

</ul>

</form>
</fieldset>