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
 * Search view: displays tag and gambit clouds and includes the search
 * form and results.
 */
if(!defined("IN_ESO"))exit;
?>
<?php $this->callHook("beforeRenderTagCloud");?>

<div id='tagArea'>
<p id='tags'><?php
// Echo the most common tags.
ksort($this->tagCloud);
foreach($this->tagCloud as $k=>$v){
    echo "<a href='".makeLink("search","?q2=".urlencode(desanitize((!empty($_SESSION["search"])?"{$_SESSION["search"]} + ":"")."{$language["gambits"]["tag:"]}$k")))."' class='$v'>".str_replace(" ","&nbsp;",$k)."</a> ";
}
?></p>

<?php $this->callHook("afterRenderTagCloud");?>

<p id='gambits'><?php
// Echo the gambits alphabetically.
ksort($this->gambitCloud);
foreach($this->gambitCloud as $k=>$v){
    echo "<a href='".makeLink("search","?q2=".urlencode(desanitize((!empty($_SESSION["search"])?"{$_SESSION["search"]} + ":"").$k)))."' class='$v'>".str_replace(" ","&nbsp;",$k)."</a> ";
}
?></p>
</div>

<?php $this->callHook("beforeRenderSearchForm");?>

<?php include $this->eso->skin->getView("searchForm.inc.php");?> 

<?php $this->callHook("afterRenderSearchForm");?>

<div id='searchResults'>
<?php include $this->eso->skin->getView("searchResults.inc.php");?>
</div>

<?php $this->callHook("afterRenderSearchResults");?>

<script type='text/javascript'>
// <![CDATA[
Search.currentSearch = <?php echo isset($_SESSION["search"]) ? json(desanitize($_SESSION["search"])) : "''";?>;
Search.init();
// ]]>
</script>
