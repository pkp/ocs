{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Event index page. Displayed when both a conference and an event
 * have been specified.
 *
 * $Id$
 *}

{*
 * The page and crumb titles differ here since the breadcrumbs already include
 * the conference title, but the page title doesn't.
 *}
 
{assign var="pageCrumbTitleTranslated" value=$event->getTitle()}
{assign var="pageTitleTranslated" value=$event->getFullTitle()}
{include file="common/header.tpl"}

<div>{$event->getSetting("eventIntroduction")|nl2br}</div>

<br />

<h2>{$event->getSetting("location")|nl2br}</h2>

{if $enableAnnouncementsHomepage}
	{* Display announcements *}
	<br />
	<center><h3>{translate key="announcement.announcementsHome"}</h3></center>
	{include file="announcement/list.tpl"}	
	<table width="100%">
		<tr>
			<td>&nbsp;</td>
		<tr>
			<td align="right"><a href="{url page="announcement"}">{translate key="announcement.moreAnnouncements"}</a></td>
		</tr>
	</table>
{/if}

<br />

{if $homepageImage}
<div align="center"><img src="{$publicFilesDir}/{$homepageImage.uploadName|escape}" width="{$homepageImage.width}" height="{$homepageImage.height}" border="0" alt="" /></div>
{/if}

<h3>{translate key="event.contents"}</h3>

<ul class="plain">
	<li>&#187; <a href="{url page="event" op="overview"}">{translate key="event.overview"}</a></li>
	<li>&#187; <a href="{url page="event" op="cfp"}">{translate key="event.cfp"}</a> ({$submissionOpenDate|date_format:$dateFormatLong} - {$submissionCloseDate|date_format:$dateFormatLong})</li>
	{if $showSubmissionLink}<li>&#187; <a href="{url page="author" op="submit"}">{translate key="event.proposalSubmission"}</a></li>{/if}
	<li>&#187; <a href="{url page="event" op="program"}">{translate key="event.program"}</a></li>
	<li>&#187; <a href="{url page="event" op="proceedings"}">{translate key="event.proceedings"}</a></li>
{*	<li>&#187; <a href="{url page="event" op="submissions"}">{translate key="event.submissions"}</a></li>
	<li>&#187; <a href="{url page="event" op="papers"}">{translate key="event.papers"}</a></li>
	<li>&#187; <a href="{url page="event" op="discussion"}">{translate key="event.discussion"}</a></li>*}
	<li>&#187; <a href="{url page="event" op="registration"}">{translate key="event.registration"}</a></li>
	<li>&#187; <a href="{url page="event" op="supporters"}">{translate key="event.supporters"}</a></li>
{*	<li>&#187; <a href="{url page="event" op="schedule"}">{translate key="event.schedule"}</a></li> *}
	<li>&#187; <a href="{url page="event" op="links"}">{translate key="event.links"}</a></li>
</ul>

{$additionalHomeContent}

{include file="common/footer.tpl"}
