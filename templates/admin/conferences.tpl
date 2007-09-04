{**
 * conferences.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of conferences in site administration.
 *
 * $Id$
 *}
{assign var="pageTitle" value="conference.conferences"}
{include file="common/header.tpl"}

<br />

<a name="conferences"></a>

<table width="100%" class="listing">
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
	<tr valign="top">
		<td><a class="action" href="{url conference=$conference->getPath() page="manager"}">{$conference->getTitle()|escape}</a></td>
		<td>{$conference->getPath()|escape}</td>
		<td><a href="{url op="moveConference" d=u conferenceId=$conference->getConferenceId()}">&uarr;</a> <a href="{url op="moveConference" d=d conferenceId=$conference->getConferenceId()}">&darr;</a></td>
		<td align="right"><a href="{url op="editConference" path=$conference->getConferenceId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a class="action" href="{url op="deleteConference" path=$conference->getConferenceId()}" onclick="return confirm('{translate|escape:"javascript" key="admin.conferences.confirmDelete"}')">{translate key="common.delete"}</a></td>
	</tr>
	<tr>
		<td colspan="4" class="{if $smarty.foreach.conferences.last}end{/if}separator">&nbsp;</td>
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
			<td colspan="2" align="left">{page_info iterator=$conferences}</td>
			<td colspan="2" align="right">{page_links anchor="conferences" name="conferences" iterator=$conferences}</td>
		</tr>
	{/if}
</table>

<p><a href="{url op="createConference"}" class="action">{translate key="admin.conferences.create"}</a> | <a href="{url op="importOCS1"}" class="action">{translate key="admin.conferences.importOCS1"}</a></p>

{include file="common/footer.tpl"}
