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
 * Profile view: displays a member's profile.
 */
if(!defined("IN_ESO"))exit;
?>
<fieldset>
<legend><?php printf($language["profile"],$this->member["name"]);?></legend>

<div class='p l c<?php echo $this->member["color"];?> profile'>
<div class='parts'>

<div>
<div class='hdr'>
<div class='thumb'><a href='<?php echo makeLink("profile",$this->member["memberId"]);?>'><img src='<?php echo $this->eso->getAvatar($this->member["memberId"],$this->member["avatarFormat"],"thumb");?>' alt=''/></a></div>
<div class='pInfo'>
<h3><?php echo $this->member["name"];?></h3>
<?php if(!empty($this->eso->canChangeGroup($this->member["memberId"], $this->member["account"]))):?><form action='<?php echo curLink();?>' method='post'><div style='display:inline'><select onchange='Conversation.changeMemberGroup(<?php echo $this->member["memberId"];?>,this.value)' name='group'>
	<?php foreach($this->eso->canChangeGroup($this->member["memberId"], $this->member["account"]) as $group):?><option value='<?php echo $group;?>'<?php if($group==$this->member["account"])echo " selected='selected'";?>><?php echo $language[$group];?></option><?php endforeach;?></select></div> <noscript><div style='display:inline'><input name='saveGroup' type='submit' value='Save' class='save'/><input type='hidden' name='member' value='<?php echo $this->member["memberId"];?>'/></div></noscript></form>
<?php else:?><span><?php echo $language[$this->member["account"]];?></span>
<?php endif;?>
</div>
</div>
<div class='body'>

<?php // Output member statistics. ?>
<ul class='form stats'>

<li><label><?php echo $language["Last active"];?></label>
<div><abbr title='<?php echo date($language["dateFormat"],$this->member["lastSeen"]);?>'><?php echo relativeTime($this->member["lastSeen"]);?></abbr> <?php echo !empty($this->member["lastAction"])?" <small>(" . translateLastAction($this->member["lastAction"]) . ")</small>":"";?></div></li>

<li><label><?php echo $language["First posted"];?></label>
<div><abbr title='<?php echo date($language["dateFormat"],$this->member["firstPosted"]);?>'><?php echo relativeTime($this->member["firstPosted"]);?></abbr></div></li>

<li><label><?php echo $language["Post count"];?></label>
<div><?php echo number_format($this->member["postCount"]);?>
<?php if($this->member["postCount"]>0):?> <small>(<?php
$postsPerDay=round(min(max(0,$this->member["postCount"]/((time()-$this->member["firstPosted"])/60/60/24)),$this->member["postCount"]));if($postsPerDay==1)echo $language["post per day"];else printf($language["posts per day"],$postsPerDay);?>)</small><?php endif;?></div></li>

<li><label><?php echo $language["Conversations started"];?></label>
<div><?php echo number_format($this->member["conversationsStarted"]);?>
<?php if($this->member["conversationsStarted"]>0):?> <small>(<a href='<?php echo makeLink("search","?q2=author:".urlencode(desanitize($this->member["name"])));?>'><?php echo $language["show conversations started"];?></a>)</small><?php endif;?></div></li>

<li><label><?php echo $language["Conversations participated in"];?></label>
<div><?php echo $this->member["conversationsParticipated"];?>
<?php if($this->member["conversationsParticipated"]>0):?> <small>(<a href='<?php echo makeLink("search","?q2=contributor:".urlencode(desanitize($this->member["name"])));?>'><?php echo $language["show conversations participated in"];?></a>)</small><?php endif;?></div></li>
	
<?php $this->callHook("statistics");?>

<?php if($this->eso->user and $this->member["memberId"]!=$this->eso->user["memberId"]):?>
<li><label><?php echo $this->member["name"];?> &amp; <?php echo $this->eso->user["name"];?><br/><span class='label private'><?php echo $language["labels"]["private"];?></span></label> <div><a href='<?php echo makeLink("search","?q2=private+%2B+contributor:".urlencode(desanitize($this->member["name"])));?>'><?php printf($language["See the private conversations I've had"],$this->member["name"]);?></a><br/>
<a href='<?php echo makeLink("conversation","new","?member=".urlencode(desanitize($this->member["name"])),"&token={$_SESSION["token"]}");?>'><?php printf($language["Start a private conversation"],$this->member["name"]);?></a></div></li>
<?php endif;?>

</ul>
</div>
</div>

<?php // Output addition profile "sections" added by plugins.
ksort($this->sections);
foreach($this->sections as $section):?>
<div><?php echo $section;?></div>
<?php endforeach;?>

</div>
<div class='avatar'><img src='<?php echo $this->eso->getAvatar($this->member["memberId"],$this->member["avatarFormat"],"l");?>' alt=''/><?php $this->callHook("profileInfo"); ?></div>
<div class='clear'></div>
</div>

</fieldset>
