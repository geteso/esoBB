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
 * Members view: displays registration settings and a list of unvalidated
 * members for admins to bring them in.
 */
if (!defined("IN_ESO")) exit;
?>

<?php
// If the user is an administrator, display the settings form.
if ($this->eso->user["admin"]):?>
<fieldset id='registrationSettings'>
<legend><?php echo $language["Registration settings"];?></legend>

<form action='<?php echo makeLink("admin", "members"); ?>' id='registrationSettings' method='post'>
<input type='hidden' name='token' value='<?php echo $_SESSION["token"]; ?>'/>

<ul class='form membersForm'>

<li><label class='checkbox'><?php echo $language["Allow registration"]; ?></label>
<input type='checkbox' class='checkbox' name='registrationOpen' value='1'<?php echo !empty($config["registrationOpen"]) ? " checked='checked'" : ""; ?>/></li>

<?php if(!empty($config["sendEmail"])):?><li><label><?php echo $language["Require email approval"]; ?><br/></label>
<input type='checkbox' class='checkbox' name='requireEmailApproval' value='1'<?php echo !empty($config["requireEmailApproval"]) ? " checked='checked'" : ""; ?>/></li><?php endif;?>

<li><label><?php echo $language["Require manual approval"]; ?><br/></label>
<input type='checkbox' class='checkbox' name='requireManualApproval' value='1'<?php echo !empty($config["requireManualApproval"]) ? " checked='checked'" : ""; ?>/></li>

<li><label></label> <span class='button'><input type='submit' name='saveMembersSettings' value='<?php echo $language["Save changes"]; ?>'/></span></li>

</ul>

</form>
</fieldset>
<?php endif; ?>

<fieldset id="registrationMembers">
<legend><?php echo $language["Unvalidated members"];?></legend>

<?php
// If there are unvalidated members, list them.
if($this->numberUnvalidated):?>

<div id='membersOnline'>
<?php while(list($memberId,$name,$avatarFormat,$color,$account)=$this->eso->db->fetchRow($this->unvalidated)):?>
<div class='p c<?php echo $color;?>'><div class='hdr'>
<div class='thumb'><a href='<?php echo makeLink("profile",$memberId);?>'><img src='<?php echo $this->eso->getAvatar($memberId,$avatarFormat,"thumb");?>' alt=''/></a></div>
<h3><a href='<?php echo makeLink("profile",$memberId);?>'><?php echo $name;?></a></h3>
<?php if(!empty($this->eso->canChangeGroup($memberId, $account))):?><form action='<?php echo curLink();?>' method='post'><div style='display:inline'><select onchange='Conversation.changeMemberGroup(<?php echo $memberId;?>,this.value)' name='group'>
	<?php foreach($this->eso->canChangeGroup($memberId, $account) as $group):?><option value='<?php echo $group;?>'<?php if($group==$account)echo " selected='selected'";?>><?php echo $language[$group];?></option><?php endforeach;?></select></div> <noscript><div style='display:inline'><input name='saveGroup' type='submit' value='Save' class='save'/><input type='hidden' name='member' value='<?php echo $memberId;?>'/></div></noscript></form>
<?php else:?><span><?php echo $language[$account];?></span>
<?php endif;?>
</div></div>
<?php endwhile;?>
</div>

<?php
// Otherwise, display a message.
else:echo $this->eso->htmlMessage("noMembersUnvalidated");endif;?>

</fieldset>
