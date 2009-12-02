{**
 * header.tpl
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Paper View -- Header component.
 *
 * $Id$
 *}
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>{$paper->getFirstAuthor(true)|escape}</title>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset|escape}" />
	{if $paper->getLocalizedSubject()}
		<meta name="keywords" content="{$paper->getLocalizedSubject()|escape}" />
	{/if}

	{include file="paper/dublincore.tpl"}
	{include file="paper/googlescholar.tpl"}

	<link rel="stylesheet" href="{$baseUrl}/lib/pkp/styles/common.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/paperView.css" type="text/css" />

	{foreach from=$stylesheets item=cssUrl}
		<link rel="stylesheet" href="{$cssUrl}" type="text/css" />
	{/foreach}

	<script type="text/javascript" src="{$baseUrl}/lib/pkp/js/general.js"></script>
	{$additionalHeadData}
</head>
<body>

<div id="container">

<div id="body">

<div id="main">

<h2>{$siteTitle|escape},&nbsp;{$schedConf->getFullTitle()|escape}</h2>

<div id="navbar">
	<ul class="menu">
		<li><a href="{url conference="index" schedConf="index" op="index"}" target="_parent">{translate key="navigation.home"}</a></li>
		<li><a href="{url page="about"}" target="_parent">{translate key="navigation.about"}</a></li>

		{if $isUserLoggedIn}
			<li><a href="{url conference="index" page="user"}" target="_parent">{translate key="navigation.userHome"}</a></li>
		{else}
			<li><a href="{url page="login"}" target="_parent">{translate key="navigation.login"}</a></li>
			<li><a href="{url page="user" op="account"}" target="_parent">{translate key="navigation.account"}</a></li>
		{/if}{* $isUserLoggedIn *}

		<li><a href="{url page="search"}" target="_parent">{translate key="navigation.search"}</a></li>

		{if $currentConference}
			{if $currentSchedConfsExist}<li><a href="{url schedConf="index" page="schedConfs" op="current"}" target="_parent">{translate key="navigation.current"}</a></li>{/if}
			{if $archivedSchedConfsExist}<li><a href="{url schedConf="index" page="schedConfs" op="archive"}" target="_parent">{translate key="navigation.archive"}</a></li>{/if}

			{if $enableAnnouncements}
				<li><a href="{url page="announcement"}" target="_parent">{translate key="announcement.announcements"}</a></li>
			{/if}{* $enableAnnouncements *}

			{call_hook name="Templates::Common::Header::Navbar::CurrentConference"}
		{/if}{* $currentConference *}

		{foreach from=$navMenuItems item=navItem}
			{if $navItem.url != '' && $navItem.name != ''}
				<li><a href="{if $navItem.isAbsolute}{$navItem.url|escape}{else}{$navItem.url|escape}{/if}" target="_parent">{if $navItem.isLiteral}{$navItem.name|escape}{else}{translate key=$navItem.name}{/if}</a></li>
			{/if}
		{/foreach}
	</ul>
</div>

<div id="breadcrumb">
	<a href="{url page="index"}" target="_parent">{translate key="navigation.home"}</a> &gt;
	<a href="{url schedConf=""}" target="_parent">{$conference->getConferenceTitle()|escape}</a> &gt;
	<a href="{url page="index"}" target="_parent">{$schedConf->getSchedConfTitle()|escape}</a> &gt;
	<a href="{url page="schedConf" op="presentations"}" target="_parent">{$track->getLocalizedTitle()|escape}</a> &gt;
	<a href="{url page="paper" op="view" path=$paperId|to_array:$galleyId}" class="current" target="_parent">{$paper->getFirstAuthor(true)|escape}</a>
</div>

<div id="content">
