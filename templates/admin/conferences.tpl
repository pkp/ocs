{**
 * conferences.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of conferences in site administration.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="conference.conferences"}
{include file="common/header.tpl"}
{/strip}
<script type="text/javascript">
{literal}
$(document).ready(function() { setupTableDND("#adminConferences", "moveConference"); });
{/literal}
</script>

<br />

<div id="conferences">
<table width="100%" class="listing" id="adminConferences">
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	<tr valign="top" class="heading">
		<td width="35%">{translate key="manager.setup.layout.conferenceTitle"}</td>
		<td width="35%">{translate key="common.path"}</td>
		<td width="10%">{translate key="common.order"}</td>
		<td width="20%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	{iterate from=conferences item=conference}
	<tr valign="top" id="conference-{$conference->getId()}" class="data">
		<td class="drag"><a class="action" href="{url conference=$conference->getPath() page="manager"}">{$conference->getConferenceTitle()|escape}</a></td>
		<td class="drag">{$conference->getPath()|escape}</td>
		<td><a href="{url op="moveConference" d=u id=$conference->getId()}">&uarr;</a> <a href="{url op="moveConference" d=d id=$conference->getId()}">&darr;</a></td>
		<td align="right"><a href="{url op="editConference" path=$conference->getId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a class="action" href="{url op="deleteConference" path=$conference->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="admin.conferences.confirmDelete"}')">{translate key="common.delete"}</a></td>
	</tr>
	{/iterate}
	{if $conferences->wasEmpty()}
	<tr>
		<td colspan="4" class="nodata">{translate key="admin.conferences.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="4" class="endseparator">&nbsp;</td>
	<tr>
	{else}
		<tr>
			<td colspan="4" class="endseparator">&nbsp;</td>
		</tr>
		<tr>
			<td colspan="2" align="left">{page_info iterator=$conferences}</td>
			<td colspan="2" align="right">{page_links anchor="conferences" name="conferences" iterator=$conferences}</td>
		</tr>
	{/if}
</table>

<p><a href="{url op="createConference"}" class="action">{translate key="admin.conferences.create"}</a> | <a href="{url op="importOCS1"}" class="action">{translate key="admin.conferences.importOCS1"}</a></p>
</div>
{include file="common/footer.tpl"}
