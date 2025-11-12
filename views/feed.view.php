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
 * Feed view: outputs items specified by the feed controller in Atom
 * format.
 */
if(!defined("IN_ESO"))exit;
?><?php
// Output XML declaration directly
echo '<?xml version="1.0" encoding="' . htmlspecialchars($language["charset"], ENT_XML1) . '"?>' . "\n";
?>
<feed xmlns="http://www.w3.org/2005/Atom">
	<title><?php echo htmlspecialchars($this->title, ENT_XML1, $language["charset"]);?></title>
	<link href="<?php echo htmlspecialchars($this->link, ENT_XML1, $language["charset"]);?>" rel="alternate"/>
	<?php if (!empty($this->subtitle)):?><subtitle><?php echo htmlspecialchars($this->subtitle, ENT_XML1, $language["charset"]);?></subtitle><?php endif;?>
	<id><?php echo htmlspecialchars($this->id, ENT_XML1, $language["charset"]);?></id>
	<updated><?php echo htmlspecialchars($this->updated, ENT_XML1, $language["charset"]);?></updated>
	<generator>esoBB</generator>
<?php foreach($this->items as $item):?>
	<entry>
		<title><?php echo htmlspecialchars($item["title"], ENT_XML1, $language["charset"]);?></title>
		<link href="<?php echo htmlspecialchars($item["link"], ENT_XML1, $language["charset"]);?>" rel="alternate"/>
		<id><?php echo htmlspecialchars($item["id"], ENT_XML1, $language["charset"]);?></id>
		<updated><?php echo htmlspecialchars($item["updated"], ENT_XML1, $language["charset"]);?></updated>
		<author>
			<name><?php echo htmlspecialchars($item["author"], ENT_XML1, $language["charset"]);?></name>
		</author>
		<content type="html"><![CDATA[<?php echo $item["content"];?>]]></content>
	</entry>
<?php endforeach;?>
</feed>
