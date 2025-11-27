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
 * Wrapper: displays an HTML page with a header, bar, and footer.
 */
if(!defined("IN_ESO"))exit;
?>
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'>
<head>

<meta http-equiv='Content-Type' content='text/html; charset=<?php echo $language["charset"];?>'/>
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0'>

<title><?php echo ($this->controller->title?$this->controller->title." - ":"").$config["forumTitle"];?></title>
<meta name='title' content='<?php echo ($this->controller->title?$this->controller->title." - ":"").$config["forumTitle"];?>'>
<link rel='preload' href='<?php echo $config["baseURL"] . $this->skin->getForumLogo();?>' as='image'>

<!-- Open Graph -->
<meta property='og:site_name' content='<?php echo $config["forumTitle"];?>'>
<?php if (!empty($this->controller->title)): ?>
<meta property='og:title' content="<?php echo $this->controller->title;?>">
<?php else: ?>
<meta property='og:title' content="<?php echo $config["forumTitle"];?>">
<?php endif; ?>
<meta property='og:type' content='website'>
<meta property='og:image' content='<?php echo $config["baseURL"] . $this->skin->getForumIcon();?>'>
<meta property='og:url' content='<?php echo $config["baseURL"];?>'>
<!-- og:description added to head @ conversation.controller, search.controller -->

<!-- Twitter -->
<meta name='twitter:title' content='<?php echo ($this->controller->title?$this->controller->title." - ":"").$config["forumTitle"];?>'>
<meta name='twitter:card' content='summary'>
<meta name='twitter:image' content='<?php echo $config["baseURL"] . $this->skin->getForumIcon();?>'>
<!-- twitter:description added to head @ conversation.controller, search.controller -->

<!-- Progressive web app -->
<link rel='manifest' href='site.webmanifest'>
<meta name='format-detection' content='telephone=no'>
<meta name='apple-mobile-web-app-title' content='<?php echo $config["forumTitle"];?>'>
<?php if (!empty($config["manifestDisplay"]) and $config["manifestDisplay"] != "browser"): ?>
<meta name='mobile-web-app-capable' content='yes'>
<meta name='apple-mobile-web-app-capable' content='yes'>
<?php endif; ?>

<!-- Icons! -->
<link rel='icon' type='image/png' href='<?php echo $config["baseURL"] . $this->skin->getForumIcon();?>'>
<link rel='apple-touch-icon' href='<?php echo $config["baseURL"] . $this->skin->getForumIcon();?>'>
<meta name='msapplication-TileImage' content='<?php echo $config["baseURL"] . $this->skin->getForumIcon();?>'>
<?php if (!empty($config["themeColor"])): ?>
<meta name='theme-color' content='#<?php echo $config["themeColor"];?>'>
<meta name='msapplication-TileColor' content='#<?php echo $config["themeColor"];?>'>
<?php endif; ?>

<?php echo $this->head();?> 
</head>

<body>
<?php $this->callHook("pageStart");?>

<div id='loading' style='display:none'><?php echo $language["Loading"];?></div>

<?php echo $this->getMessages();?>

<div id='wrapper'<?php if($this->action!="search"):?> class='small'<?php endif;?>>

<!-- HEADER -->
<div id='header'>
<div id='hdr'>

<?php if (($config["showDescription"] == "true") && ($this->action == "search")): ?>
<h1 id="hasForumDescription">
<?php else: ?>
<h1>
<?php endif; ?>

<a href='' title='<?php echo $config["forumTitle"];?>'><img src='<?php echo $this->skin->getForumLogo();?>' data-fallback='<?php echo !empty($config["forumLogo"])?$config["forumLogo"]:"skins/{$config["skin"]}/logo.png";?>' alt=''
/><span id='forumTitle'><?php echo $config["forumTitle"];?>
<?php if (($config["showDescription"] == "true") && ($this->action == "search")): ?>
<small id='forumDescription'><?php echo $config["forumDescription"];?></small>
<?php endif; ?>
</span>
</a></h1>
<?php if($this->action=="search"):?>
<p id='stats'>
<?php foreach($this->getStatistics() as $k=>$v):?>
<span id='statistic-<?php echo $k;?>'><?php echo $v;?></span><br/>
<?php endforeach;?>
</p>
<?php else:include "views/searchForm.inc.php";endif;?>
</div>

<!-- NAVIGATION -->
<div id='bar'>
<?php if(count($this->bar["left"])):?><ul class='fl'><?php ksort($this->bar["left"]);foreach($this->bar["left"] as $v)echo "<li>$v</li>";?></ul><?php endif;?>
<?php if(count($this->bar["right"])):?><ul class='fr'><?php ksort($this->bar["right"]);foreach($this->bar["right"] as $v)echo "<li>$v</li>";?></ul><?php endif;?>
</div>

</div>

<div id='body'>
<div id='body-content'>
<?php $this->controller->render();?>
</div>
</div>

<?php $this->callHook("footer"); ?>
<div id='ftr'>
<div id='ftr-content'>
<p id='copyright'><!-- The following text constitutes a copyright notification. -->
<?php echo $language["Powered by"];?> <a href='https://geteso.org/' target='_blank' class='external' onclick='return Conversation.externalLink()'>esoBB</a><!-- A derivative of esoTalk.  Not directly affiliated with Simon or Toby Zerner. --> <?php if ($this->user["admin"]) echo ESO_VERSION; ?>
<!-- End copyright notification. --></p>
<?php if ($this->action !== "search"):
	$stats = "";
	foreach ($this->getStatistics() as $k=>$v) $stats .= "<span id='statistic-$k'>$v</span> - ";
	echo "<p id='stats'>".substr($stats,0,-3)."</p>";
endif; ?>
<ul class='footer'><?php if (count($this->footer)):
	ksort($this->footer);
	foreach ($this->footer as $v) echo "<li>$v</li>";
endif; ?></ul>
<ul class='bar'><?php if (count($this->bar["right"])):
	ksort($this->bar["right"]);foreach ($this->bar["right"] as $v) echo "<li>$v</li>";
endif; ?></ul>
</div>
</div>

<?php $this->callHook("pageEnd");?>
</div>
</body>
</html>
