{**
 * rooms.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of rooms in the Scheduler in conference management.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="manager.scheduler.rooms"}
{assign var="pageId" value="manager.scheduler.rooms"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li><a href="{url op="editBuilding" path=$buildingId}">{translate key="manager.scheduler.building.editBuilding"}</a></li>
	<li class="current"><a href="{url op="rooms" path=$buildingId}">{translate key="manager.scheduler.rooms"}</a></li>
</ul>

<br />

<div id="rooms">
<table width="100%" class="listing">
	<tr>
		<td colspan="3" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="70%">{translate key="manager.scheduler.room.name"}</td>
		<td width="15%">{translate key="manager.scheduler.room.abbrev"}</td>
		<td width="15%">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="3" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=rooms item=room}
	<tr valign="top">
		<td>{$room->getRoomName()|escape}</td>
		<td>{$room->getRoomAbbrev()|escape}</td>
		<td><a href="{url op="editRoom" path=$room->getBuildingId()|to_array:$room->getId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="deleteRoom" path=$room->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="manager.scheduler.room.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
	</tr>
	<tr>
		<td colspan="3" class="{if $rooms->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $rooms->wasEmpty()}
	<tr>
		<td colspan="3" class="nodata">{translate key="manager.scheduler.room.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="3" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td align="left">{page_info iterator=$rooms}</td>
		<td align="right" colspan="2">{page_links anchor="rooms" name="rooms" iterator=$rooms}</td>
	</tr>
{/if}
</table>

<a href="{url op="createRoom" path=$buildingId}" class="action">{translate key="manager.scheduler.room.create"}</a>
</div>
{include file="common/footer.tpl"}
