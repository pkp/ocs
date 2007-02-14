{**
 * schedConfs.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of scheduled conferences in site administration.
 *
 * $Id$
 *}

{assign var="pageTitle" value="schedConf.scheduledConferences"}
{include file="common/header.tpl"}

<br />

<table width="100%" class="listing">
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	<tr valign="top" class="heading">
		<td width="35%">{translate key="manager.schedConfs.scheduledConference"}</td>
		<td width="35%">{translate key="manager.schedConfs.schedConfStartDate"}</td>
		<td width="10%">{translate key="common.order"}</td>
		<td width="20%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	{iterate from=schedConfs item=schedConf}
	<tr valign="top">
		<td><a class="action" href="{url schedConf=$schedConf->getPath() page="manager"}">{$schedConf->getTitle()|escape}</a></td>
		<td>{$schedConf->getStartDate()|date_format:$dateFormatShort}</td>
		<td><a href="{url op="moveSchedConf" d=u schedConfId=$schedConf->getSchedConfId()}">&uarr;</a> <a href="{url op="moveSchedConf" d=d schedConfId=$schedConf->getSchedConfId()}">&darr;</a></td>

		<td align="right">
			<a href="{url op="editSchedConf" path=$conference->getConferenceId()|to_array:$schedConf->getSchedConfId()}" class="action">
				{translate key="common.edit"}
			</a>
			&nbsp;|&nbsp;
			<a class="action" href="{url op="deleteSchedConf" path=$schedConf->getSchedConfId()}" onclick="return confirm('{translate|escape:"javascript" key="manager.schedConfs.confirmDelete"}')">
				{translate key="common.delete"}
			</a>
		</td>
	</tr>
	<tr>
		<td colspan="4" class="{if $smarty.foreach.schedConfs.last}end{/if}separator">&nbsp;</td>
	</tr>
	{/iterate}
	{if $schedConfs->wasEmpty()}
	<tr>
		<td colspan="4" class="nodata">{translate key="manager.schedConfs.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="4" class="endseparator">&nbsp;</td>
	<tr>
	{else}
		<tr>
			<td colspan="2" align="left">{page_info iterator=$schedConfs}</td>
			<td colspan="2" align="right">{page_links name="schedConfs" iterator=$schedConfs}</td>
		</tr>
	{/if}
</table>

<p><a href="{url op="createSchedConf"}" class="action">{translate key="manager.schedConfs.create"}</a></p>

{include file="common/footer.tpl"}
