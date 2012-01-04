{**
 * specialEventForm.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Special event form under Scheduler.
 *
 * $Id$
 *}
{strip}
{assign var="pageCrumbTitle" value="$specialEventTitle"}
{if $specialEventId}
	{url|assign:"specialEventUrl" op="editSpecialEvent" path=$specialEventId escape=false}
	{assign var="pageTitle" value="manager.scheduler.specialEvent.editSpecialEvent"}
{else}
	{url|assign:"specialEventUrl" op="createSpecialEvent" escape=false}
	{assign var="pageTitle" value="manager.scheduler.specialEvent.createSpecialEvent"}
{/if}
{assign var="pageId" value="manager.scheduler.specialEvent.specialEventForm"}
{include file="common/header.tpl"}
{/strip}

<br/>

<form name="specialEvent" method="post" action="{url op="updateSpecialEvent"}">
{if $specialEventId}
<input type="hidden" name="specialEventId" value="{$specialEventId|escape}" />
{/if}

{include file="common/formErrors.tpl"}

<table class="data" width="100%">
{if count($formLocales) > 1}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{form_language_chooser form="specialEvent" url=$specialEventUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
{/if}
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="name" required="true" key="manager.scheduler.specialEvent.name"}</td>
	<td width="80%" class="value"><input type="text" name="name[{$formLocale|escape}]" value="{$name[$formLocale]|escape}" size="40" id="name" maxlength="80" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="startTime" required="true" key="common.date"}</td>
	<td class="value" id="startTime">
		{html_select_date prefix="startTime" all_extra="class=\"selectMenu\"" time=$startTime|default:$defaultStartTime start_year=$firstYear end_year=$lastYear}
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="startTime" required="true" key="manager.scheduler.startTime"}</td>
	<td class="value">
		{html_select_time prefix="startTime" all_extra="class=\"selectMenu\"" display_seconds=false display_meridian=true use_24_hours=false time=$startTime|default:$defaultStartTime}
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="endTime" required="true" key="manager.scheduler.endTime"}</td>
	<td class="value" id="endTime">
		<input name="endTimeDay" value="1" type="hidden" />
		<input name="endTimeMonth" value="1" type="hidden" />
		<input name="endTimeYear" value="2008" type="hidden" />
		{html_select_time prefix="endTime" all_extra="class=\"selectMenu\"" display_seconds=false display_meridian=true use_24_hours=false time=$endTime|default:$defaultStartTime}
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="description" key="manager.scheduler.specialEvent.description"}</td>
	<td class="value">
		<textarea name="description[{$formLocale|escape}]" id="description" cols="40" rows="10" class="textArea">{$description[$formLocale]|escape}</textarea>
	</td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> {if not $specialEventId}<input type="submit" name="createAnother" value="{translate key="manager.scheduler.specialEvent.saveAndCreateAnother"}" class="button" /> {/if}<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="specialEvents"}'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
