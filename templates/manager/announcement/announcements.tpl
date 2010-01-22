{**
 * announcements.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of announcements in conference management.
 *
 * $Id$
 *}
{assign var="pageTitle" value="manager.announcements"}
{assign var="pageId" value="manager.announcements"}
{include file="common/header.tpl"}

<ul class="menu">
	<li class="current"><a href="{url op="announcements" clearPageContext=1}">{translate key="manager.announcements"}</a></li>
	<li><a href="{url op="announcementTypes" clearPageContext=1}">{translate key="manager.announcementTypes"}</a></li>
</ul>

<br />

<a name="announcements"></a>

<table width="100%" class="listing">
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="10%">{translate key="manager.announcements.dateExpire"}</td>
		<td width="10%">{translate key="manager.announcements.type"}</td>
		<td width="10%">{translate key="manager.announcements.schedConf"}</td>
		<td width="55%">{translate key="manager.announcements.title"}</td>
		<td width="15%">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=announcements item=announcement}
	{assign var=schedConfId value=$announcement->getSchedConfId()}
	<tr valign="top">
		<td>{$announcement->getDateExpire()|date_format:$dateFormatShort}</td>
		<td>{$announcement->getAnnouncementTypeName()}</td>
		<td>{$schedConfNames[$schedConfId]}</td>
		<td>{$announcement->getAnnouncementTitle()|escape}</td>
		<td><a href="{url op="editAnnouncement" path=$announcement->getAnnouncementId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="deleteAnnouncement" path=$announcement->getAnnouncementId()}" onclick="return confirm('{translate|escape:"jsparam" key="manager.announcements.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
	</tr>
	<tr>
		<td colspan="5" class="{if $announcements->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $announcements->wasEmpty()}
	<tr>
		<td colspan="5" class="nodata">{translate key="manager.announcements.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="5" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="3" align="left">{page_info iterator=$announcements}</td>
		<td colspan="2" align="right">{page_links anchor="announcements" name="announcements" iterator=$announcements}</td>
	</tr>
{/if}
</table>

<a href="{url op="createAnnouncement"}" class="action">{translate key="manager.announcements.create"}</a>

{include file="common/footer.tpl"}
