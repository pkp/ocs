{**
 * announcementTypes.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of announcement types in conference management.
 *
 * $Id$
 *}

{assign var="pageTitle" value="director.announcementTypes"}
{assign var="pageId" value="director.announcementTypes"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{url op="announcements"}">{translate key="director.announcements"}</a></li>
	<li class="current"><a href="{url op="announcementTypes"}">{translate key="director.announcementTypes"}</a></li>
</ul>

<br />

</table>
<table width="100%" class="listing">
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="85%">{translate key="director.announcementTypes.typeName"}</td>
		<td width="15%">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=announcementTypes item=announcementType}
	<tr valign="top">
		<td>{$announcementType->getTypeName()|escape}</td>
		<td><a href="{url op="editAnnouncementType" path=$announcementType->getTypeId()}" class="action">{translate key="common.edit"}</a> <a href="{url op="deleteAnnouncementType" path=$announcementType->getTypeId()}" onclick="return confirm('{translate|escape:"javascript" key="director.announcementTypes.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
	</tr>
	<tr>
		<td colspan="2" class="{if $announcementTypes->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $announcementTypes->wasEmpty()}
	<tr>
		<td colspan="2" class="nodata">{translate key="director.announcementTypes.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="2" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$announcementTypes}</td>
		<td colspan="2" align="right">{page_links name="announcementTypes" iterator=$announcementTypes}</td>
	</tr>
{/if}
</table>

<a href="{url op="createAnnouncementType"}" class="action">{translate key="director.announcementTypes.create"}</a>

{include file="common/footer.tpl"}
