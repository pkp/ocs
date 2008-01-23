{**
 * buildings.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of buildings in the Scheduler in conference management.
 *
 * $Id$
 *}
{assign var="pageTitle" value="manager.scheduler.buildings"}
{assign var="pageId" value="manager.scheduler.buildings"}
{include file="common/header.tpl"}

<br />

<a name="buildings"></a>

<table width="100%" class="listing">
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="85%">{translate key="manager.scheduler.building.name"}</td>
		<td width="15%">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=buildings item=building}
	<tr valign="top">
		<td>{$building->getBuildingName()|escape}</td>
		<td><a href="{url op="editBuilding" path=$building->getBuildingId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="rooms" path=$building->getBuildingId()}" class="action">{translate key="manager.scheduler.rooms"}</a>&nbsp;|&nbsp;<a href="{url op="deleteBuilding" path=$building->getBuildingId()}" onclick="return confirm('{translate|escape:"jsparam" key="manager.scheduler.building.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
	</tr>
	<tr>
		<td colspan="2" class="{if $buildings->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $buildings->wasEmpty()}
	<tr>
		<td colspan="2" class="nodata">{translate key="manager.scheduler.building.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="2" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td align="left">{page_info iterator=$buildings}</td>
		<td align="right">{page_links anchor="buildings" name="buildings" iterator=$buildings}</td>
	</tr>
{/if}
</table>

<a href="{url op="createBuilding"}" class="action">{translate key="manager.scheduler.building.create"}</a>

{include file="common/footer.tpl"}
