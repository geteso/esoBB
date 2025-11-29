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
 * Online view: displays a list of members currently online.
 */
if(!defined("IN_ESO"))exit;
?>

<fieldset id="fieldmembers">
<legend><?php echo $language["Online members"];?></legend>
<?php

if(!$this->eso->user and $config["onlineMembers"] === "login"):
echo $this->eso->htmlMessage("loginRequired");

// If there are members online, list them.
else:?>

<div id='membersOnline'>
<?php if($this->numberOnline):
while(list($memberId,$name,$avatarFormat,$color,$account,$lastSeen,$lastAction)=$this->eso->db->fetchRow($this->online)):?>
<div class='p c<?php echo $color;?>' data-member-id='<?php echo $memberId;?>'><div class='hdr'>
<div class='thumb'><a href='<?php echo makeLink("profile",$memberId);?>'><img src='<?php echo $this->eso->getAvatar($memberId,$avatarFormat,"thumb");?>' alt=''/></a></div>
<h3><a href='<?php echo makeLink("profile",$memberId);?>'><?php echo $name;?></a></h3>
<span><?php echo translateLastAction($lastAction);?> (<?php echo relativeTime($lastSeen);?>)</span>
</div></div>
<?php endwhile;
else:
echo $this->eso->htmlMessage("noMembersOnline");
endif;?>
</div>

<?php endif;?>

<script type='text/javascript'>
// <![CDATA[
Online.init();
// ]]>
</script>

</fieldset>
