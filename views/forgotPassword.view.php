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
 * Forgot password view: displays one of two interfaces - a form with an
 * email field to initiate a password retrieval, or a form to set a new
 * password.
 */
if(!defined("IN_ESO"))exit;
?>

<fieldset id='forgot-pass'>
<legend><?php echo $language["Forgot your password"];?></legend>
<?php

// Display a form with an email field.
if (!$this->setPassword):
echo $this->eso->htmlMessage("forgotPassword");?>

<form id='forgot-password' action='<?php echo makeLink("forgot-password");?>' method='post'>
<ul class='form'>
	
<li><label><?php echo $language["Enter your email"];?></label>
<input type='text' autocomplete='email' value='' name='email' id='email' class='text'/>
<div id='email-message'></div></li>

<li><label id='lbl-fgps'></label> <?php echo $this->eso->skin->button(array("id"=>"forgotPasswordSubmit","value"=>$language["Recover password"]));?></li>

</ul>
</form>

<script type='text/javascript'>
// <![CDATA[
ForgotPassword.fieldsValidated = {'email': false};
ForgotPassword.init();
// ]]>
</script>

<?php

// Display a form to set a new password.
else:
echo $this->eso->htmlMessage("setNewPassword");?>

<form id='forgot-pass-reset' action='<?php echo makeLink("forgot-password",@$_GET["q2"]);?>' method='post'>
<ul class='form'>

<li><label><?php echo $language["New password"];?></label>
<input type='password' autocomplete='new-password' value='' name='password' id='password' class='text'/>
<?php if(isset($this->errors["password"])):echo $this->eso->htmlMessage($this->errors["password"]);endif;?></li>

<li><label><?php echo $language["Confirm password"];?></label>
<input type='password' autocomplete='new-password' value='' name='confirm' id='confirm' class='text'/>
<?php if(isset($this->errors["confirm"])):echo $this->eso->htmlMessage($this->errors["confirm"]);endif;?></li>

<li><label id='lbl-fgcf'></label>
<?php echo $this->eso->skin->button(array("name"=>"changePassword","value"=>$language["Change password"]));?></li>

</ul>
</form>

<?php endif;?>
</fieldset>
