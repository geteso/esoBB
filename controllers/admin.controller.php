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
 * Admin controller: handles the sections shown to admins, including the
 * admin dashboard and a way to change forum settings.
 */
class admin extends Controller {
	
var $view = "admin/admin.view.php";
var $subView = "";
var $sections = array();

function init()
{
	global $language;
	
	// Non-admins/mods aren't allowed here.
	if (!$this->eso->user["moderator"]) redirect("");

	// Add the default sections to the menu if the user is an administrator.
	if ($this->eso->user["admin"]) {
		$this->defaultSections = array("dashboard", "settings", "languages", "members", "plugins", "skins");
		$this->addSection("dashboard", $language["Dashboard"], array($this, "dashboardInit"), array($this, "dashboardAjax"));
		$this->addSection("settings", $language["Forum settings"], array($this, "settingsInit"));
		$this->addSection("languages", $language["Languages"], array($this, "languagesInit"));
		$this->addSection("members", $language["Member-plural"], array($this, "membersInit"));
		$this->addSection("plugins", $language["Plugins"], array($this, "pluginsInit"));
		$this->addSection("skins", $language["Skins"], array($this, "skinsInit"));
	// If the user is a moderator, add a limited array of sections to the menu.
	} elseif ($this->eso->user["moderator"]) {
		$this->defaultSections = array("dashboard", "members");
		$this->addSection("dashboard", $language["Dashboard"], array($this, "dashboardInit"), array($this, "dashboardAjax"));
		$this->addSection("members", $language["Member-plural"], array($this, "membersInit"));
	}
	
	$this->callHook("init");
	
	// Work out the current section. Use the first section (Dashboard) by default.
 	$this->section = defined("AJAX_REQUEST") ? @$_GET["section"] : @$_GET["q2"];
	reset($this->sections);
	if (!array_key_exists($this->section, $this->sections)) $this->section = key($this->sections);
	
	// Call the current section's initilization function.
	return call_user_func_array($this->sections[$this->section]["initFunction"], array(&$this));
}

function ajax()
{
 	if (empty($this->sections[$this->section]["ajaxFunction"])) return;
 	return call_user_func_array($this->sections[$this->section]["ajaxFunction"], array(&$this));
}

function dashboardInit(&$adminController)
{
	global $config, $language;
	$this->title = $language["Dashboard"];
	$this->subView = "admin/dashboard.php";
	
	// Get forum statistics to be outputted in the view.
	$this->stats = array(
		"Member-plural" => $this->eso->db->result("SELECT COUNT(*) FROM {$config["tablePrefix"]}members", 0),
 		"Conversation-plural" => $this->eso->db->result("SELECT COUNT(*) FROM {$config["tablePrefix"]}conversations", 0),
 		"Posts" => $this->eso->db->result("SELECT COUNT(*) FROM {$config["tablePrefix"]}posts", 0),
 		// "New members in the past week" => $this->eso->db->result("SELECT COUNT(*) FROM {$config["tablePrefix"]}members WHERE UNIX_TIMESTAMP()-60*60*24*7<joinTime", 0),
 		"New conversations in the past week" => $this->eso->db->result("SELECT COUNT(*) FROM {$config["tablePrefix"]}conversations WHERE UNIX_TIMESTAMP()-60*60*24*7<startTime", 0),
 		"New posts in the past week" => $this->eso->db->result("SELECT COUNT(*) FROM {$config["tablePrefix"]}posts WHERE UNIX_TIMESTAMP()-60*60*24*7<time", 0)	
	);
	
	$this->serverInfo = array(
 		"Forum version" => ESO_VERSION,
 		"PHP version" => phpversion(),
 		"MySQL version" => $this->eso->db->result("SELECT VERSION()", 0)
	);
	
	$this->callHook("dashboardInit");
}

function dashboardAjax(&$adminController)
{
	global $config;
	
 	switch (@$_POST["action"]) {
 		case "checkForUpdates":
 			if ($latestVersion = $this->eso->checkForUpdates()
				and ($this->user["memberId"] == $config["rootAdmin"]))
 					return $this->eso->htmlMessage("updatesAvailable", $latestVersion);
 	}
}

function settingsInit(&$adminController)
{
	global $language, $config;
	$this->subView = "admin/settings.php";
	$this->languages = $this->eso->getLanguages();

	// Change the forum logo?
	if (isset($_POST["changeLogo"])
	 	and $this->eso->validateToken(@$_POST["token"])
		and $this->changeLogo()) $this->eso->message("changesSaved");

	// Change the forum icon?
	if (isset($_POST["changeIcon"])
	 	and $this->eso->validateToken(@$_POST["token"])
		and $this->changeIcon()) $this->eso->message("changesSaved");

	// Save the settings?
	if (isset($_POST["saveSettings"])
	 	and $this->eso->validateToken(@$_POST["token"])
		and $this->saveSettings()) {
			$this->eso->message("changesSaved");
			refresh();
		}

	// Save the advanced settings?
	if (isset($_POST["saveAdvancedSettings"])
		and $this->eso->user["memberId"] == $config["rootAdmin"]
	 	and $this->eso->validateToken(@$_POST["token"])
		and $this->saveAdvancedSettings()) {
			$this->eso->message("changesSaved");
			refresh();
		}
	
}

function saveSettings()
{
	$newConfig = array();
	
    // Forum title must contain at least one character.
	if (empty($_POST["forumTitle"]) or !strlen($_POST["forumTitle"])) {$this->eso->message("forumTitleError"); return false;}
	$newConfig["forumTitle"] = $_POST["forumTitle"];

    // Forum description must contain at least one character.
	if (empty($_POST["forumDescription"]) or !strlen($_POST["forumDescription"])) {$this->eso->message("forumDescriptionError"); return false;}
	$newConfig["forumDescription"] = $_POST["forumDescription"];
	
	$newConfig["useFriendlyURLs"] = (bool)!empty($_POST["useFriendlyURLs"]);

	$newConfig["showDescription"] = (bool)!empty($_POST["showDescription"]);

	$newConfig["metaDescription"] = (bool)!empty($_POST["metaDescription"]);
	
	if (count($newConfig)) $this->writeSettingsConfig($newConfig);

	return true;
}

function saveAdvancedSettings()
{
	$newConfig = array();

	$newConfig["gzipOutput"] = (bool)!empty($_POST["gzipOutput"]);

	$newConfig["https"] = (bool)!empty($_POST["https"]);

	$newConfig["uploadPackages"] = (bool)!empty($_POST["uploadPackages"]);

	$newConfig["changeUsername"] = (bool)!empty($_POST["changeUsername"]);

	// Logins per minute must be greater than or equal to 1.
	if (empty($_POST["loginsPerMinute"]) or !is_numeric($_POST["loginsPerMinute"]) or ($_POST["loginsPerMinute"] < 1)) {$this->eso->message("invalidConfig"); return false;}
	$newConfig["loginsPerMinute"] = $_POST["loginsPerMinute"];

	// Minimum password length must be greater than or equal to 1.
	if (empty($_POST["minPasswordLength"]) or !is_numeric($_POST["minPasswordLength"]) or ($_POST["minPasswordLength"] < 1)) {$this->eso->message("invalidConfig"); return false;}
	$newConfig["minPasswordLength"] = $_POST["minPasswordLength"];

	$newConfig["nonAsciiCharacters"] = (bool)!empty($_POST["nonAsciiCharacters"]);

	if (empty($_POST["userOnlineExpire"]) or !is_numeric($_POST["userOnlineExpire"]) or ($_POST["userOnlineExpire"] < 1)) {$this->eso->message("invalidConfig"); return false;}
	$newConfig["userOnlineExpire"] = $_POST["userOnlineExpire"];

	if (empty($_POST["messageDisplayTime"]) or !is_numeric($_POST["messageDisplayTime"]) or ($_POST["messageDisplayTime"] < 1)) {$this->eso->message("invalidConfig"); return false;}
	$newConfig["messageDisplayTime"] = $_POST["messageDisplayTime"];

	if (empty($_POST["results"]) or !is_numeric($_POST["results"])) {$this->eso->message("invalidConfig"); return false;}
	$newConfig["results"] = $_POST["results"];

	if (!is_numeric($_POST["moreResults"])) {$this->eso->message("invalidConfig"); return false;}
	$newConfig["moreResults"] = $_POST["moreResults"];

	if (!is_numeric($_POST["numberOfTagsInTagCloud"])) {$this->eso->message("invalidConfig"); return false;}
	$newConfig["numberOfTagsInTagCloud"] = $_POST["numberOfTagsInTagCloud"];

	$newConfig["showAvatarThumbnails"] = (bool)!empty($_POST["showAvatarThumbnails"]);

	if (!is_numeric($_POST["updateCurrentResultsInterval"])) {$this->eso->message("invalidConfig"); return false;}
	$newConfig["updateCurrentResultsInterval"] = $_POST["updateCurrentResultsInterval"];

	if (!is_numeric($_POST["checkForNewResultsInterval"])) {$this->eso->message("invalidConfig"); return false;}
	$newConfig["checkForNewResultsInterval"] = $_POST["checkForNewResultsInterval"];

	// Amount of searches limited to per minute must be greater than or equal to 1.
	if (empty($_POST["searchesPerMinute"]) or !is_numeric($_POST["searchesPerMinute"]) or ($_POST["searchesPerMinute"] < 1)) {$this->eso->message("invalidConfig"); return false;}
	$newConfig["searchesPerMinute"] = $_POST["searchesPerMinute"];

	if (count($newConfig)) $this->writeSettingsConfig($newConfig);

	return true;
}

// Change the forum logo.
function changeLogo()
{
	if (empty($_POST["logo"]["type"])) return false;
	global $config;
	
	$allowedTypes = array("image/jpeg", "image/png", "image/gif", "image/pjpeg", "image/x-png");
	
	// This is where the forum logo will be saved, suffixed with an extension (eg. .jpg).
	$logoFile = "config/logo";
	
	switch ($_POST["logo"]["type"]) {
		
		// Upload an avatar from the user's computer.
		case "upload":
			
			// Check for an error submitting the file and make sure the upload is a valid image file type.
			if ($_FILES["logoUpload"]["error"] != 0
				or !is_uploaded_file($_FILES["logoUpload"]["tmp_name"])) {
				$this->eso->message("avatarError");
				return false;
			}
			
			// Verify the file is actually an image by checking its content, not just the MIME type
			$fileInfo = @getimagesize($_FILES["logoUpload"]["tmp_name"]);
			if (!$fileInfo || !isset($fileInfo["mime"]) || !in_array($fileInfo["mime"], $allowedTypes)) {
				$this->eso->message("avatarError");
				return false;
			}
			
			// Validate magic bytes to ensure file content matches declared type
			if (!validateImageMagicBytes($_FILES["logoUpload"]["tmp_name"], $fileInfo["mime"])) {
				$this->eso->message("avatarError");
				return false;
			}
			
			$type = $fileInfo["mime"];
			$file = $_FILES["logoUpload"]["tmp_name"];
			break;
		
		// Upload an avatar from a remote URL.
		case "url":
			
			// Make sure we can open URLs with fopen, otherwise there's no point in continuing!
			if (!ini_get("allow_url_fopen")) return false;
			
			// Validate the URL to prevent SSRF attacks.
			if (!($url = validateRemoteUrl($_POST["logo"]["url"]))) {
				$this->eso->message("avatarError");
				return false;
			}
			
			// Get the image's type.
			$info = @getimagesize($url);
			if (!$info || !isset($info["mime"])) {
				$this->eso->message("avatarError");
				return false;
			}
			$type = $info["mime"];
			
			// Check the type of the image, and open file read/write handlers.
			if (!in_array($type, $allowedTypes)
				or (($rh = @fopen($url, "rb")) === false)
				or (($wh = @fopen($logoFile, "wb")) === false)) {
				$this->eso->message("avatarError");
				return false;
			}
			
			// Transfer the image from the remote location to our server.
			while (!feof($rh)) {
				if (fwrite($wh, fread($rh, 1024)) === false) {
					$this->eso->message("avatarError");
					return false;
				}
			}
			fclose($rh); fclose($wh);
			
			// Validate magic bytes of downloaded file to ensure it matches declared type
			if (!validateImageMagicBytes($logoFile, $type)) {
				@unlink($logoFile);
				$this->eso->message("avatarError");
				return false;
			}
			
			$file = $logoFile;
			break;
		
		// Unset the user's avatar.
		case "none":
		
			// If the user doesn't have an avatar, we don't need to do anything!
			if (empty($config["forumLogo"])) return true;
			
			// Delete the avatar and thumbnail files.
			$file = $config["forumLogo"];
			if (file_exists($file)) @unlink($file);
			
			$config["forumLogo"] = "";
			$this->writeSettingsConfig(array("forumLogo" => ""));
			return true;
			
		default: return false;
	}
	
	// Phew, we got through all that. Now let's turn the image into a resource...
	switch ($type) {
		case "image/jpeg": case "image/pjpeg": $image = @imagecreatefromjpeg($file); break;
		case "image/x-png": case "image/png": $image = @imagecreatefrompng($file); break;
		case "image/gif": $image = @imagecreatefromgif($file);
	}
	if (!$image) {
		$this->eso->message("avatarError");
		return false;
	}
	// ...and get its dimensions.
	list($curWidth, $curHeight) = getimagesize($file);
	
	//if (!empty($_POST["resizeLogo"])) {
		//$newWidth = 30;
		$newWidth = null;
		$newHeight = 32;
	//}
	

		
		// Set the destination.
		$destination = $logoFile;
		
		if (file_exists($config["forumLogo"])) @unlink($config["forumLogo"]);

		// If the new max dimensions exist and are smaller than the current dimensions, we're gonna want to resize.
		if (($newWidth or $newHeight) and (($newWidth and $newWidth < $curWidth) or ($newHeight and $newHeight < $curHeight))) {
			
			// Work out the resize ratio and calculate the dimensions of the new image.
			$widthRatio = $newWidth ? ($newWidth / $curWidth) : null;
			$heightRatio = $newHeight ? ($newHeight / $curHeight) : null;
			$ratio = ($widthRatio and (!$heightRatio or $widthRatio <= $heightRatio)) ? $widthRatio : $heightRatio;
			$width = $ratio * $curWidth;
			$height = $ratio * $curHeight;
			$needsToBeResized = true;
		}
		
		// Otherwise just use the current dimensions.
		else {
			$width = $curWidth;
			$height = $curHeight;
			$needsToBeResized = false;
		}

		// If it's a gif that doesn't need to be resized (and it's not a thumbnail), we move instead of resampling 
		// so as to preserve animation.
		if (!$needsToBeResized and $type == "image/gif") {
			
			// Read the gif file's contents.
			$handle = fopen($file, "r"); 
			$contents = fread($handle, filesize($file)); 
			fclose($handle);
			
			// Filter the first 256 characters, making sure there are no HTML tags of any kind.
			// We have to do this because IE6 has a major security issue where if it finds any HTML in the first 256
			// characters, it interprets the rest of the document as HTML (even though it's clearly an image!)
			$tags = array("!-", "a hre", "bgsound", "body", "br", "div", "embed", "frame", "head", "html", "iframe", "input", "img", "link", "meta", "object", "plaintext", "script", "style", "table");
			$re = array();
			foreach ($tags as $tag) {
				$part = "(?:<";
				$length = strlen($tag);
				for ($i = 0; $i < $length; $i++) $part .= "\\x00*" . $tag[$i];
				$re[] = $part . ")";
			}
			
			// If we did find any HTML tags, we're gonna have to lose the animation by resampling the image.
			if (preg_match("/" . implode("|", $re) . "/", substr($contents, 0, 255))) $needsToBeResized = true;
			
			// But if it's all safe, write the image to the avatar file!
			else writeFile($destination . ".gif", $contents);
		}

		// If this is a gif image and it needs to be resized, if it's a thumbnail, or if it's any other type of image...
		if ($needsToBeResized or $type != "image/gif") {
			
			// -waves magic wand- Now, let's create the image!
			$newImage = imagecreatetruecolor($width, $height);
			
			// Preserve the alpha for pngs and gifs.
			if (in_array($type, array("image/png", "image/gif", "image/x-png"))) {
				imagecolortransparent($newImage, imagecolorallocate($newImage, 0, 0, 0));
				imagealphablending($newImage, false);
				imagesavealpha($newImage, true);
			}
			
			// (Oh yeah, the reason we're doin' the whole imagecopyresampled() thing even for images that don't need to 
			// be resized is because it helps prevent a possible cross-site scripting attack in which the file has 
			// malicious data after the header.)
			imagecopyresampled($newImage, $image, 0, 0, 0, 0, $width, $height, $curWidth, $curHeight);
			
			// Save the image to the correct destination and format.
			switch ($type) {
				// jpeg
				case "image/jpeg": case "image/pjpeg":
					if (!imagejpeg($newImage, "$destination.jpg", 85)) $saveError = true;
					break;
				// png
				case "image/x-png": case "image/png":
					if (!imagepng($newImage, "$destination.png")) $saveError = true;
					break;
				// gif - the only way to preserve gif transparency is to save the image as a png... but we'll still 
				// pretend that it's a gif!
				case "image/gif":
					if (!imagepng($newImage, "$destination.gif")) $saveError = true;
			}
			if (!empty($saveError))  {
				$this->eso->message("avatarError");
				return false;
			}

			// Clean up.
			imagedestroy($newImage);
		}
	
	// Clean up temporary stuff.
	imagedestroy($image);
//	@unlink($file);
	
	// Depending on the type of image that was uploaded, update the user's avatarFormat field.
	switch ($type) {
		case "image/jpeg": case "image/pjpeg": $avatarFormat = "jpg"; break;
		case "image/x-png": case "image/png": $avatarFormat = "png"; break;
		case "image/gif": $avatarFormat = "gif";
	}
	
	$config["forumLogo"] = $logoFile . "." . $avatarFormat;
	$this->writeSettingsConfig(array("forumLogo" => $config["forumLogo"]));
	
	return true;
}

// Change the forum icon.
function changeIcon()
{
	if (empty($_POST["icon"]["type"])) return false;
	global $config;
	
	$allowedTypes = array("image/jpeg", "image/png", "image/gif", "image/pjpeg", "image/x-png");
	
	// This is where the forum icon will be saved, suffixed with an extension (eg. .jpg).
	$iconFile = "config/icon";
	
	switch ($_POST["icon"]["type"]) {
		
		// Upload an avatar from the user's computer.
		case "upload":
			
			// Check for an error submitting the file and make sure the upload is a valid image file type.
			if ($_FILES["iconUpload"]["error"] != 0
				or !is_uploaded_file($_FILES["iconUpload"]["tmp_name"])) {
				$this->eso->message("avatarError");
				return false;
			}
			
			// Verify the file is actually an image by checking its content, not just the MIME type
			$fileInfo = @getimagesize($_FILES["iconUpload"]["tmp_name"]);
			if (!$fileInfo || !isset($fileInfo["mime"]) || !in_array($fileInfo["mime"], $allowedTypes)) {
				$this->eso->message("avatarError");
				return false;
			}
			
			// Validate magic bytes to ensure file content matches declared type
			if (!validateImageMagicBytes($_FILES["iconUpload"]["tmp_name"], $fileInfo["mime"])) {
				$this->eso->message("avatarError");
				return false;
			}
			
			$type = $fileInfo["mime"];
			$file = $_FILES["iconUpload"]["tmp_name"];
			break;
		
		// Upload an avatar from a remote URL.
		case "url":
			
			// Make sure we can open URLs with fopen, otherwise there's no point in continuing!
			if (!ini_get("allow_url_fopen")) return false;
			
			// Validate the URL to prevent SSRF attacks.
			if (!($url = validateRemoteUrl($_POST["icon"]["url"]))) {
				$this->eso->message("avatarError");
				return false;
			}
			
			// Get the image's type.
			$info = @getimagesize($url);
			if (!$info || !isset($info["mime"])) {
				$this->eso->message("avatarError");
				return false;
			}
			$type = $info["mime"];
			
			// Check the type of the image, and open file read/write handlers.
			if (!in_array($type, $allowedTypes)
				or (($rh = @fopen($url, "rb")) === false)
				or (($wh = @fopen($iconFile, "wb")) === false)) {
				$this->eso->message("avatarError");
				return false;
			}
			
			// Transfer the image from the remote location to our server.
			while (!feof($rh)) {
				if (fwrite($wh, fread($rh, 1024)) === false) {
					$this->eso->message("avatarError");
					return false;
				}
			}
			fclose($rh); fclose($wh);
			
			// Validate magic bytes of downloaded file to ensure it matches declared type
			if (!validateImageMagicBytes($iconFile, $type)) {
				@unlink($iconFile);
				$this->eso->message("avatarError");
				return false;
			}
			
			$file = $iconFile;
			break;
		
		// Unset the user's avatar.
		case "none":
		
			// If the user doesn't have an avatar, we don't need to do anything!
			if (empty($config["forumIcon"])) return true;
			
			// Delete the avatar and thumbnail files.
			$file = $config["forumIcon"];
			if (file_exists($file)) @unlink($file);
			
			$config["forumIcon"] = "";
			$this->writeSettingsConfig(array("forumIcon" => ""));
			return true;
			
		default: return false;
	}
	
	// Phew, we got through all that. Now let's turn the image into a resource...
	switch ($type) {
		case "image/jpeg": case "image/pjpeg": $image = @imagecreatefromjpeg($file); break;
		case "image/x-png": case "image/png": $image = @imagecreatefrompng($file); break;
		case "image/gif": $image = @imagecreatefromgif($file);
	}
	if (!$image) {
		$this->eso->message("avatarError");
		return false;
	}
	// ...and get its dimensions.
	list($curWidth, $curHeight) = getimagesize($file);
	
	// if (!empty($_POST["resizeIcon"])) {
		$newWidth = 256;
		$newHeight = 256;
	// }
	

		
		// Set the destination.
		$destination = $iconFile;
		
		if (file_exists($config["forumIcon"])) @unlink($config["forumIcon"]);

		// If the new max dimensions exist and are different from the current dimensions, we're gonna want to resize.
		if (($newWidth or $newHeight) and ($newWidth !== $curWidth or $newHeight !== $curHeight)) {
			
			// Work out the resize ratio and calculate the dimensions of the new image.
			$widthRatio = $newWidth / $curWidth;
			$heightRatio = $newHeight / $curHeight;
			$ratio = ($widthRatio and $widthRatio <= $heightRatio) ? $widthRatio : $heightRatio;
			$width = $ratio * $curWidth;
			$height = $ratio * $curHeight;
			$needsToBeResized = true;
		}
		
		// Otherwise just use the current dimensions.
		else {
			$width = $curWidth;
			$height = $curHeight;
			$needsToBeResized = false;
		}

		// If it's a gif that doesn't need to be resized (and it's not a thumbnail), we move instead of resampling 
		// so as to preserve animation.
		if (!$needsToBeResized and $type == "image/gif") {
			
			// Read the gif file's contents.
			$handle = fopen($file, "r"); 
			$contents = fread($handle, filesize($file)); 
			fclose($handle);
			
			// Filter the first 256 characters, making sure there are no HTML tags of any kind.
			// We have to do this because IE6 has a major security issue where if it finds any HTML in the first 256
			// characters, it interprets the rest of the document as HTML (even though it's clearly an image!)
			$tags = array("!-", "a hre", "bgsound", "body", "br", "div", "embed", "frame", "head", "html", "iframe", "input", "img", "link", "meta", "object", "plaintext", "script", "style", "table");
			$re = array();
			foreach ($tags as $tag) {
				$part = "(?:<";
				$length = strlen($tag);
				for ($i = 0; $i < $length; $i++) $part .= "\\x00*" . $tag[$i];
				$re[] = $part . ")";
			}
			
			// If we did find any HTML tags, we're gonna have to lose the animation by resampling the image.
			if (preg_match("/" . implode("|", $re) . "/", substr($contents, 0, 255))) $needsToBeResized = true;
			
			// But if it's all safe, write the image to the avatar file!
			else writeFile($destination . ".gif", $contents);
		}

		// If this is a gif image and it needs to be resized, if it's a thumbnail, or if it's any other type of image...
		if ($needsToBeResized or $type != "image/gif") {
			
			// -waves magic wand- Now, let's create the image!
			$newImage = imagecreatetruecolor($newWidth, $newHeight);
			
			// Preserve the alpha for pngs and gifs.
			if (in_array($type, array("image/png", "image/gif", "image/x-png"))) {
				imagecolortransparent($newImage, imagecolorallocate($newImage, 0, 0, 0));
				imagealphablending($newImage, false);
				imagesavealpha($newImage, true);
			}
			
			// (Oh yeah, the reason we're doin' the whole imagecopyresampled() thing even for images that don't need to 
			// be resized is because it helps prevent a possible cross-site scripting attack in which the file has 
			// malicious data after the header.)
			imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $curWidth, $curHeight);
			
			// Save the image to the correct destination and format.
			switch ($type) {
				// jpeg
				case "image/jpeg": case "image/pjpeg":
					if (!imagejpeg($newImage, "$destination.jpg", 85)) $saveError = true;
					break;
				// png
				case "image/x-png": case "image/png":
					if (!imagepng($newImage, "$destination.png")) $saveError = true;
					break;
				// gif - the only way to preserve gif transparency is to save the image as a png... but we'll still 
				// pretend that it's a gif!
				case "image/gif":
					if (!imagepng($newImage, "$destination.gif")) $saveError = true;
			}
			if (!empty($saveError))  {
				$this->eso->message("avatarError");
				return false;
			}

			// Clean up.
			imagedestroy($newImage);
		}
	
	// Clean up temporary stuff.
	imagedestroy($image);
//	@unlink($file);
	
	// Depending on the type of image that was uploaded, update the user's avatarFormat field.
	switch ($type) {
		case "image/jpeg": case "image/pjpeg": $avatarFormat = "jpg"; break;
		case "image/x-png": case "image/png": $avatarFormat = "png"; break;
		case "image/gif": $avatarFormat = "gif";
	}
	
	$config["forumIcon"] = $iconFile . "." . $avatarFormat;
	$this->writeSettingsConfig(array("forumIcon" => $config["forumIcon"]));
	
	return true;
}

function writeSettingsConfig($newConfigElements)
{
	include "config/config.php";
	writeConfigFile("config/config.php", '$config', array_merge($config, $newConfigElements));
	global $config;
	$config = array_merge($config, $newConfigElements);
}

function addSection($id, $title, $initFunction, $ajaxFunction = false, $position = false)
{
	addToArrayString($this->sections, $id, array("title" => $title, "initFunction" => $initFunction, "ajaxFunction" => $ajaxFunction), $position);
}

function languagesInit()
{	
	global $language, $config;
	$this->title = $language["Languages"];
	$this->subView = "admin/languages.php";
	$this->languages = $this->eso->getLanguages();
	
	// If the "add a new language pack" form has been submitted, attempt to install the uploaded pack.
	if (isset($_FILES["installLanguage"]) and $this->eso->validateToken(@$_POST["token"]) and !empty($config["uploadPackages"])) $this->installLanguage();

	// Save the language settings.
	if (isset($_POST["forumLanguage"])
		and $this->eso->validateToken(@$_POST["token"])
		and $this->changeLanguage(@$_POST["forumLanguage"])) {
			$this->eso->message("changesSaved");
			refresh();
		}

}

function changeLanguage($language)
{
	$newConfig = array();

	if (in_array($language, $this->languages)) $newConfig["language"] = $language;
	else return false;

    if (count($newConfig)) $this->writeSettingsConfig($newConfig);

	return true;
}

// Install an uploaded language pack.
function installLanguage()
{
	// If the uploaded file has any errors, don't proceed.
	if ($_FILES["installLanguage"]["error"]) {
		$this->eso->message("invalidLanguagePack");
		return false;
	}
	
	// Move the uploaded language pack into the languages directory.
	if (!move_uploaded_file($_FILES["installLanguage"]["tmp_name"], "languages/{$_FILES["installLanguage"]["name"]}")) {
		$this->eso->message("notWritable", false, "languages/");
		return false;
	}
			
	// Everything worked correctly - success!
	$this->eso->message("languagePackAdded");
}

function membersInit(&$adminController)
{
	global $language, $config;
	$this->subView = "admin/members.php";
	$this->registrationSettings = array(
		"email" => $language["registrationEmail"],
		"manual" => $language["registrationManual"],
		"false" => $language["registrationAuto"]
	);

	// Save the settings?
	if (isset($_POST["saveMembersSettings"])
		and $this->eso->user["admin"]
	 	and $this->eso->validateToken(@$_POST["token"])
		and $this->saveMembersSettings()) {
			$this->eso->message("changesSaved");
			refresh();
		}
	
	// Fetch a list of unvalidated mmbers.
	$this->unvalidated = $this->eso->db->query("SELECT memberId, name, avatarFormat, IF(color>{$this->eso->skin->numberOfColors},{$this->eso->skin->numberOfColors},color), account, lastSeen, lastAction FROM {$config["tablePrefix"]}members WHERE account='Unvalidated' ORDER BY memberId DESC");
	$this->numberUnvalidated = $this->eso->db->numRows($this->unvalidated);
}

function saveMembersSettings()
{
	$newConfig = array();

	$newConfig["registrationOpen"] = (bool)!empty($_POST["registrationOpen"]);
	if (!empty($config["sendEmail"])) $newConfig["requireEmailApproval"] = (bool)!empty($_POST["requireEmailApproval"]);
	$newConfig["requireManualApproval"] = (bool)!empty($_POST["requireManualApproval"]);
	
	if (count($newConfig)) $this->writeSettingsConfig($newConfig);

	return true;
}

// Get all the plugins into an array and perform any plugin-related actions.
function pluginsInit(&$adminController)
{
	
	global $language, $config;
	$this->title = $language["Plugins"];
	$this->subView = "admin/plugins.php";
	
	// If the 'add a new plugin' form has been submitted, attempt to install the uploaded plugin.
	if (isset($_FILES["installPlugin"]) and $this->eso->validateToken(@$_POST["token"]) and !empty($config["uploadPackages"])) $this->installPlugin();
	
	// Get the installed plugins and their details by reading the plugins/ directory.
	if ($handle = opendir("plugins")) {
	    while (false !== ($file = readdir($handle))) {
		
			// Make sure the plugin is valid, and set up its class.
	        if ($file[0] != "." and is_dir("plugins/$file") and file_exists("plugins/$file/plugin.php") and (include_once "plugins/$file/plugin.php") and class_exists($file)) {
				$plugin = new $file;
				$plugin->eso =& $this->eso;
				
				// Has the settings form for this plugin been submitted?
				if (isset($_POST["saveSettings"]) and $_POST["plugin"] == $plugin->id and $this->eso->validateToken($_POST["token"]))
					$plugin->saveSettings();
				
				// Add the plugin to the installed plugins array.
				$this->plugins[$plugin->id] = array(
					"loaded" => in_array($file, $config["loadedPlugins"]),
					"name" => $plugin->name,
					"version" => $plugin->version,
					"description" => $plugin->description,
					"author" => $plugin->author,
					"settings" => $plugin->settings(),
					"plugin" => $plugin
				);
			}
			
	    }
	    closedir($handle);
	}
	ksort($this->plugins);
	
	if (defined("AJAX_REQUEST")) {
		switch ($_POST["action"]) {

			// Toggle a plugin.
			case "toggle":
				if (!$this->eso->validateToken(@$_POST["token"])) return;
				$this->togglePlugin(@$_POST["id"]);
		}
		
		return;
	}
	
	// Toggle a plugin if necessary.
	if (!empty($_GET["toggle"]) and $this->eso->validateToken(@$_GET["token"]) and $this->togglePlugin($_GET["toggle"]))
 		redirect("admin", "plugins");
}


// Toggle a plugin.
function togglePlugin($plugin)
{
	if (!$plugin or !array_key_exists($plugin, $this->plugins)) return false;
	global $config, $messages;
	
	// If the plugin is currently enabled, take it out of the loaded plugins array.
	$k = array_search($plugin, $config["loadedPlugins"]);
	if ($k !== false) unset($config["loadedPlugins"][$k]);
	
	// Otherwise, if it's not enabled, add it to the array.
	elseif ($k === false) {
		$config["loadedPlugins"][] = $plugin;
		if ($msg = $this->plugins[$plugin]["plugin"]->enable()) {
			$this->eso->message("pluginCannotBeEnabled", false, array($this->plugins[$plugin]["name"], $messages[$msg]["message"]));
			return false;
		}
	}
	
	// Strip out duplicate and non-existing plugins from the array.
	$config["loadedPlugins"] = array_unique($config["loadedPlugins"]);
	foreach ($config["loadedPlugins"] as $k => $v) {
		if (!array_key_exists($v, $this->plugins)) unset($config["loadedPlugins"][$k]);
	}
	
	// Write the config/plugins.php file.
	if (!writeConfigFile("config/plugins.php", '$config["loadedPlugins"]', array_values((array)$config["loadedPlugins"]))) {
		$this->eso->message("notWritable", false, "config/plugins.php");
		return false;
	}
	
	return true;
}

// Install an uploaded plugin.
function installPlugin()
{
	// If the uploaded file has any errors, don't proceed.
	if ($_FILES["installPlugin"]["error"]) {
		$this->eso->message("invalidPlugin");
		return false;
	}
	
	// Temorarily move the uploaded plugin into the plugins directory so that we can read it.
	if (!move_uploaded_file($_FILES["installPlugin"]["tmp_name"], "plugins/{$_FILES["installPlugin"]["name"]}")) {
		$this->eso->message("notWritable", false, "plugins/");
		return false;
	}
	
	// Unzip the plugin. If we can't, show an error.
	if (!($files = unzip("plugins/{$_FILES["installPlugin"]["name"]}", "plugins/"))) $this->eso->message("invalidPlugin");
	else {
		
		// Loop through the files in the zip and make sure it's a valid plugin.
		$directories = 0; $pluginFound = false;
		foreach ($files as $k => $file) {
			
			// Strip out annoying Mac OS X files!
			if (substr($file["name"], 0, 9) == "__MACOSX/" or substr($file["name"], -9) == ".DS_Store") {
				unset($files[$k]);
				continue;
			}
			
			// If the zip has more than one base directory, it's not a valid plugin.
			if ($file["directory"] and substr_count($file["name"], "/") < 2) $directories++;
			
			// Make sure there's an actual plugin file in there.
			if (substr($file["name"], -10) == "plugin.php") $pluginFound = true;
		}
		
		// OK, this plugin in valid!
		if ($pluginFound and $directories == 1) {
			
			// Loop through plugin files and write them to the plugins directory.
			$error = false;
			foreach ($files as $k => $file) {
				
				// Make a directory if it doesn't exist!
				if ($file["directory"] and !is_dir("plugins/{$file["name"]}")) mkdir("plugins/{$file["name"]}");
				
				// Write a file.
				elseif (!$file["directory"]) {
					if (!writeFile("plugins/{$file["name"]}", $file["content"])) {
						$this->eso->message("notWritable", false, "plugins/{$file["name"]}");
						$error = true;
						break;
					}
				}
			}
			
			// Everything copied over correctly - success!
			if (!$error) $this->eso->message("pluginAdded");
		}
		
		// Hmm, something went wrong. Show an error.
		else $this->eso->message("invalidPlugin");
	}
	
	// Delete the temporarily uploaded plugin file.
	unlink("plugins/{$_FILES["installPlugin"]["name"]}");
}

var $skins = array();

// Get all the skins into an array.
function skinsInit()
{	
	global $language, $config;
	$this->title = $language["Skins"];
	$this->subView = "admin/skins.php";
	
	
	// If the 'add a new skin' form has been submitted, attempt to install the uploaded skin.
	if (isset($_FILES["installSkin"]) and $this->eso->validateToken(@$_POST["token"]) and !empty($config["uploadPackages"])) $this->installSkin();
	
	// Get the installed skins and their details by reading the skins/ directory.
	if ($handle = opendir("skins")) {
	    while (false !== ($file = readdir($handle))) {
		
			// Make sure the skin is valid, and set up its class.
	        if ($file[0] != "." and is_dir("skins/$file") and file_exists("skins/$file/skin.php") and (include_once "skins/$file/skin.php") and class_exists($file)) {
	        	$skin = new $file;
				if (file_exists("skins/$file/preview.jpg")) $preview = "preview.jpg";
				elseif (file_exists("skins/$file/preview.png")) $preview = "preview.png";
				elseif (file_exists("skins/$file/preview.gif")) $preview = "preview.gif";
				else $preview = "";
				$this->skins[$file] = array(
					"selected" => $config["skin"] == $file,
					"name" => $skin->name,
					"version" => $skin->version,
					"author" => $skin->author,
					"preview" => $preview
				);
			}
			
	    }
	    closedir($handle);
	}
	ksort($this->skins);
	
	// Activate a skin in necessary.
	if (!empty($_GET["q3"]) and $this->eso->validateToken(@$_GET["token"])) $this->changeSkin($_GET["q3"]);
}

// Change the skin.
function changeSkin($skin)
{
	// Make sure the skin we're trying to change to exists!
	if (!array_key_exists($skin, $this->skins)) return false;
	
	// Write the skin configuration file...
	writeConfigFile("config/skin.php", '$config["skin"]', $skin);
	
	// ...and reload the page! All done!
	redirect("admin", "skins");
}

// Install an uploaded skin.
function installSkin()
{
	// If the uploaded file has any errors, don't proceed.
	if ($_FILES["installSkin"]["error"]) {
		$this->eso->message("invalidSkin");
		return false;
	}

	// Temorarily move the uploaded skin into the skins directory so that we can read it.
	if (!move_uploaded_file($_FILES["installSkin"]["tmp_name"], "skins/{$_FILES["installSkin"]["name"]}")) {
		$this->eso->message("notWritable", false, "skins/");
		return false;
	}

	// Unzip the skin. If we can't, show an error.
	if (!($files = unzip("skins/{$_FILES["installSkin"]["name"]}", "skins/"))) $this->eso->message("invalidSkin");
	else {
		
		// Loop through the files in the zip and make sure it's a valid skin.
		$directories = 0; $skinFound = false;
		foreach ($files as $k => $file) {

			// Strip out annoying Mac OS X files!
			if (substr($file["name"], 0, 9) == "__MACOSX/" or substr($file["name"], -9) == ".DS_Store") {
				unset($files[$k]);
				continue;
			}

			// If the zip has more than one base directory, it's not a valid skin.
			if ($file["directory"] and substr_count($file["name"], "/") < 2) $directories++;

			// Make sure there's an actual skin file in there.
			if (substr($file["name"], -8) == "skin.php") $skinFound = true;
		}

		// OK, this skin in valid!
		if ($skinFound and $directories == 1) {

			// Loop through skin files and write them to the skins directory.
			$error = false;
			foreach ($files as $k => $file) {

				// Make a directory if it doesn't exist!
				if ($file["directory"] and !is_dir("skins/{$file["name"]}")) mkdir("skins/{$file["name"]}");

				// Write a file.
				elseif (!$file["directory"]) {
					if (!writeFile("skins/{$file["name"]}", $file["content"])) {
						$this->eso->message("notWritable", false, "skins/{$file["name"]}");
						$error = true;
						break;
					}
				}
			}
			
			// Everything copied over correctly - success!
			if (!$error) $this->eso->message("skinAdded");
		}
		
		// Hmm, something went wrong. Show an error.
		else $this->eso->message("invalidSkin");
	}
	
	// Delete the temporarily uploaded skin file.
	unlink("skins/{$_FILES["installSkin"]["name"]}");
}
	
}

?>
