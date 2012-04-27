{**
 * rt.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Reading Tools.
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
	<meta name="description" content="" />
	<meta name="keywords" content="" />

	<link rel="stylesheet" href="{$baseUrl}/lib/pkp/styles/common.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/paperView.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/lib/pkp/styles/rt.css" type="text/css" />

	{foreach from=$stylesheets item=cssUrl}
		<link rel="stylesheet" href="{$cssUrl}" type="text/css" />
	{/foreach}

	<script type="text/javascript" src="{$baseUrl}/lib/pkp/js/general.js"></script>
	{$additionalHeadData}
</head>
<body>

<div id="container">
<div id="main" style="width: 150px; font-size: 0.7em; padding-top: 1.5em; padding-left: 1em">

<h5>{$conference->getConferenceTitle()|escape}<br />{$schedConf->getSchedConfTitle()|escape}</h5>

<p><a href="{url page="schedConf" op="presentations"}" target="_parent" class="rtAction">{translate key="schedConf.presentations.short"}</a></p>

<h5>{translate key="rt.readingTools"}</h5>

<div class="rtSeparator"></div>

<h6>{$paper->getLocalizedTitle()|strip_tags|truncate:20:"...":true}</h6>
<p><em>{$paper->getAuthorString(true)|escape}</em></p>

<div class="rtSeparator"></div>

<br />

{if $conferenceRt->getEnabled()}
<div id="paperInfo" class="rtBlock">
	<ul>
		{if $conferenceRt->getAbstract() && $galley}<li><a href="{url page="paper" op="view" path=$paperId}" target="_parent">{translate key="paper.abstract"}</a></li>{/if}
		<li><a href="{url page="about" op="editorialPolicies" anchor="peerReviewProcess"}" target="_parent">{translate key="rt.reviewPolicy"}</a></li>
		{if $conferenceRt->getAuthorBio()}<li><a href="javascript:openRTWindow('{url page="rt" op="bio" path=$paperId|to_array:$galleyId}');">{translate key="rt.authorBio"}</a></li>{/if}
		{if $conferenceRt->getCaptureCite()}<li><a href="javascript:openRTWindow('{url page="rt" op="captureCite" path=$paperId|to_array:$galleyId}');">{translate key="rt.captureCite"}</a></li>{/if}
		{if $conferenceRt->getViewMetadata()}<li><a href="javascript:openRTWindow('{url page="rt" op="metadata" path=$paperId|to_array:$galleyId}');">{translate key="rt.viewMetadata"}</a></li>{/if}
		{if $conferenceRt->getSupplementaryFiles() && $paper->getSuppFiles()}<li><a href="javascript:openRTWindow('{url page="rt" op="suppFiles" path=$paperId|to_array:$galleyId}');">{translate key="rt.suppFiles"}</a></li>{/if}
		{if $conferenceRt->getPrinterFriendly()}<li><a href="{if !$galley || $galley->isHtmlGalley()}javascript:openRTWindow('{url page="rt" op="printerFriendly" path=$paperId|to_array:$galleyId}');{else}{url page="paper" op="download" path=$paperId|to_array:$galley->getId()}{/if}">{translate key="rt.printVersion"}</a></li>{/if}
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
		{if $conferenceRt->getEmailAuthor()}
			<li>
				{if $isUserLoggedIn}
					<a href="javascript:openRTWindow('{url page="rt" op="emailAuthor" path=$paperId|to_array:$galleyId}');">{translate key="rt.emailAuthor"}</a>
				{else}
					{translate key="rt.emailAuthor"}*
					{assign var=needsLoginNote value=1}
				{/if}
			</li>
		{/if}
		{assign var=rtAddComment value=$conferenceRt->getAddComment()}
		{if $rtAddComment && $postingAllowed && !$postingDisabled}
			<li><a href="{url page="comment" op="add" path=$paper->getId()|to_array:$galleyId}" target="_parent">{translate key="rt.addComment"}</a></li>
		{elseif $rtAddComment && $commentsClosed}
			{translate key="rt.addComment"}†
			{assign var=needsCommentsNote value=1}
		{elseif $rtAddComment && $postingDisabled}
			{translate key="rt.addComment"}*
			{assign var=needsLoginNote value=1}
		{/if}
		{if $conferenceRt->getFindingReferences()}
			<li><a href="javascript:openRTWindow('{url page="rt" op="findingReferences" path=$paper->getId()|to_array:$galleyId}');">{translate key="rt.findingReferences"}</a></li>
		{/if}
	</ul>
</div>
<br />
{/if}

{if $version}
<div id="relatedItems" class="rtBlock">
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

<div id="thisConference" class="rtBlock">
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
<p><em style="font-size: 0.9em">{translate key="rt.comments.commentsClosed" closeCommentsDate=$closeCommentsDate|date_format:$dateFormatShort}</em></p>
{/if}

{if $needsLoginNote}
{url|assign:"loginUrl" page="user" op="account"}
<p><em style="font-size: 0.9em">{translate key="rt.email.needLogin" loginUrl=$loginUrl}</em></p>
{/if}

</div>

</div>

</body>

</html>
