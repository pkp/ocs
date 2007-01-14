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

{* Display current events. *}
<h3>{translate key="conference.currentConferences"}</h3>
{if not $currentEvents->eof()}
	{iterate from=currentEvents item=event}
		<h4><a href="{url event=$event->getPath()}">{$event->getFullTitle()|escape}</a></h4>
		<p>{$event->getSetting('location')|nl2br}</p>
		<p>{$event->getSetting('startDate')|date_format:$dateFormatLong} &ndash; {$event->getSetting('endDate')|date_format:$dateFormatLong}</p>
		{if $event->getSetting('eventIntroduction')}
			<p>{$event->getSetting('eventIntroduction')|nl2br}</p>
		{/if}
		<p><a href="{url event=$event->getPath()}" class="action">{translate key="site.eventView"}</a> | <a href="{url event=$event->getPath() page="user" op="register"}" class="action">{translate key="site.conferenceRegister"}</a></p>
	{/iterate}
{else}
	{translate key="conference.noCurrentConferences"}
{/if}


{include file="common/footer.tpl"}
