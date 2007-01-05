{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Conference index page. Displayed when a conference, but not an event,
 * has been selected.
 *
 * $Id$
 *}

{assign var="pageTitleTranslated" value=$conferenceTitle}
{include file="common/header.tpl"}

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

<div>{$conferenceOverview|nl2br}</div>

{if $homepageImage}
<div align="center"><img src="{$publicFilesDir}/{$homepageImage.uploadName|escape}" width="{$homepageImage.width}" height="{$homepageImage.height}" border="0" alt="" /></div>
{/if}

<br /><br />

{$additionalHomeContent}

{if $event}
	{* Display the table of contents or cover page of the current issue. *}
	<h3>{$event->getName()|escape}</h3>
	{include file="event/view.tpl"}
{else}
	<center><h3>{translate key="event.scheduledConferences"}</h3></center>
	<div class="separator"></div>
	{if not $events->eof()}
		{iterate from=events item=event}
			<h3><a href="{url event=$event->getPath()}">{$event->getFullTitle()|escape}</a></h3>
			{if $event->getSetting('eventIntroduction')}
				<p>{$event->getSetting('eventIntroduction')|nl2br}</p>
			{/if}
		{/iterate}
	{else}
		<center align="center">{translate key="event.noScheduledConferences"}</center>
	{/if}
	<div class="separator"></div>
{/if}

{if $isAcceptingSubmissions}
{/if}

{include file="common/footer.tpl"}
