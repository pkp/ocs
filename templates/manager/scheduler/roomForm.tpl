{**
 * roomForm.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Room form under Scheduler.
 *
 * $Id$
 *}
{strip}
{assign var="pageCrumbTitle" value="$roomTitle"}
{if $roomId}
	{url|assign:"roomUrl" op="editRoom" path=$buildingId|to_array:$roomId escape=false}
	{assign var="pageTitle" value="manager.scheduler.room.editRoom"}
{else}
	{url|assign:"roomUrl" op="createRoom" path=$buildingId escape=false}
	{assign var="pageTitle" value="manager.scheduler.room.createRoom"}
{/if}
{assign var="pageId" value="manager.scheduler.room.roomForm"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li class="current"><a href="{$roomUrl}">{$pageTitle|translate}</a></li>
	{if $roomId}
		<li><a href="{url op="rooms" path=$roomId}">{translate key="manager.scheduler.rooms"}</a></li>
	{/if}
</ul>

<br/>

<form name="room" method="post" action="{url op="updateRoom"}">
{if $roomId}
<input type="hidden" name="roomId" value="{$roomId|escape}" />
{/if}
<input type="hidden" name="buildingId" value="{$buildingId|escape}" />

{include file="common/formErrors.tpl"}

<table class="data" width="100%">
{if count($formLocales) > 1}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{form_language_chooser form="room" url=$roomUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
{/if}
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="name" required="true" key="manager.scheduler.room.name"}</td>
	<td width="80%" class="value"><input type="text" name="name[{$formLocale|escape}]" value="{$name[$formLocale]|escape}" size="40" id="name" maxlength="80" class="textField" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="abbrev" required="true" key="manager.scheduler.room.abbrev"}</td>
	<td width="80%" class="value"><input type="text" name="abbrev[{$formLocale|escape}]" value="{$abbrev[$formLocale]|escape}" size="40" id="abbrev" maxlength="80" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="description" key="manager.scheduler.building.description"}</td>
	<td class="value">
		<textarea name="description[{$formLocale|escape}]" id="description" cols="40" rows="10" class="textArea">{$description[$formLocale]|escape}</textarea>
	</td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> {if not $roomId}<input type="submit" name="createAnother" value="{translate key="manager.scheduler.room.saveAndCreateAnother"}" class="button" /> {/if}<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="rooms" path=$buildingId}'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
