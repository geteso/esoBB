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
 * Functions: contains functions which are used all over the application.
 */
if (!defined("IN_ESO")) exit;

// Substitute sensitive characters with a replacement character.
function sanitize($value)
{
	if (!is_array($value)) {
		$replace = array("&" => "&amp;", "<" => "&lt;", ">" => "&gt;", "'" => "&#39;", "\"" => "&quot;", "\\" => "&#92;", "\x00" => "");
		return strtr(trim($value), $replace);
	} else {
		foreach ($value as $k => $v) $value[$k] = sanitize($v);
		return $value;
	}
}

// Replace sanitized sensitive characters with their raw values.
function desanitize($value)
{
	if (!is_array($value)) {
		$replace = array("&amp;" => "&", "&lt;" => "<", "&gt;" => ">", "&#39;" => "'", "&#039;" => "'", "&quot;" => "\"", "&#92;" => "\\", "&#092;" => "\\");
		return strtr($value, $replace);
	} else {
		foreach ($value as $k => $v) $value[$k] = desanitize($v);
		return $value;
	}
}

// Sanitize a string for outputting in a HTML context.
function sanitizeHTML($value)
{
	return htmlentities($value, ENT_QUOTES, "UTF-8");
}

// Sanitize HTTP header-sensitive characters (CR and LF.)
function sanitizeForHTTP($value)
{
	return str_replace(array("\r", "\n", "%0a", "%0d", "%0A", "%0D"), "", $value);
}

// Sanitize file-system sensitive characters - filter anything but [A-Za-z0-9.-].
function sanitizeFileName($value)
{
	return preg_replace("/(?:[\/:\\\]|\.{2,}|\\x00)/", "", $value);
}

// Add slashes before double quotes.
function escapeDoubleQuotes($value)
{
	return str_replace('"', '\"', $value);
}

// Create a conversation title slug from a given string. Any non-alphanumeric characters will be converted to "-".
function slug($string)
{
	// If there are any characters other than basic alphanumeric, space, punctuation, then we need to attempt transliteration.
	if (preg_match("/[^\x20-\x7f]/", $string)) {

		// Thanks to krakos for this code!
		if (function_exists('transliterator_transliterate')) {

			// Unicode decomposition rules states that these cannot be decomposed, hence we have to deal with them manually.
			// Note: even though "scharfe s" is commonly transliterated as "sz", in this context "ss" is preferred as it's the most popular method among German speakers.
			$src = array('œ', 'æ', 'đ', 'ø', 'ł', 'ß', 'Œ', 'Æ', 'Đ', 'Ø', 'Ł');
			$dst = array('oe','ae','d', 'o', 'l', 'ss', 'OE', 'AE', 'D', 'O', 'L');
			$string = str_replace($src, $dst, $string);

			// Using transliterator to get rid of accents and convert non-Latin to Latin.
			$string = transliterator_transliterate("Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC; [:Punctuation:] Remove; Lower();", $string);
		
		}
		else {

			// A fallback to the old method.
			// Convert special Latin letters and other characters to HTML entities.
			$string = htmlentities($string, ENT_NOQUOTES, "UTF-8");

			// With those HTML entities, either convert them back to a normal letter, or remove them.
			$string = preg_replace(array("/&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml|caron);/i", "/&[^;]{2,6};/"), array("$1", " "), $string);

		}
	}

	// Allow plugins to alter the slug.
	global $eso;
	if (!empty($eso)) $eso->callHook("GenerateSlug", array(&$string));

	// Now replace non-alphanumeric characters with a hyphen, and remove multiple hyphens.
	if (extension_loaded("mbstring")) {
		$slug = str_replace(' ','-',trim(preg_replace('~[^\\pL\d]+~u',' ',mb_strtolower($string, "UTF-8"))));
		return mb_substr($slug, 0, 63, "UTF-8");
	} else {
		$slug = strtolower(trim(preg_replace(array("/[^0-9a-z]/i", "/-+/"), "-", $string), "-"));
		return substr($slug, 0, 63);
	}

}

// Finds $words in $text and puts a span with class='highlight' around them.
function highlight($text, $words)
{
	foreach ($words as $word) {
		if (!$word = trim($word)) continue;
		$text = preg_replace("/(?<=[\s>]|^)(" . preg_quote($word, "/") . ")(?=[\s<,.?!:]|$)/i", "<span class='highlight'>$1</span>", $text);
	}
	return $text; 
}

// Returns an array of the parameters in a mod_rewrite or index.php/... request.
// ex. passing /eso/forum/index.php/conversation/123 returns [conversation, 123]
//     passing /base/path/to/forum/search/test?query=string returns [search, test]
function processRequestURI($requestURI)
{
	// Extract the path from the base URL.
	global $config;
	$path = parse_url($config["baseURL"]);
	$path = $path["path"];
	// Remove the base path from the request URI.
	$request = preg_replace("|^$path|", "", $requestURI);
	// If there is a querystring, remove it.
	if (($pos = strpos($request, "?")) !== false) $request = substr_replace($request, "", $pos);
	// Explode the request string. Make sure index.php is not included.
	$parts = explode("/", trim(urldecode($request), "/"));
	if ($parts[0] == "index.php") array_shift($parts);
	return $parts;
}

// Generate a salt of $numOfChars characters long containing random letters, numbers, and symbols.
function generateRandomString($numOfChars, $possibleChars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890~!@#%^&*()_+=-{}[]:;<,>.?/`")
{
	$salt = "";
	for ($i = 0; $i < $numOfChars; $i++) $salt .= $possibleChars[rand(0, strlen($possibleChars) - 1)];
	return $salt;
}

// For bad server configs... ;) Pretty much just performing stripslashes on an array here.
function undoMagicQuotes($value)
{
	if (!is_array($value)) return stripslashes($value);
	else {
		foreach ($value as $k => $v) $value[$k] = undoMagicQuotes($v);
		return $value;
	}
}

// For bad server configs as well. It's funny how we have to go out of our way to fix stuff like this. :(
function undoRegisterGlobals()
{
    if (ini_get("register_globals")) {
        $array = array("_REQUEST", "_SESSION", "_SERVER", "_ENV", "_FILES");
        foreach ($array as $value) {
        	foreach ((array)$GLOBALS[$value] as $key => $var) {
            	if (isset($GLOBALS[$key]) and $var === $GLOBALS[$key]) unset($GLOBALS[$key]);
        	}
        }
    }
}

// Encodes JSON (JavaScript Object Notation) representations of values and returns a JSON string.
function json($array)
{
	// Loop through the array and check if the keys are all consecutive integers starting from 0.
	// If they are, we can use the JavaScript array syntax ['foo', 'bar'] rather than the object notation syntax.
	$noKeys = true;
	for ($i = 0, reset($array); $i < count($array); $i++, next($array)) {
		if (key($array) !== $i) {
			$noKeys = false;
			break;
		}
	}
	// Now loop through the array again, this time adding each key/value's string representation to the $json array.
	$json = array();
	foreach ($array as $k => $v) {
		if (is_array($v)) $value = json($v);
		elseif (is_string($v)) $value = "'" . str_replace(array("\r", "\n"), "\\n", addslashes($v)) . "'";
		elseif (is_bool($v)) $value = $v ? "true" : "false";
		elseif (is_null($v)) $value = "null";
		elseif (!is_object($v) and !is_resource($v)) $value = $v;
		else continue;
		if (!$noKeys) $key = preg_match("/[^\w]/", $k) ? "'" . addslashes($k) . "'" : $k;
		$json[] = $noKeys ? $value : "$key:$value";
	}
	// Return a JavaScript array string or a JSON string (depending if we're using keys or not.)
	return $noKeys ? "[" . implode(",", $json) . "]" : "{" . implode(",", $json) . "}";
}

// Function to quickly translate a string by using the $language variable.
function translate($string)
{
	global $language;
	return array_key_exists($string, $language) ? $language[$string] : $string;
}

// Generate a relative URL based on a variable number of arguments passed to the function.
// The exact output of the function depends on the values of $config["useFriendlyURLs"] and $config["useModRewrite"].
// ex. makeLink(123, "?start=4") -> "123?start=4", "index.php/123?start=4", "?q1=123&start=4"
// another ex. makeLink(61, "test-conversation", "?editPost=425", "&start=20", "#p425")
function makeLink()
{
	global $config;
	$link = "";
	$args = func_get_args();
	// Loop through the arguments.
	foreach ($args as $k => $q) {
		if ($q === []) continue;
		// If we are using friendly URLs, append a "/" to the argument if it's not prefixed with "#", "?", or "&".
		if (!empty($config["useFriendlyURLs"])) $link .= ($q[0] == "#" or $q[0] == "?" or $q[0] == "&") ? $q : "$q/";
		// Otherwise, convert anything not prefixed with "#", "?", or "&" to a "?q1=x" argument.
		else {
			if ($q[0] == "?" and $k != 0) $q[0] = "&";
			$link .= ($q[0] == "#" or $q[0] == "?" or $q[0] == "&") ? $q : ($k == 0 ? "?" : "&") . "q" . ($k + 1) . "=$q";
		}
	}
	// If we're not using mod_rewrite, we need to prepend "index.php/" to the link.
	if (!empty($config["useFriendlyURLs"]) and empty($config["useModRewrite"])) $link = "index.php/$link";
	return $link;
}

function cookieIp()
{
	$ip = htmlspecialchars($_SERVER["REMOTE_ADDR"]);
	// A fix for web servers that are not fully IPv6 compatible.
	if (strpos($ip, "::")) $ip = substr($ip, strrpos($ip, ":")+1);
	return ip2long($ip);
}

// Generate a link to the current page. To get a form to submit to the same page: <form action='curLink()'.
function curLink()
{
	// Remove the base path from the request URI, and return it as the curLink.
	global $curLink, $config;
	$path = parse_url($config["baseURL"]);
	$path = $path["path"];
	$curLink = preg_replace("|^$path|", "", $_SERVER["REQUEST_URI"]);
	return $curLink;
}

// Send a HTTP Location header to redirect to a specific page. (Uses the same argument syntax as makeLink().)
function redirect($return = false)
{
	global $config;
	$args = func_get_args();
	header("Location: " . sanitizeForHTTP($config["baseURL"] . call_user_func_array("makeLink", $args)));
	flush(); // Opera sometimes displays a blank "redirection" page if this isn't here. :/
	exit;
}

// Refresh the page by redirect()ing to the curLink() URL.
function refresh()
{
	global $config;
	header("Location: " . sanitizeForHTTP($config["baseURL"] . curLink()));
	flush(); // Again, Opera sometimes displays a blank "redirection" page if this isn't here. :/
	exit;
}

// Validate an email field: check it against a regular expression and make sure no validated account is using it.
function validateEmail(&$email)
{
	global $eso, $config;
	$email = substr($email, 0, 63);
	if (!preg_match("/^[A-Z0-9._%-+.-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i", $email)) return "invalidEmail";
	elseif ($eso->db->numRows($eso->db->query("SELECT 1 FROM {$config["tablePrefix"]}members WHERE email='" . $eso->db->escape($email) . "' AND account!='Unvalidated'"))) return "emailTaken";
}

// Validate the name field: make sure it's not reserved, is long enough, doesn't contain invalid characters, and is not already taken by another member.
function validateName(&$name)
{
	global $eso, $config;
	$reservedNames = $config["reservedNames"];

	if (!empty($eso)) $eso->callHook("beforeValidateName", array(&$name));

	// Make sure the name isn't a reserved word.
	if (in_array(strtolower($name), $reservedNames)) return "nameTaken";

	// Make sure the name is not too small or large.
	if (extension_loaded("mbstring")) $length = mb_strlen($name, "UTF-8");
	else $length = strlen($name);
	if ($length < 3 or $length > 20) return "nameEmpty";

	// It can't be empty either!
	if (!strlen($name)) return "nameEmpty";
	if (is_numeric($name) && (int)$name === 0) return "nameEmpty";

	// If we're not allowing weird characters, match anything outside of the non-extended ASCII alphabet.
	if (empty($config["nonAsciiCharacters"])) {
		if (preg_match("/[^[:print:]]/", $name)) return "invalidCharacters";
	}
	
	if (@$eso->db->result($eso->db->query("SELECT 1 FROM {$config["tablePrefix"]}members WHERE name='" . $eso->db->escape($name) . "' AND account!='Unvalidated'"), 0))
		return "nameTaken";
}

// Validate a password field: make sure it's not too long, then encrypt it with a salt.
function validatePassword(&$password)
{
	global $config;
	if (strlen($password) < $config["minPasswordLength"]) return "passwordTooShort";
//	$hash = md5($salt . $password);
//	return $hash;
}

// Work out the relative difference between the current time and a given timestamp.
// Returns a human-friendly string, ex. '1 hour ago'.
function relativeTime($then)
{
	global $language;
	
	// If there is no $then, we can only assume that whatever it is never happened...
	if (!$then) return $language["Never"];
	
	// Work out how many seconds it has been since $then.
	$ago = time() - $then;
	
	// If $then happened less than 1 second ago (or is yet to happen,) say "Just now".
	if ($ago < 1) return $language["Just now"];

	// 31536000 seconds = 1 year
	if ($ago >= 31536000) {
		$years = floor($ago / 31536000);
		return sprintf($language[($years == 1 ? "year" : "years") . " ago"], $years);
	}
	// 2626560 seconds = 1 month
	elseif ($ago >= 2626560) {
		$months = floor($ago / 2626560);
		return sprintf($language[($months == 1 ? "month" : "months") . " ago"], $months);
	}
	// 604800 seconds = 1 week
	elseif ($ago >= 604800) {
		$weeks = floor($ago / 604800);
		return sprintf($language[($weeks == 1 ? "week" : "weeks") . " ago"], $weeks);
	}
	// 86400 seconds = 1 day
	elseif ($ago >= 86400) {
		$days = floor($ago / 86400);
		return sprintf($language[($days == 1 ? "day" : "days") . " ago"], $days);
	}
	// 3600 seconds = 1 hour
	elseif ($ago >= 3600) {
		$hours = floor($ago / 3600);
		return sprintf($language[($hours == 1 ? "hour" : "hours") . " ago"], $hours);
	}
	// 60 seconds = 1 minute
	elseif ($ago >= 60) {
		$minutes = floor($ago / 60);
		return sprintf($language[($minutes == 1 ? "minute" : "minutes") . " ago"], $minutes);
	}
	// 1 second = 1 second. Duh.
	elseif ($ago >= 1) {
		$seconds = floor($ago / 1);
		return sprintf($language[($seconds == 1 ? "second" : "seconds") . " ago"], $seconds);
	}
}

// Minify a JavaScript string using JSMin.
function minifyJS($js)
{
	require_once PATH_LIBRARY."/vendor/jsmin.php";
	return JSMin::minify($js);
}

// Send an email with proper headers.
function sendEmail($to, $subject, $body)
{
	global $config, $language, $eso;
	if (empty($config["sendEmail"])) return false;
	if (!preg_match("/^[A-Z0-9._%-+]+@[A-Z0-9.-]+.[A-Z]{2,4}$/i", $to)) return false;

	try {
		$phpmailer = PATH_LIBRARY.'/vendor/class.phpmailer.php';
		require_once($phpmailer);
		$mail = new PHPMailer\PHPMailer\PHPMailer(true);

		if (isset($eso) and ($return = $eso->callHook("sendEmail", array(&$to, &$subject, &$body), true)) !== null)
			return $return;

		if ($config["smtpAuth"])
			$mail->IsSMTP();
			$mail->SMTPAuth = true;
			$mail->SMTPSecure = $config["SMTP"]["auth"];
			if ($config["smtpHost"]) $mail->Host = $config["smtpHost"];
			if ($config["smtpPort"]) $mail->Port = $config["smtpPort"];
			if ($config["smtpUser"]) $mail->Username = $config["smtpUser"];
			if ($config["smtpPass"]) $mail->Password = $config["smtpPass"];
		$mail->CharSet = 'UTF-8';
		$mail->IsHTML(true);
		$mail->AddAddress($to);
		$mail->SetFrom($config["emailFrom"], sanitizeForHTTP($config["forumTitle"]));
		$mail->Subject = sanitizeForHTTP(desanitize($subject));
		$mail->AltBody = strip_tags($body);
		$mail->Body = $body;
		$mail->Encoding = 'quoted-printable';

		return $mail->Send();
	} catch (Exception $e) {
		return false;
	}
}

// Return a list of files and their contents from a zip file.
function unzip($filename)
{
	$files = array();	
	$handle = fopen($filename, "rb");

	// Seek to the end of central directory record.
	$size = filesize($filename);
    @fseek($handle, $size - 22);

	// Error checking.
	if (ftell($handle) != $size - 22) return false; // Can't seek to end of central directory?
	// Check end of central directory signature.
	$data = unpack("Vid", fread($handle, 4));
	if ($data["id"] != 0x06054b50) return false;

	// Extract the central directory information.
	$centralDir = unpack("vdisk/vdiskStart/vdiskEntries/ventries/Vsize/Voffset/vcommentSize", fread($handle, 18));
	$pos = $centralDir["offset"];

	// Loop through each entry in the zip file.
	for ($i = 0; $i < $centralDir["entries"]; $i++) {
		
		// Read next central directory structure header.
		@rewind($handle);
		@fseek($handle, $pos + 4);
		$header = unpack("vversion/vversionExtracted/vflag/vcompression/vmtime/vmdate/Vcrc/VcompressedSize/Vsize/vfilenameLen/vextraLen/vcommentLen/vdisk/vinternal/Vexternal/Voffset", fread($handle, 42));
		
		// Get the filename.
		$header["filename"] = $header["filenameLen"] ? fread($handle, $header["filenameLen"]) : "";
		
		// Save the position.
		$pos = ftell($handle) + $header["extraLen"] + $header["commentLen"];

		// Go to the position of the file.
		@rewind($handle);
		@fseek($handle, $header["offset"] + 4);

		// Read the local file header to get the filename length.
		$localHeader = unpack("vversion/vflag/vcompression/vmtime/vmdate/Vcrc/VcompressedSize/Vsize/vfilenameLen/vextraLen", fread($handle, 26));

		// Get the filename.
		$localHeader["filename"] = fread($handle, $localHeader["filenameLen"]);
		// Skip the extra bit.
		if ($localHeader["extraLen"] > 0) fread($handle, $localHeader["extraLen"]);
		
		// Extract the file (if it's not a folder.)
		$directory = substr($header["filename"], -1) == "/";
		if (!$directory and $header["compressedSize"] > 0) {
			if ($header["compression"] == 0) $content = fread($handle, $header["compressedSize"]);
			else $content = gzinflate(fread($handle, $header["compressedSize"]));
	    } else $content = "";
		
		// Add to the files array.
		$files[] = array(
			"name" => $header["filename"],
			"size" => $header["size"],
			"directory" => $directory,
			"content" => !$directory ? $content : false
		);
		
	}

	fclose($handle);
	
	// Return an array of files that were extracted.
	return $files;
}

// Add an element to an array after a specifed a position.
// ex. $test = array(0 => "test", 3 => "asdf")
// addToArray($test, "foo", 2) : array(0 => "test", 2 => "foo", 3 => "asdf")
// addToArray($test, "foo", 3) : array(0 => "test", 3 => "asdf", 4 => "foo")
function addToArray(&$array, $add, $position = false)
{
	// If no position is specified, add it to the end and return the key
	if ($position === false) {
		$array[] = $add;
		end($array);
		ksort($array);
		return key($array);
	}
	// Else, until we can get ahold of a position (starting from the specified one), keep on going!
	do {
		if (isset($array[$position])) {
			$position++;
			continue;
		}
		$array[$position] = $add;
		ksort($array);
		return $position;
	} while (true);
}

// Add an element to an array using a string for a key but in a specified position.
// ex. $test = array("foo" => 1, "bar" => 2)
// addToArrayString($test, "test", 3, 0) : array("test" => 3, "foo" => 1, "bar" => 2)
// addToArrayString($test, "test", 3, 2) : array("foo" => 1, "test" => 3, "bar" => 2)
function addToArrayString(&$array, $key, $value, $position = false)
{
	// If we're intending to add it to the end of the array, that's easy.
	$count = count($array) + 1;
	if ($position >= $count or $position === false) {
		$array[$key] = $value;
		return;
	}
	// Otherwise, loop through the array one-by-one, constructing a new array as we go.
	// When we reach $position, add our new element, and then continue adding the elements from the old array.
	$newArray = array();
	for ($i = 0, reset($array); $i < $count; $i++) {
		if ($i == $position) $newArray[$key] = $value;
		else {
			$newArray[key($array)] = current($array);
			next($array);
		}
	}
	// Replace the old array with our new one.
	$array = $newArray;
}

// Convert a PHP variable to text so it can be outputted to a file and included later.
function variableToText($variable, $indent = "")
{
	$text = "";
	
	// If the variable is an array...
	if (is_array($variable)) {
		
		// Loop through the array and check if the keys are all consecutive integers starting from 0.
		// If they are, we can omit the keys.
		$noKeys = true;
		for ($i = 0, reset($variable); $i < count($variable); $i++, next($variable)) {
			if (key($variable) !== $i) {
				$noKeys = false;
				break;
			}
		}
		// Now loop through the array again, this time adding each key/value's string representation to $text.
		$text .= "array(\n";
		foreach ($variable as $k => $v) {
			$text .= $indent;
			if (!$noKeys) $text .= (is_string($k) ? "\"" . escapeDoubleQuotes($k) . "\"" : $k) . " => ";
			$text .= variableToText($v, "$indent\t") . ",\n";
		}
		$text = rtrim($text, ",\n") . "\n" . substr($indent, 1) . ")";
	}
	
	// If the variable is of another type, add its appropriate text representation.
	elseif (is_string($variable)) $text .= "\"" . escapeDoubleQuotes($variable) . "\"";
	elseif (is_bool($variable)) $text .= $variable ? "true" : "false";
	elseif (is_null($variable)) $text .= "null";
	elseif (!is_object($variable) and !is_resource($variable)) $text .= $variable;
	return $text;
}

// Write a standard config.php file containing an array.
function writeConfigFile($file, $variable, $settings)
{
	return writeFile($file, "<?php\n$variable = " . variableToText($settings, "\t") . ";\n?>");
}

// A shorthand version of fopen/fwrite/fclose. Returns false if the file can't be opened for writing.
function writeFile($file, $contents)
{
	// Attempt to open the file for writing.
	if (($handle = @fopen($file, "w")) === false) return false;
	// Write the file.
	fwrite($handle, $contents);
	fclose($handle);
	return true;
}

// Regenerate the session token.
function regenerateToken()
{
	session_regenerate_id(true);
	$_SESSION["token"] = substr(md5(uniqid(rand())), 0, 13);
	$_SESSION["userAgent"] = md5($_SERVER["HTTP_USER_AGENT"]);
}

// htmlspecialchars_decode for PHP 4.
if (!function_exists("htmlspecialchars_decode")) {
	function htmlspecialchars_decode($text)
	{
		return strtr($text, array_flip(get_html_translation_table(HTML_SPECIALCHARS)));
	}
}

?>
