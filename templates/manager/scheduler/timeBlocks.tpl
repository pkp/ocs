{**
 * templates/manager/scheduler/timeBlocks.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of time blocks in the Scheduler in conference management.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="manager.scheduler.timeBlocks"}
{assign var="pageId" value="manager.scheduler.timeBlocks"}
{/strip}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{url op="schedule"}">{translate key="manager.scheduler.schedule"}</a></li>
	<li class="current"><a href="{url op="timeBlocks"}">{$pageTitle|translate}</a></li>
</ul>

<br />

<a name="timeBlocks"></a>

<table width="100%" class="listing">
	<tr>
		<td colspan="3" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="30%">{translate key="common.date"}</td>
		<td width="55%">{translate key="common.time"}</td>
		<td width="15%">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="3" class="headseparator">&nbsp;</td>
	</tr>
{iterate from=timeBlocks item=timeBlock}
	{assign var="thisDate" value=$timeBlock->getStartTime()|date_format:$dateFormatShort}
	{if $lastDate}{* If this isn't the first row *}
		<tr>
			<td colspan="3" class="{if $lastDate != $thisDate}end{/if}separator">&nbsp;</td>
		</tr>
	{/if}
	<tr valign="top">
		<td>{$timeBlock->getStartTime()|date_format:$dateFormatShort}</td>
		<td>{$timeBlock->getStartTime()|date_format:$timeFormat}&nbsp;&ndash;&nbsp;{$timeBlock->getEndTime()|date_format:$timeFormat}</td>
		<td><a href="{url op="editTimeBlock" path=$timeBlock->getId()}" class="action">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a href="{url op="deleteTimeBlock" path=$timeBlock->getId()}" onclick="return confirm('{translate|escape:"jsparam" key="manager.scheduler.timeBlock.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
	</tr>
	{if $timeBlocks->eof()}
	<tr>
		<td colspan="3" class="endseparator">&nbsp;</td>
	</tr>
	{/if}
	{assign var="lastDate" value=$thisDate}
{/iterate}
{if $timeBlocks->wasEmpty()}
	<tr>
		<td colspan="3" class="nodata">{translate key="manager.scheduler.timeBlock.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="3" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="1" align="left">{page_info iterator=$timeBlocks}</td>
		<td colspan="2" align="right">{page_links anchor="timeBlocks" name="timeBlocks" iterator=$timeBlocks}</td>
	</tr>
{/if}
</table>

<a href="{url op="createTimeBlock"}" class="action">{translate key="manager.scheduler.timeBlock.create"}</a>

{include file="common/footer.tpl"}
