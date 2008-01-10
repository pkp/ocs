{**
 * createTimeBlocksForm.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to populate initial set of time blocks under Scheduler.
 *
 * $Id$
 *}
{assign var="pageTitle" value="manager.scheduler.createTimeBlocks"}
{assign var="pageId" value="manager.scheduler.createTimeBlocks"}
{include file="common/header.tpl"}

<form name="createTimeBlocks" method="post" action="{url op="createTimeBlocks" path="execute"}">

{include file="common/formErrors.tpl"}

<p>{translate key="manager.scheduler.timeBlocks.description"}</p>

<table class="data" width="100%">
{if count($formLocales) > 1}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"createTimeBlocksUrl" op="createTimeBlocks"}
			{form_language_chooser form="createTimeBlocks" url=$createTimeBlocksUrl}
			{* Repeat all localized values for time block titles. *}
			{foreach from=$formLocales key=locale item=localeName}
				{if $locale != $formLocale}{foreach from=$name key=nameKey item=localeNames}
					{foreach from=$localeNames key=thisLocale item=name}
						<input type="hidden" name="name[{$nameKey|escape}][{$thisLocale|escape}]" value="{$name|escape}" />
					{/foreach}
				{/foreach}{/if}
			{/foreach}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
{/if}
	<tr valign="top">
		<td width="20%">{translate key="search.dateFrom"}</td>
		<td width="80%">{html_select_date prefix="startDate" time=$startDate all_extra="class=\"selectMenu\"" start_year=$startDate|date_format:"%Y" end_year=$startDate|date_format:"%Y"}</td>
	</tr>
	<tr valign="top">
		<td>{translate key="search.dateTo"}</td>
		<td>{html_select_date prefix="endDate" time=$endDate all_extra="class=\"selectMenu\"" start_year=$endDate|date_format:"%Y" end_year=$endDate|date_format:"%Y"}</td>
	</tr>
</table>

<h3>{translate key="manager.scheduler.timeBlock.createNew"}</h3>

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="newBlockName" key="manager.scheduler.timeBlock.name"}</td>
	<td width="80%" class="data"><input size="40" type="text" class="textField" name="newBlockName[{$formLocale|escape}]" id="newBlockName" value="{$newBlockName[$formLocale]|escape}" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel key="manager.scheduler.timeBlock.startTime"}</td>
	<td class="data">
		{html_select_time prefix="newBlockStart" use_24_hours=false minute_interval=5 display_seconds=false all_extra="class=\"selectMenu\"" time=$newBlockStart}
	</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="manager.scheduler.timeBlock.duration"}</td>
	<td class="data">
		{html_select_time prefix="newBlockDuration" use_24_hours=true display_meridian=false minute_interval=5 display_seconds=false all_extra="class=\"selectMenu\"" time=$newBlockDuration}
	</td>
</tr>
</table>

<p><input type="submit" name="createTimeBlock" value="{translate key="common.create"}" class="button" /></p>

<div class="separator"></div>

<h3>{translate key="manager.scheduler.timeBlocks"}</h3>

<table class="listing" width="100%">
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	<tr valign="top" class="heading">
		<td width="45%">{translate key="manager.scheduler.timeBlock.name"}</td>
		<td width="20%">{translate key="manager.scheduler.timeBlock.startTime"}</td>
		<td width="20%">{translate key="manager.scheduler.timeBlock.duration"}</td>
		<td width="15%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
{foreach name=blocks from=$timeBlocks key=blockIndex item=block}
<input type="hidden" name="timeBlockIndexes[]" value="{$blockIndex|escape}" />
<tr valign="top">
	<td>
		<input size="40" type="text" class="textField" name="name[{$blockIndex|escape}][{$formLocale|escape}]" id="name-{$blockIndex|escape}-{$formLocale|escape}" value="{$block.name.$formLocale}" />
	</td>
	<td>
		{html_select_time prefix="start-$blockIndex-" use_24_hours=false minute_interval=5 display_seconds=false all_extra="class=\"selectMenu\"" time=$block.start}
	</td>
	<td>
		{html_select_time prefix="duration-$blockIndex-" use_24_hours=true display_meridian=false minute_interval=5 display_seconds=false all_extra="class=\"selectMenu\"" time=$block.duration}
	</td>
	<td align="right">
		<a href="javascript:document.createTimeBlocks.action='{url path="deleteTimeBlock" blockIndex=$blockIndex escape=false}';document.createTimeBlocks.submit()" class="action">{translate key="common.delete"}</a>
	</td>
</tr>
<tr>
	<td colspan="5" class="{if $smarty.foreach.blocks.last}end{/if}separator">&nbsp;</td>
</tr>
{foreachelse}
<tr>
	<td colspan="5" class="nodata">{translate key="manager.scheduler.timeBlock.noneCreated"}</td>
</tr>
<tr>
	<td colspan="5" class="endseparator">&nbsp;</td>
<tr>
{/foreach}
</table>

<p><input type="submit" value="{translate key="common.done"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="scheduler" escape=false}'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
