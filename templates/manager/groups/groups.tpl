{**
 * groups.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of groups in conference management.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="manager.groups"}
{assign var="pageId" value="manager.groups"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
{literal}
$(document).ready(function() { setupTableDND("#dragTable", "moveGroup"); });
{/literal}
</script>

<br/>

<form action="{url op="setBoardEnabled"}" method="post">
	{url|assign:"aboutOrganizingTeamUrl" page="about" op="organizingTeam"}
	{url|assign:"peopleManagementUrl" page="manager" op="people" path="all"}
	{translate key="manager.groups.enableBoard.description" aboutOrganizingTeamUrl=$aboutOrganizingTeamUrl}<br/>
	<input type="radio" id="boardEnabledOff" {if !$boardEnabled}checked="checked" {/if}name="boardEnabled" value="0"/>&nbsp;<label for="boardEnabledOff">{translate key="manager.groups.disableBoard"}</label><br/>
	<input type="radio" id="boardEnabledOn" {if $boardEnabled}checked="checked" {/if}name="boardEnabled" value="1"/>&nbsp;<label for="boardEnabledOn">{translate key="manager.groups.enableBoard"}</label><br/>
	<input type="submit" value="{translate key="common.record"}" class="button defaultButton"/>
</form>

<br />

<div id="groups">
<table width="100%" id="dragTable" class="listing">
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="75%">{translate key="manager.groups.title"}</td>
		<td width="25%">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=groups item=group}
	<tr class="data" valign="top" id="group-{$group->getId()}">
		<td class="drag">
			{url|assign:"url" page="manager" op="email" toGroup=$group->getId()}
			{$group->getLocalizedTitle()|escape}&nbsp;{icon name="mail" url=$url}
		</td>
		<td>
			<a href="{url op="editGroup" path=$group->getId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="groupMembership" path=$group->getId() clearPageContext=1}" class="action">{translate key="manager.groups.membership"}</a>&nbsp;|&nbsp;<a href="{url op="deleteGroup" path=$group->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="manager.groups.confirmDelete"}')" class="action">{translate key="common.delete"}</a>&nbsp;|&nbsp;<a href="{url op="moveGroup" d=u id=$group->getId()}">&uarr;</a>&nbsp;<a href="{url op="moveGroup" d=d id=$group->getId()}">&darr;</a>
		</td>
	</tr>
{/iterate}
	<tr>
		<td colspan="2" class="endseparator">&nbsp;</td>
	</tr>
{if $groups->wasEmpty()}
	<tr>
		<td colspan="2" class="nodata">{translate key="manager.groups.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="2" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td align="left">{page_info iterator=$groups}</td>
		<td align="right">{page_links anchor="groups" name="groups" iterator=$groups}</td>
	</tr>
{/if}
</table>

<a href="{url op="createGroup"}" class="action">{translate key="manager.groups.create"}</a>
</div>
{include file="common/footer.tpl"}
