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
 * Uploader class: provides a way to easily validate and manage uploaded
 * files.  Typically, an upload should be validated using the
 * getUploadedFile() function, which will return the temporary filename
 * of the uploaded file.  This can then be saved with saveAs() or
 * saveAsImage().
 */
class Uploader {

var $lastError = false;
var $lastFileInfo = false;

function iniToBytes($value)
{
	$l = substr($value, -1);
	$ret = substr($value, 0, -1);
	switch(strtoupper($l)){
		case "P":
			$ret *= 1024;
		case "T":
			$ret *= 1024;
		case "G":
			$ret *= 1024;
		case "M":
			$ret *= 1024;
		case "K":
			$ret *= 1024;
		break;
	}
	return $ret;
}

// Get the maximum file upload size in bytes.
function maxUploadSize()
{
	return min($this->iniToBytes(ini_get("post_max_size")), $this->iniToBytes(ini_get("upload_max_filesize")));
}

// Validate an uploaded file and return its temporary file name.
// Returns array with "success", "file", "error", "mime", "size" keys, or false on failure.
function getUploadedFile($key, $allowedTypes = array())
{
	$this->lastError = false;
	$this->lastFileInfo = false;

	// If the uploaded file doesn't exist, then we have to fail.
	if (!isset($_FILES[$key]) or !is_uploaded_file($_FILES[$key]["tmp_name"])) {
		$this->lastError = "fileUploadFailed";
		return false;
	}

	$file = $_FILES[$key];

	// Check for upload errors.
	switch ($file["error"]) {
		case 1:
		case 2:
			$this->lastError = sprintf("fileUploadTooBig", ini_get("upload_max_filesize"));
			return false;
		case 3:
		case 4:
		case 6:
		case 7:
		case 8:
			$this->lastError = "fileUploadFailed";
			return false;
	}

	// Check file size against max upload size.
	if ($file["size"] > $this->maxUploadSize()) {
		$this->lastError = sprintf("fileUploadTooBig", ini_get("upload_max_filesize"));
		return false;
	}

	// If allowed types are specified, validate MIME type.
	if (!empty($allowedTypes)) {
		// Validate file extension matches expected image type.
		$fileName = $file["name"];
		$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
		$allowedExtensions = array();
		foreach ($allowedTypes as $mime) {
			switch ($mime) {
				case "image/jpeg":
				case "image/pjpeg":
					$allowedExtensions[] = "jpg";
					$allowedExtensions[] = "jpeg";
					break;
				case "image/png":
				case "image/x-png":
					$allowedExtensions[] = "png";
					break;
				case "image/gif":
					$allowedExtensions[] = "gif";
					break;
				case "image/webp":
					$allowedExtensions[] = "webp";
					break;
			}
		}
		if (!empty($allowedExtensions) && !in_array($fileExtension, $allowedExtensions)) {
			$this->lastError = "avatarError";
			return false;
		}
		
		$fileInfo = @getimagesize($file["tmp_name"]);
		if (!$fileInfo || !isset($fileInfo["mime"]) || !in_array($fileInfo["mime"], $allowedTypes)) {
			$this->lastError = "avatarError";
			return false;
		}
		
		// Validate magic bytes for images.
		if (!$this->validateImageMagicBytes($file["tmp_name"], $fileInfo["mime"])) {
			$this->lastError = "avatarError";
			return false;
		}
		
		$this->lastFileInfo = $fileInfo;
	}

	return $file["tmp_name"];
}

// Save an uploaded file to the specified destination.
function saveAs($source, $destination)
{
	$this->lastError = false;
	
	// Ensure destination directory exists.
	$dir = dirname($destination);
	if (!is_dir($dir)) {
		if (!@mkdir($dir, 0755, true)) {
			$this->lastError = "fileUploadFailed";
			return false;
		}
	}
	
	// Check if destination directory is writable.
	if (!is_writable($dir)) {
		$this->lastError = "fileUploadFailed";
		return false;
	}
	
	// Attempt to move the uploaded file to the destination.
	if (!move_uploaded_file($source, $destination)) {
		$this->lastError = "fileUploadFailed";
		return false;
	}

	return $destination;
}

// Download a file from a remote URL and return the temporary file path.
// Returns temporary file path on success, false on failure.
function downloadFromUrl($url, $allowedTypes = array())
{
	$this->lastError = false;
	$this->lastFileInfo = false;
	
	// Make sure we can open URLs with fopen.
	if (!ini_get("allow_url_fopen")) {
		$this->lastError = "fileUploadFailed";
		return false;
	}
	
	// Validate the URL to prevent SSRF attacks.
	if (!($url = validateRemoteUrl($url))) {
		$this->lastError = "avatarError";
		return false;
	}
	
	// Get the image's type.
	$info = @getimagesize($url);
	if (!$info || !isset($info["mime"])) {
		$this->lastError = "avatarError";
		return false;
	}
	
	$type = $info["mime"];
	
	// Check if type is allowed.
	if (!empty($allowedTypes) && !in_array($type, $allowedTypes)) {
		$this->lastError = "avatarError";
		return false;
	}
	
	// Create a temporary file to store the downloaded image.
	$tempFile = tempnam(sys_get_temp_dir(), "eso_upload_");
	if (!$tempFile) {
		$this->lastError = "fileUploadFailed";
		return false;
	}
	
	// Open file handlers.
	$rh = @fopen($url, "rb");
	$wh = @fopen($tempFile, "wb");
	
	if ($rh === false || $wh === false) {
		@fclose($rh);
		@fclose($wh);
		@unlink($tempFile);
		$this->lastError = "fileUploadFailed";
		return false;
	}
	
	// Transfer the image from the remote location to our temporary file.
	while (!feof($rh)) {
		if (fwrite($wh, fread($rh, 1024)) === false) {
			fclose($rh);
			fclose($wh);
			@unlink($tempFile);
			$this->lastError = "fileUploadFailed";
			return false;
		}
	}
	fclose($rh);
	fclose($wh);
	
	// Validate magic bytes of downloaded file.
	if (!$this->validateImageMagicBytes($tempFile, $type)) {
		@unlink($tempFile);
		$this->lastError = "avatarError";
		return false;
	}
	
	$this->lastFileInfo = $info;
	return $tempFile;
}

// Validate file content by checking magic bytes (file signatures).
// Returns true if the file matches the expected image type, false otherwise.
function validateImageMagicBytes($filePath, $expectedMimeType)
{
	if (!file_exists($filePath) || !is_readable($filePath)) return false;
	
	$handle = @fopen($filePath, "rb");
	if (!$handle) return false;
	
	$header = @fread($handle, 12);
	@fclose($handle);
	
	if (strlen($header) < 12) return false;
	
	// Check magic bytes for different image types
	switch ($expectedMimeType) {
		case "image/jpeg":
		case "image/pjpeg":
			// JPEG: FF D8 FF
			return (substr($header, 0, 3) === "\xFF\xD8\xFF");
		
		case "image/png":
		case "image/x-png":
			// PNG: 89 50 4E 47 0D 0A 1A 0A
			return (substr($header, 0, 8) === "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A");
		
		case "image/gif":
			// GIF: 47 49 46 38 (GIF8)
			return (substr($header, 0, 4) === "GIF8");
		
		case "image/webp":
			// WebP: RIFF (bytes 0-3) + WEBP (bytes 8-11)
			// Header is already 12 bytes, so we can check both signatures
			return (substr($header, 0, 4) === "RIFF" && substr($header, 8, 4) === "WEBP");
		
		default:
			return false;
	}
}

// Validate an image file (MIME type and magic bytes).
function validateImage($filePath, $allowedTypes = array())
{
	if (!file_exists($filePath) || !is_readable($filePath)) return false;
	
	$fileInfo = @getimagesize($filePath);
	if (!$fileInfo || !isset($fileInfo["mime"])) return false;
	
	if (!empty($allowedTypes) && !in_array($fileInfo["mime"], $allowedTypes)) return false;
	
	return $this->validateImageMagicBytes($filePath, $fileInfo["mime"]);
}

// Get image information (dimensions and MIME type).
function getImageInfo($filePath)
{
	if (!file_exists($filePath) || !is_readable($filePath)) return false;
	
	$info = @getimagesize($filePath);
	if (!$info) return false;
	
	return array(
		"width" => $info[0],
		"height" => $info[1],
		"mime" => $info["mime"],
		"type" => $info[2]
	);
}

// Create a GD image resource from a file.
function createImageResource($filePath, $mimeType)
{
	switch ($mimeType) {
		case "image/jpeg":
		case "image/pjpeg":
			return @imagecreatefromjpeg($filePath);
		case "image/png":
		case "image/x-png":
			return @imagecreatefrompng($filePath);
		case "image/gif":
			return @imagecreatefromgif($filePath);
		case "image/webp":
			// Check if WebP support is available in GD library
			if (function_exists("imagecreatefromwebp")) {
				return @imagecreatefromwebp($filePath);
			}
			return false;
		default:
			return false;
	}
}

// Calculate resize dimensions for an image.
// Returns array with new width and height, or false on failure.
function resizeImage($curWidth, $curHeight, $maxWidth = null, $maxHeight = null, $exactWidth = null, $exactHeight = null)
{
	// If exact dimensions are specified, use those.
	if ($exactWidth !== null && $exactHeight !== null) {
		return array("width" => $exactWidth, "height" => $exactHeight, "needsResize" => ($exactWidth != $curWidth || $exactHeight != $curHeight));
	}
	
	// Otherwise, calculate based on max dimensions.
	if (($maxWidth or $maxHeight) and (($maxWidth and $maxWidth < $curWidth) or ($maxHeight and $maxHeight < $curHeight))) {
		$widthRatio = $maxWidth ? ($maxWidth / $curWidth) : null;
		$heightRatio = $maxHeight ? ($maxHeight / $curHeight) : null;
		$ratio = ($widthRatio and (!$heightRatio or $widthRatio <= $heightRatio)) ? $widthRatio : $heightRatio;
		$width = $ratio * $curWidth;
		$height = $ratio * $curHeight;
		return array("width" => $width, "height" => $height, "needsResize" => true);
	}
	
	return array("width" => $curWidth, "height" => $curHeight, "needsResize" => false);
}

// Save an image resource to a file.
function saveImageResource($image, $destination, $format, $quality = 85)
{
	switch ($format) {
		case "jpg":
		case "jpeg":
			return imagejpeg($image, $destination, $quality);
		case "png":
			return imagepng($image, $destination);
		case "gif":
			// GIF transparency is preserved by saving as PNG but keeping .gif extension
			return imagepng($image, $destination);
		case "webp":
			// Check if WebP support is available in GD library
			if (function_exists("imagewebp")) {
				return imagewebp($image, $destination, $quality);
			}
			return false;
		default:
			return false;
	}
}

// Save an uploaded or downloaded image with processing options.
// Options: maxWidth, maxHeight, width, height, format, quality, preserveAnimation, thumbnail, maxDimension
// Returns array with "success", "path", "format", "error" keys.
function saveAsImage($source, $destination, $options = array())
{
	$this->lastError = false;
	
	// Default options.
	$maxWidth = isset($options["maxWidth"]) ? $options["maxWidth"] : null;
	$maxHeight = isset($options["maxHeight"]) ? $options["maxHeight"] : null;
	$exactWidth = isset($options["width"]) ? $options["width"] : null;
	$exactHeight = isset($options["height"]) ? $options["height"] : null;
	$format = isset($options["format"]) ? $options["format"] : "auto";
	$quality = isset($options["quality"]) ? $options["quality"] : 85;
	$preserveAnimation = isset($options["preserveAnimation"]) ? $options["preserveAnimation"] : true;
	$thumbnail = isset($options["thumbnail"]) ? $options["thumbnail"] : false;
	$maxDimension = isset($options["maxDimension"]) ? $options["maxDimension"] : null; // Maximum width or height (prevents memory exhaustion)
	
	// Get image information.
	$info = $this->getImageInfo($source);
	if (!$info) {
		$this->lastError = "avatarError";
		return array("success" => false, "error" => "avatarError");
	}
	
	$curWidth = $info["width"];
	$curHeight = $info["height"];
	$mimeType = $info["mime"];
	
	// Check maximum dimension limits to prevent memory exhaustion attacks.
	if ($maxDimension !== null && ($curWidth > $maxDimension || $curHeight > $maxDimension)) {
		$this->lastError = "avatarError";
		return array("success" => false, "error" => "avatarError");
	}
	
	// Determine output format.
	if ($format == "auto") {
		switch ($mimeType) {
			case "image/jpeg":
			case "image/pjpeg":
				$outputFormat = "jpg";
				break;
			case "image/png":
			case "image/x-png":
				$outputFormat = "png";
				break;
			case "image/gif":
				$outputFormat = "gif";
				break;
			case "image/webp":
				$outputFormat = "webp";
				break;
			default:
				$this->lastError = "avatarError";
				return array("success" => false, "error" => "avatarError");
		}
	} else {
		$outputFormat = $format;
	}
	
	// Calculate resize dimensions.
	$resizeInfo = $this->resizeImage($curWidth, $curHeight, $maxWidth, $maxHeight, $exactWidth, $exactHeight);
	$width = $resizeInfo["width"];
	$height = $resizeInfo["height"];
	$needsToBeResized = $resizeInfo["needsResize"];
	
	// Ensure destination directory exists.
	$dir = dirname($destination);
	if (!is_dir($dir)) {
		if (!@mkdir($dir, 0755, true)) {
			$this->lastError = "fileUploadFailed";
			return array("success" => false, "error" => "fileUploadFailed");
		}
	}
	
	// If it's a GIF that doesn't need to be resized and we want to preserve animation...
	if (!$needsToBeResized && $mimeType == "image/gif" && $preserveAnimation && $format == "auto") {
		// Read the GIF file's contents.
		$handle = @fopen($source, "r");
		if ($handle) {
			$contents = @fread($handle, filesize($source));
			@fclose($handle);
			
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
			if (preg_match("/" . implode("|", $re) . "/", substr($contents, 0, 255))) {
				$needsToBeResized = true;
			} else {
				// But if it's all safe, write the image to the destination file!
				if (writeFile($destination . ".gif", $contents)) {
					// Handle thumbnail if requested.
					if ($thumbnail) {
						$this->createThumbnail($source, $destination, $thumbnail, $mimeType, $curWidth, $curHeight);
					}
					return array("success" => true, "path" => $destination . ".gif", "format" => "gif");
				}
			}
		}
	}
	
	// Load the image resource.
	$image = $this->createImageResource($source, $mimeType);
	if (!$image) {
		$this->lastError = "avatarError";
		return array("success" => false, "error" => "avatarError");
	}
	
	// If we need to resize or process the image...
	if ($needsToBeResized || $format != "auto" || $thumbnail) {
		// Create a new image with the target dimensions.
		$newImage = imagecreatetruecolor($width, $height);
		
		// Preserve the alpha for PNGs, GIFs, and WebP.
		if (in_array($mimeType, array("image/png", "image/gif", "image/x-png", "image/webp"))) {
			imagecolortransparent($newImage, imagecolorallocate($newImage, 0, 0, 0));
			imagealphablending($newImage, false);
			imagesavealpha($newImage, true);
		}
		
		// Copy and resize the image.
		// (Oh yeah, the reason we're doin' the whole imagecopyresampled() thing even for images that don't need to 
		// be resized is because it helps prevent a possible cross-site scripting attack in which the file has 
		// malicious data after the header. It also ensures EXIF data is stripped.)
		imagecopyresampled($newImage, $image, 0, 0, 0, 0, $width, $height, $curWidth, $curHeight);
		
		// Save the image.
		$destPath = $destination . "." . $outputFormat;
		if (!$this->saveImageResource($newImage, $destPath, $outputFormat, $quality)) {
			imagedestroy($newImage);
			imagedestroy($image);
			$this->lastError = "avatarError";
			return array("success" => false, "error" => "avatarError");
		}
		
		imagedestroy($newImage);
		
		// Handle thumbnail if requested.
		if ($thumbnail) {
			$this->createThumbnail($source, $destination, $thumbnail, $mimeType, $curWidth, $curHeight);
		}
		
		imagedestroy($image);
		return array("success" => true, "path" => $destPath, "format" => $outputFormat);
	}
	
	// If we don't need to resize, just copy the file.
	$destPath = $destination . "." . $outputFormat;
	if (!copy($source, $destPath)) {
		imagedestroy($image);
		$this->lastError = "fileUploadFailed";
		return array("success" => false, "error" => "fileUploadFailed");
	}
	
	imagedestroy($image);
	
	// Handle thumbnail if requested.
	if ($thumbnail) {
		$this->createThumbnail($source, $destination, $thumbnail, $mimeType, $curWidth, $curHeight);
	}
	
	return array("success" => true, "path" => $destPath, "format" => $outputFormat);
}

// Create a thumbnail of an image.
function createThumbnail($source, $destination, $thumbnailOptions, $mimeType, $curWidth, $curHeight)
{
	$thumbWidth = isset($thumbnailOptions["width"]) ? $thumbnailOptions["width"] : 64;
	$thumbHeight = isset($thumbnailOptions["height"]) ? $thumbnailOptions["height"] : 64;
	$suffix = isset($thumbnailOptions["suffix"]) ? $thumbnailOptions["suffix"] : "_thumb";
	
	// Calculate thumbnail dimensions.
	$resizeInfo = $this->resizeImage($curWidth, $curHeight, $thumbWidth, $thumbHeight);
	$thumbW = $resizeInfo["width"];
	$thumbH = $resizeInfo["height"];
	
	// Load the image resource.
	$image = $this->createImageResource($source, $mimeType);
	if (!$image) return false;
	
	// Create thumbnail image.
	$thumbImage = imagecreatetruecolor($thumbW, $thumbH);
	
	// Preserve alpha for PNGs, GIFs, and WebP.
	if (in_array($mimeType, array("image/png", "image/gif", "image/x-png", "image/webp"))) {
		imagecolortransparent($thumbImage, imagecolorallocate($thumbImage, 0, 0, 0));
		imagealphablending($thumbImage, false);
		imagesavealpha($thumbImage, true);
	}
	
	// Copy and resize.
	imagecopyresampled($thumbImage, $image, 0, 0, 0, 0, $thumbW, $thumbH, $curWidth, $curHeight);
	
	// Determine format from destination extension or MIME type.
	$thumbDest = $destination . $suffix;
	if ($mimeType == "image/jpeg" || $mimeType == "image/pjpeg") {
		$thumbDest .= ".jpg";
		$thumbFormat = "jpg";
	} elseif ($mimeType == "image/png" || $mimeType == "image/x-png") {
		$thumbDest .= ".png";
		$thumbFormat = "png";
	} elseif ($mimeType == "image/webp") {
		$thumbDest .= ".webp";
		$thumbFormat = "webp";
	} else {
		$thumbDest .= ".gif";
		$thumbFormat = "gif";
	}
	
	// Save thumbnail.
	$this->saveImageResource($thumbImage, $thumbDest, $thumbFormat, 85);
	
	imagedestroy($thumbImage);
	imagedestroy($image);
	
	return true;
}

}
