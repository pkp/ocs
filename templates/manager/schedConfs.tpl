{**
 * schedConfs.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of scheduled conferences in site administration.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="schedConf.scheduledConferences"}
{include file="common/header.tpl"}
{/strip}
<script type="text/javascript">
{literal}
$(document).ready(function() { setupTableDND("#adminSchedConfs", "moveSchedConf"); });
{/literal}
</script>

<br />

<div id="schedConfs">
<table width="100%" class="listing" id="adminSchedConfs">
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	<tr valign="top" class="heading">
		<td width="50%">{translate key="manager.schedConfs.scheduledConference"}</td>
		<td width="20%">{translate key="manager.schedConfs.form.acronym"}</td>
		<td width="10%">{translate key="common.order"}</td>
		<td width="20%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	{iterate from=schedConfs item=schedConf}
	<tr valign="top" id="schedConf-{$schedConf->getId()}" class="data">
		<td><a class="action" href="{url schedConf=$schedConf->getPath() page="manager"}">{$schedConf->getSchedConfTitle()|escape}</a></td>
		<td class="drag">{$schedConf->getLocalizedSetting('acronym')|escape|default:"&mdash;"}</td>
		<td><a href="{url op="moveSchedConf" d=u id=$schedConf->getId()}">&uarr;</a> <a href="{url op="moveSchedConf" d=d id=$schedConf->getId()}">&darr;</a></td>
		<td align="right">
			<a href="{url op="editSchedConf" path=$conference->getId()|to_array:$schedConf->getId()}" class="action">{translate key="common.edit"}</a>
			&nbsp;|&nbsp;
			<a class="action" href="{url op="deleteSchedConf" path=$schedConf->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="manager.schedConfs.confirmDelete"}')">
				{translate key="common.delete"}
			</a>
		</td>
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
			<td colspan="4" class="endseparator">&nbsp;</td>
		</tr>
		<tr>
			<td colspan="2" align="left">{page_info iterator=$schedConfs}</td>
			<td colspan="2" align="right">{page_links anchor="schedConfs" name="schedConfs" iterator=$schedConfs}</td>
		</tr>
	{/if}
</table>

<p><a href="{url op="createSchedConf"}" class="action">{translate key="manager.schedConfs.create"}</a></p>
</div>
{include file="common/footer.tpl"}
