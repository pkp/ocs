{**
 * buildings.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of buildings in the Scheduler in conference management.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="manager.scheduler.buildings"}
{assign var="pageId" value="manager.scheduler.buildings"}
{include file="common/header.tpl"}
{/strip}

<br />

<div id="buildings">
<table width="100%" class="listing">
	<tr>
		<td colspan="3" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="70%">{translate key="manager.scheduler.building.name"}</td>
		<td width="15%">{translate key="manager.scheduler.building.abbrev"}</td>
		<td width="15%">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="3" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=buildings item=building}
	<tr valign="top">
		<td>{$building->getBuildingName()|escape}</td>
		<td>{$building->getBuildingAbbrev()|escape}</td>
		<td><a href="{url op="editBuilding" path=$building->getId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="rooms" path=$building->getId()}" class="action">{translate key="manager.scheduler.rooms"}</a>&nbsp;|&nbsp;<a href="{url op="deleteBuilding" path=$building->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="manager.scheduler.building.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
	</tr>
	<tr>
		<td colspan="3" class="{if $buildings->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $buildings->wasEmpty()}
	<tr>
		<td colspan="3" class="nodata">{translate key="manager.scheduler.building.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="3" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td align="left">{page_info iterator=$buildings}</td>
		<td align="right" colspan="2">{page_links anchor="buildings" name="buildings" iterator=$buildings}</td>
	</tr>
{/if}
</table>

<a href="{url op="createBuilding"}" class="action">{translate key="manager.scheduler.building.create"}</a>
</div>
{include file="common/footer.tpl"}
