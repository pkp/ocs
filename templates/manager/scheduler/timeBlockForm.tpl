{**
 * templates/manager/scheduler/timeBlockForm.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Time block form under Scheduler.
 *
 * $Id$
 *}
{strip}
{assign var="pageCrumbTitle" value="$timeBlockTitle"}
{if $timeBlockId}
	{url|assign:"timeBlockUrl" op="editTimeBlock" path=$timeBlockId}
	{assign var="pageTitle" value="manager.scheduler.timeBlock.editTimeBlock"}
{else}
	{url|assign:"timeBlockUrl" op="createTimeBlock"}
	{assign var="pageTitle" value="manager.scheduler.timeBlock.createTimeBlock"}
{/if}
{/strip}
{assign var="pageId" value="manager.scheduler.timeBlock.timeBlockForm"}
{include file="common/header.tpl"}

<br/>

<form name="timeBlock" method="post" action="{url op="updateTimeBlock"}">
{if $timeBlockId}
<input type="hidden" name="timeBlockId" value="{$timeBlockId|escape}" />
{/if}

{include file="common/formErrors.tpl"}

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{translate key="manager.timeline.schedConfStartsOn"}</td>
	<td width="80%" class="value">{$currentSchedConf->getSetting('startDate')|date_format:$dateFormatShort|default:"&mdash;"}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="manager.timeline.schedConfEndsOn"}</td>
	<td class="value">{$currentSchedConf->getSetting('endDate')|date_format:$dateFormatShort|default:"&mdash;"}</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel key="common.date"}</td>
	<td class="value">
		{html_select_date prefix="startTime" all_extra="selectMenu" time=$startTime|default:$schedConfStartDate start_year=$firstYear end_year=$lastYear}
	</td>
<tr valign="top">
	<td class="label">{fieldLabel key="manager.scheduler.timeBlock.startTime"}</td>
	<td class="value" width="30%">
		{html_select_time prefix="startTime" use_24_hours=false minute_interval=5 display_seconds=false all_extra="class=\"selectMenu\"" time=$startTime|default:"09:00"}
	</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="manager.scheduler.timeBlock.endTime"}</td>
	<td class="value">
		{html_select_time prefix="endTime" use_24_hours=false minute_interval=5 display_seconds=false all_extra="class=\"selectMenu\"" time=$endTime|default:"10:00"}
	</td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> {if not $timeBlockId}<input type="submit" name="createAnother" value="{translate key="manager.scheduler.timeBlock.saveAndCreateAnother"}" class="button" /> {/if}<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="timeBlocks"}'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
