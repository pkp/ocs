{**
 * paper.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Paper View.
 *
 * $Id$
 *}

<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>{$paper->getFirstPresenter(true)|escape}</title>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset}" />
	<meta name="description" content="" />
	<meta name="keywords" content="" />
	<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/paperView.css" type="text/css" />
	{foreach from=$stylesheets item=cssUrl}
	<link rel="stylesheet" href="{$cssUrl}" type="text/css" />
	{/foreach}
	<script type="text/javascript" src="{$baseUrl}/js/general.js"></script>
</head>
<body>

<div id="container">

<div id="body">

<div id="main">

<h2>{$siteTitle|escape},&nbsp;{$schedConf->getFullTitle()|escape}</h2>

<div id="navbar">
	<ul class="menu">
		<li><a href="{url schedConf=""}" target="_parent">{translate key="navigation.home"}</a></li>
		<li><a href="{url page="about"}" target="_parent">{translate key="navigation.about"}</a></li>
		{if $isUserLoggedIn}
			<li><a href="{url page="user"}" target="_parent">{translate key="navigation.userHome"}</a></li>
		{else}
			<li><a href="{url page="login"}" target="_parent">{translate key="navigation.login"}</a></li>
			<li><a href="{url page="user" op="account"}" target="_parent">{translate key="navigation.account"}</a></li>
		{/if}
		<li><a href="{url page="search"}" target="_parent">{translate key="navigation.search"}</a></li>
		{if $currentConference}
			{if $currentSchedConfsExist}<li><a href="{url schedConf="index" page="schedConfs" op="current"}">{translate key="navigation.current"}</a></li>{/if}
			{if $archivedSchedConfsExist}<li><a href="{url schedConf="index" page="schedConfs" op="archive"}">{translate key="navigation.archive"}</a></li>{/if}
			{if $enableAnnouncements}
				<li><a href="{url page="announcement"}" target="_parent">{translate key="announcement.announcements"}</a></li>
			{/if}
			{call_hook name="Templates::Common::Header::Navbar::CurrentConference"}
		{/if}
		{foreach from=$navMenuItems item=navItem}
			<li><a href="{if $navItem.isAbsolute}{$navItem.url|escape}{else}{$navItem.url|escape}{/if}" target="_parent">{if $navItem.isLiteral}{$navItem.name|escape}{else}{translate key=$navItem.name}{/if}</a></li>
		{/foreach}
	</ul>
</div>

<div id="breadcrumb">
	<a href="{url page="index"}" target="_parent">{translate key="navigation.home"}</a> &gt;
	<a href="{url schedConf=""}" target="_parent">{$conference->getTitle()|escape}</a> &gt;
	<a href="{url page="index"}" target="_parent">{$schedConf->getTitle()|escape}</a> &gt;
	<a href="{url page="schedConf" op="proceedings"}" target="_parent">{$track->getTrackTitle()|escape}</a> &gt;
	<a href="{url page="paper" op="view" path=$paperId|to_array:$galleyId}" class="current" target="_parent">{$paper->getFirstPresenter(true)|escape}</a>
</div>

<div id="content">
{if $galley}
	{$galley->getHTMLContents()}
{else}

	<h3>{$paper->getPaperTitle()|strip_unsafe_html}</h3>
	<div><i>{$paper->getPresenterString()|escape}</i></div>
	<br />

	<h4>{translate key="paper.abstract"}</h4>
	<br />
	<div>{$paper->getPaperAbstract()|strip_unsafe_html|nl2br}</div>
	<br />

	{if $mayViewPaper}
		{assign var=galleys value=$paper->getGalleys()}
		{if $galleys}
			{translate key="reader.fullText"}
			{foreach from=$galleys item=galley name=galleyList}
				<a href="{url page="paper" op="view" path=$paperId|to_array:$galley->getGalleyId()}" class="action" target="_parent">{$galley->getLabel()|escape}</a>
			{/foreach}
		{/if}
	{elseif $schedConf->getSetting('delayOpenAccess') && $schedConf->getSetting('delayOpenAccessDate') > time()}
		{translate key="reader.fullTextSubscribersOnlyUntil" date=$schedConf->getSetting('delayOpenAccessDate')|date_format:$dateFormatShort}
	{else}
		{translate key="reader.fullTextSubscribersOnly"}
	{/if}
{/if}

{if $comments}
<div class="separator"></div>
<h4>{translate key="comments.commentsOnPaper"}</h4>

<ul>
{foreach from=$comments item=comment}
{assign var=poster value=$comment->getUser()}
	<li>
		<a href="{url page="comment" op="view" path=$paper->getPaperId()|to_array:$galleyId:$comment->getCommentId()}" target="_parent">{$comment->getTitle()|escape}</a>
		{if $comment->getChildCommentCount()==1}{translate key="comments.oneReply"}{elseif $comment->getChildCommentCount()>0}{translate key="comments.nReplies" num=$comment->getChildCommentCount()}{/if}<br/>
		{if $poster}{translate key="comments.authenticated" userName=$comment->getPosterName()|escape}{elseif $comment->getPosterName()}{translate key="comments.anonymousNamed" userName=$comment->getPosterName()|escape}{else}{translate key="comments.anonymous"}{/if} ({$comment->getDatePosted()|date_format:$dateFormatShort})
	</li>
{/foreach}
</ul>

<a href="{url page="comment" op="view" path=$paper->getPaperId()|to_array:$galleyId}" class="action" target="_parent">{translate key="comments.viewAllComments"}</a>{if $postingAllowed}&nbsp;|&nbsp;<a class="action" href="{url page="comment" op="add" path=$paper->getPaperId()|to_array:$galleyId}" target="_parent">{translate key="rt.addComment"}</a>{/if}<br />

{if $commentsClosed}{translate key="comments.commentsClosed" closeCommentsDate=$closeCommentsDate|date_format:$dateFormatShort}<br />{/if}

{/if}

</div>

</div>
</div>
</div>

{if $defineTermsContextId}
<script type="text/javascript">
{literal}
<!--
	// Open "Define Terms" context when double-clicking any text
	function openSearchTermWindow(url) {
		var term;
		if (window.getSelection) {
			term = window.getSelection();
		} else if (document.getSelection) {
			term = document.getSelection();
		} else if(document.selection && document.selection.createRange && document.selection.type.toLowerCase() == 'text') {
			var range = document.selection.createRange();
			term = range.text;
		}
		openRTWindowWithToolbar(url + '?defineTerm=' + term);
	}

	if(document.captureEvents) {
		document.captureEvents(Event.DBLCLICK);
	}
	document.ondblclick = new Function("openSearchTermWindow('{/literal}{url page="rt" op="context" path=$paperId|to_array:$galleyId:$defineTermsContextId escape=false}{literal}')");
// -->
{/literal}
</script>
{/if}

</body>
</html>
