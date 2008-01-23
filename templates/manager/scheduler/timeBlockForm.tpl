{**
 * timeBlockForm.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Time block form under Scheduler.
 *
 * $Id$
 *}
{assign var="pageCrumbTitle" value="$timeBlockTitle"}
{if $timeBlockId}
{url|assign:"timeBlockUrl" op="editTimeBlock" path=$timeBlockId}
{assign var="pageTitle" value="manager.scheduler.timeBlock.editTimeBlock"}
{else}
{url|assign:"timeBlockUrl" op="createTimeBlock"}
{assign var="pageTitle" value="manager.scheduler.timeBlock.createTimeBlock"}
{/if}
{assign var="pageId" value="manager.scheduler.timeBlock.timeBlockForm"}
{include file="common/header.tpl"}

<br/>

<form name="timeBlock" method="post" action="{url op="updateTimeBlock"}">
{if $timeBlockId}
<input type="hidden" name="timeBlockId" value="{$timeBlockId|escape}" />
{/if}

{include file="common/formErrors.tpl"}

<table class="data" width="100%">
{if count($formLocales) > 1}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{form_language_chooser form="timeBlock" url=$timeBlockUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
{/if}
<tr valign="top">
	<td class="label">{translate key="manager.timeline.schedConfStartsOn"}</td>
	<td class="value">{$schedConfStartDate|date_format:$dateFormatShort}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="manager.timeline.schedConfEndsOn"}</td>
	<td class="value">{$schedConfEndDate|date_format:$dateFormatShort}</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="name" required="true" key="manager.scheduler.timeBlock.name"}</td>
	<td class="value"><input type="text" name="name[{$formLocale|escape}]" value="{$name[$formLocale]|escape}" size="40" id="name" maxlength="80" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel key="common.date"}</td>
	<td class="value">
		{html_select_date prefix="startTime" all_extra="selectMenu" time=$startTime}
	</td>
<tr valign="top">
	<td class="label">{fieldLabel key="manager.scheduler.timeBlock.startTime"}</td>
	<td class="value" width="30%">
		{html_select_time prefix="startTime" use_24_hours=false minute_interval=5 display_seconds=false all_extra="class=\"selectMenu\"" time=$startTime}
	</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="manager.scheduler.timeBlock.endTime"}</td>
	<td class="value">
		{html_select_time prefix="endTime" use_24_hours=false minute_interval=5 display_seconds=false all_extra="class=\"selectMenu\"" time=$endTime}
	</td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> {if not $timeBlockId}<input type="submit" name="createAnother" value="{translate key="manager.scheduler.timeBlock.saveAndCreateAnother"}" class="button" /> {/if}<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="timeBlocks" escape=false}'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
