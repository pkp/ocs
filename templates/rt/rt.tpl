{**
 * rt.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Reading Tools.
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
	<link rel="stylesheet" href="{$baseUrl}/styles/rt.css" type="text/css" />
	{foreach from=$stylesheets item=cssUrl}
	<link rel="stylesheet" href="{$cssUrl}" type="text/css" />
	{/foreach}
	<script type="text/javascript" src="{$baseUrl}/js/general.js"></script>
</head>
<body>

<div id="container">
<div id="main" style="width: 150px; font-size: 0.7em; padding-top: 1.5em; padding-left: 1em">

<h5>{$conference->getTitle()|escape}<br />{$schedConf->getTitle()|escape}</h5>

<p><a href="{url page="presentations"}" target="_parent" class="rtAction">{translate key="schedConf.presentations"}</a></p>

<h5>{translate key="rt.readingTools"}</h5>

<div class="rtSeparator"></div>

<h6>{$paper->getPaperTitle()|strip_unsafe_html|truncate:20:"...":true}</h6>
<p><i>{$paper->getPresenterString(true)|escape}</i></p>

<div class="rtSeparator"></div>

<br />

{if $conferenceRt->getEnabled()}
<div class="rtBlock">
	<ul>
		{if $conferenceRt->getAbstract() && $galley}<li><a href="{url page="paper" op="view" path=$paperId}" target="_parent">{translate key="paper.abstract"}</a></li>{/if}
		<li><a href="{url page="about" op="editorialPolicies" anchor="peerReviewProcess"}" target="_parent">{translate key="rt.reviewPolicy"}</a></li>
		{if $conferenceRt->getPresenterBio()}<li><a href="javascript:openRTWindow('{url page="rt" op="bio" path=$paperId|to_array:$galleyId}');">{translate key="rt.presenterBio"}</a></li>{/if}
		{if $conferenceRt->getCaptureCite()}<li><a href="javascript:openRTWindow('{url page="rt" op="captureCite" path=$paperId|to_array:$galleyId}');">{translate key="rt.captureCite"}</a></li>{/if}
		{if $conferenceRt->getViewMetadata()}<li><a href="javascript:openRTWindow('{url page="rt" op="metadata" path=$paperId|to_array:$galleyId}');">{translate key="rt.viewMetadata"}</a></li>{/if}
		{if $conferenceRt->getSupplementaryFiles() && $paper->getSuppFiles()}<li><a href="javascript:openRTWindow('{url page="rt" op="suppFiles" path=$paperId|to_array:$galleyId}');">{translate key="rt.suppFiles"}</a></li>{/if}
		{if $conferenceRt->getPrinterFriendly()}<li><a href="{if !$galley || $galley->isHtmlGalley()}javascript:openRTWindow('{url page="rt" op="printerFriendly" path=$paperId|to_array:$galleyId}');{else}{url page="paper" op="download" path=$paperId|to_array:$galley->getFileId()}{/if}">{translate key="rt.printVersion"}</a></li>{/if}
		{if $conferenceRt->getDefineTerms() && $version}
			{foreach from=$version->getContexts() item=context}
				{if $context->getDefineTerms()}
					<li><a href="javascript:openRTWindowWithToolbar('{url page="rt" op="context" path=$paperId|to_array:$galleyId:$context->getContextId()}');">{$context->getTitle()|escape}</a></li>
				{/if}
			{/foreach}
		{/if}
		{if $conferenceRt->getEmailOthers()}
			<li>
				{if $isUserLoggedIn}
					<a href="javascript:openRTWindow('{url page="rt" op="emailColleague" path=$paperId|to_array:$galleyId}');">{translate key="rt.colleague"}</a>
				{else}
					{translate key="rt.colleague"}*
					{assign var=needsLoginNote value=1}
				{/if}
			</li>
		{/if}
		{if $conferenceRt->getEmailPresenter()}
			<li>
				{if $isUserLoggedIn}
					<a href="javascript:openRTWindow('{url page="rt" op="emailPresenter" path=$paperId|to_array:$galleyId}');">{translate key="rt.emailPresenter"}</a>
				{else}
					{translate key="rt.emailPresenter"}*
					{assign var=needsLoginNote value=1}
				{/if}
			</li>
		{/if}
		{if $conferenceRt->getAddComment() && $postingAllowed}
			<li><a href="{url page="comment" op="add" path=$paper->getPaperId()|to_array:$galleyId}" target="_parent">{translate key="rt.addComment"}</a></li>
		{elseif $commentsClosed}
			{translate key="rt.addComment"}†
			{assign var=needsCommentsNote value=1}
		{elseif !$postingDisabled}
			{translate key="rt.addComment"}*
			{assign var=needsLoginNote value=1}
		{/if}
	</ul>
</div>
<br />
{/if}

{if $version}
<div class="rtBlock">
	<span class="rtSubtitle">{translate key="rt.relatedItems"}</span>
	<ul>
		{foreach from=$version->getContexts() item=context}
			{if !$context->getDefineTerms()}
				<li><a href="javascript:openRTWindowWithToolbar('{url page="rt" op="context" path=$paperId|to_array:$galleyId:$context->getContextId()}');">{$context->getTitle()|escape}</a></li>
			{/if}
		{/foreach}
	</ul>
</div>
{/if}

<br />

<div class="rtBlock">
	<span class="rtSubtitle">{translate key="rt.thisConference"}</span>
	<form method="post" action="{url page="search" op="results"}" target="_parent">
	<table>
	<tr>
		<td><input type="text" id="query" name="query" size="15" maxlength="255" value="" class="textField" /></td>
	</tr>
	<tr>
		<td><select name="searchField" size="1" class="selectMenu">
			{html_options_translate options=$paperSearchByOptions}
		</select></td>
	</tr>
	<tr>
		<td><input type="submit" value="{translate key="common.search"}" class="button" /></td>
	</tr>
	</table>
	</form>
</div>

<div class="rtSeparatorThin"></div>

{if $galley}
	{if $galley->isHtmlGalley()}
		<a href="{url op="viewPaper" path=$paperId|to_array:$galleyId}" target="_parent" class="rtAction">{translate key="common.close"}</a>
	{elseif $galley->isPdfGalley()}
		<a href="{url op="viewPDFInterstitial" path=$paperId|to_array:$galleyId}" target="_parent" class="rtAction">{translate key="common.close"}</a>
	{else}
		<a href="{url op="viewDownloadInterstitial" path=$paperId|to_array:$galleyId}" target="_parent" class="rtAction">{translate key="common.close"}</a>
	{/if}
{/if}

{if $needsCommentsNote}
<p><i style="font-size: 0.9em">{translate key="rt.comments.commentsClosed" closeCommentsDate=$closeCommentsDate|date_format:$dateFormatShort}</i></p>
{/if}

{if $needsLoginNote}
{url|assign:"loginUrl" page="user" op="account"}
<p><i style="font-size: 0.9em">{translate key="rt.email.needLogin" loginUrl=$loginUrl}</i></p>
{/if}

</div>

</div>

</body>

</html>
