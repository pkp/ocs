{**
 * announcements.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of announcements in conference management.
 *
 * $Id$
 *}

{assign var="pageTitle" value="director.announcements"}
{assign var="pageId" value="director.announcements"}
{include file="common/header.tpl"}

<ul class="menu">
	<li class="current"><a href="{url op="announcements"}">{translate key="director.announcements"}</a></li>
	<li><a href="{url op="announcementTypes"}">{translate key="director.announcementTypes"}</a></li>
</ul>

<br />

</table>
<table width="100%" class="listing">
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="10%">{translate key="director.announcements.dateExpire"}</td>
		<td width="10%">{translate key="director.announcements.type"}</td>
		<td width="10%">{translate key="director.announcements.event"}</td>
		<td width="55%">{translate key="director.announcements.title"}</td>
		<td width="15%">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=announcements item=announcement}
	{assign var=eventId value=$announcement->getEventId()}
	<tr valign="top">
		<td>{$announcement->getDateExpire()|date_format:$dateFormatShort}</td>
		<td>{$announcement->getTypeName()}</td>
		<td>{$eventNames[$eventId]}</td>
		<td>{$announcement->getTitle()|escape}</td>
		<td><a href="{url op="editAnnouncement" path=$announcement->getAnnouncementId()}" class="action">{translate key="common.edit"}</a> <a href="{url op="deleteAnnouncement" path=$announcement->getAnnouncementId()}" onclick="return confirm('{translate|escape:"javascript" key="director.announcements.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
	</tr>
	<tr>
		<td colspan="5" class="{if $announcements->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $announcements->wasEmpty()}
	<tr>
		<td colspan="5" class="nodata">{translate key="director.announcements.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="5" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="3" align="left">{page_info iterator=$announcements}</td>
		<td colspan="2" align="right">{page_links name="announcements" iterator=$announcements}</td>
	</tr>
{/if}
</table>

<a href="{url op="createAnnouncement"}" class="action">{translate key="director.announcements.create"}</a>

{include file="common/footer.tpl"}
