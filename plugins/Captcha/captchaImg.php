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
 * Captcha image: generates a captcha image.
 * Exciting...
 */

// Set headers to prevent caching.
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header("Content-type: image/jpeg");

// Include the captcha configuration.
require "../../config/config.php";
if (file_exists("../../config/Captcha.php")) include "../../config/Captcha.php";
$numberOfCharacters = !empty($config["Captcha"]["numberOfCharacters"]) ? $config["Captcha"]["numberOfCharacters"] : 3;

// Set the session save path.
$rootPath = realpath(dirname(dirname(dirname(__FILE__))));
$sessionsPath = $rootPath . "/sessions";
if (!is_dir($sessionsPath)) {
	@mkdir($sessionsPath, 0755, true);
}
session_save_path($sessionsPath);
ini_set('session.gc_probability', 1);

// Start a session if one does not already exist.
if (!session_id()) {
	session_name("{$config["cookieName"]}_Session");
	session_start();
}

// Set up the captcha image.
$font = realpath("exagger8.otf");
$imgX = 30 + 30 * $numberOfCharacters;
$imgY = 40;
$img = @imagecreatetruecolor($imgX, $imgY) or die("Cannot Initialize new GD image stream");

// Generate the captcha string that the user will have to enter.
$chars = "abcdefghikmnprstuvwxy3456789";
$string = "";
for ($i = 0; $i < $numberOfCharacters; $i++) $string .= $chars[rand(0, strlen($chars) - 1)];
$_SESSION["captcha"] = md5($string);

// Draw the background of the image.
$background = imagecolorallocate($img, rand(110, 150), rand(110, 150), rand(110, 150));
imagefill($img, 0, 0, $background);

// Draw a few circles.
for ($i = 0; $i < 3; $i++) {
	$color = imagecolorallocate($img, rand(110, 150), rand(110, 150), rand(110, 150));
	imagefilledellipse($img, rand(0, $imgX), rand(0, $imgY), rand(25, $imgX), rand(25, $imgY), $color);
}

// Draw the characters.
$length = strlen($string);
for ($i = 0; $i < $length; $i++) {
	$shadow = imagecolorallocate($img, rand(100, 125), rand(100, 125), rand(100, 125));
	$color = imagecolorallocate($img, rand(50, 100), rand(50, 100), rand(50, 100));
	$angle = rand(-30, 30);
	imagettftext($img, 30, $angle, $i * 30 + 20 + 3, 30 + 3, $shadow, $font, $string[$i]);
	imagettftext($img, 30, $angle, $i * 30 + 20, 30, $color, $font, $string[$i]);
}

// Output the image!
imagejpeg($img, null, 40);
imagedestroy($img);

