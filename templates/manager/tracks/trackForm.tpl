{**
 * trackForm.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to create/modify a conference track.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="track.track"}
{assign var="pageCrumbTitle" value="track.track"}
{include file="common/header.tpl"}
{/strip}

<form name="track" method="post" action="{url op="updateTrack"}" onsubmit="return saveSelectedDirectors()">
{if $trackId}
<input type="hidden" name="trackId" value="{$trackId|escape}" />
{/if}
<input type="hidden" name="assignedDirectors" value="" />
<input type="hidden" name="unassignedDirectors" value="" />

{literal}
<script type="text/javascript">
<!--
	// Move the currently selected item between two select menus
	function moveSelectItem(currField, newField) {
		var selectedIndex = currField.selectedIndex;
		
		if (selectedIndex == -1) {
			return;
		}
		
		var selectedOption = currField.options[selectedIndex];

		// If "None" exists in new menu, delete it.
		for (var i = 0; i < newField.options.length; i++) {
			if (newField.options[i].disabled) {
				// Delete item from old menu
				for (var j = i + 1; j < newField.options.length; j++) {
					newField.options[j - 1].value = newField.options[j].value;
					newField.options[j - 1].text = newField.options[j].text;
				}
				newField.options.length -= 1;
			}
		}

		// Add item to new menu
		newField.options.length += 1;
		newField.options[newField.options.length - 1] = new Option(selectedOption.text, selectedOption.value);

		// Delete item from old menu
		for (var i = selectedIndex + 1; i < currField.options.length; i++) {
			currField.options[i - 1].value = currField.options[i].value;
			currField.options[i - 1].text = currField.options[i].text;
		}
		currField.options.length -= 1;

		// If no items are left in the current menu, add a "None" item.
		if (currField.options.length == 0) {
			currField.options.length = 1;
			currField.options[0] = new Option('{/literal}{translate|escape:"quote" key="common.none"}{literal}', '');
			currField.options[0].disabled = true;
		}

		// Update selected item
		else if (currField.options.length > 0) {
			currField.selectedIndex = selectedIndex < (currField.options.length - 1) ? selectedIndex : (currField.options.length - 1);
		}
	}
	
	// Save IDs of selected directors in hidden field
	function saveSelectedDirectors() {
		var assigned = document.track.assigned;
		var assignedIds = '';
		for (var i = 0; i < assigned.options.length; i++) {
			if (assignedIds != '') {
				assignedIds += ':';
			}
			assignedIds += assigned.options[i].value;
		}
		document.track.assignedDirectors.value = assignedIds;
		
		var unassigned = document.track.unassigned;
		var unassignedIds = '';
		for (var i = 0; i < unassigned.options.length; i++) {
			if (unassignedIds != '') {
				unassignedIds += ':';
			}
			unassignedIds += unassigned.options[i].value;
		}
		document.track.unassignedDirectors.value = unassignedIds;
		
		return true;
	}
// -->
</script>
{/literal}

{include file="common/formErrors.tpl"}

<table class="data" width="100%">
{if count($formLocales) > 1}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{if $trackId}{url|assign:"trackFormUrl" op="editTrack" path=$trackId escape=false}
			{else}{url|assign:"trackFormUrl" op="createTrack" path=$trackId}
			{/if}
			{form_language_chooser form="track" url=$trackFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
{/if}
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="title" required="true" key="track.title"}</td>
	<td width="80%" class="value"><input type="text" name="title[{$formLocale|escape}]" value="{$title[$formLocale]|escape}" id="title" size="40" maxlength="120" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="abbrev" required="true" key="track.abbreviation"}</td>
	<td class="value"><input type="text" name="abbrev[{$formLocale|escape}]" id="abbrev" value="{$abbrev[$formLocale]|escape}" size="20" maxlength="20" class="textField" />&nbsp;&nbsp;{translate key="track.abbreviation.example"}</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="policy" key="manager.tracks.policy"}</td>
	<td class="value"><textarea name="policy[{$formLocale|escape}]" rows="4" cols="40" id="policy" class="textArea">{$policy[$formLocale]|escape}</textarea></td>
</tr>
<td class="label">{fieldLabel name="reviewFormId" key="submission.reviewForm"}</td>
<td class="value">
	<select name="reviewFormId" size="1" id="reviewFormId" class="selectMenu">
		<option value="">{translate key="manager.reviewForms.noneChosen"}</option>
		{html_options options=$reviewFormOptions selected=$reviewFormId}
	</select>
</td>
<tr valign="top">
	<td rowspan="2" class="label">{fieldLabel suppressId="true" key="submission.indexing"}</td>
	<td class="value">
		{fieldLabel name="identifyType" key="manager.tracks.identifyType"} <input type="text" name="identifyType[{$formLocale|escape}]" id="identifyType" value="{$identifyType[$formLocale]|escape}" size="20" maxlength="60" class="textField" />
		<br />
		<span class="instruct">{translate key="manager.tracks.identifyTypeExamples"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="value">
		<input type="checkbox" name="metaNotReviewed" id="metaNotReviewed" value="1" {if $metaNotReviewed}checked="checked"{/if} />
		{fieldLabel name="metaNotReviewed" key="manager.tracks.submissionNotReviewed"}
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel suppressId="true" key="submission.restrictions"}</td>
	<td class="value">
		<input type="checkbox" name="directorRestriction" id="directorRestriction" value="1" {if $directorRestriction}checked="checked"{/if} />
		{fieldLabel name="directorRestriction" key="manager.tracks.directorRestriction"}
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="hideAbout" key="navigation.about"}</td>
	<td class="value">
		<input type="checkbox" name="hideAbout" id="hideAbout" value="1" {if $hideAbout}checked="checked"{/if} />
		{fieldLabel name="hideAbout" key="manager.tracks.hideAbout"}
	</td>
</tr>
{if $commentsEnabled}
<tr valign="top">
	<td class="label">{fieldLabel name="disableComments" key="comments.readerComments"}</td>
	<td class="value">
		<input type="checkbox" name="disableComments" id="disableComments" value="1" {if $disableComments}checked="checked"{/if} />
		{fieldLabel name="disableComments" key="manager.tracks.disableComments"}
	</td>
</tr>
{/if}
<tr valign="top">
	<td rowspan="2" class="label">{fieldLabel key="manager.tracks.wordCount"}</td>
	<td class="value">
		{fieldLabel name="wordCount" key="manager.tracks.wordCountInstructions"}&nbsp;&nbsp;<input type="text" name="wordCount" id="abbrev" value="{$wordCount}" size="10" maxlength="20" class="textField" />
	</td>
</tr>
</table>
<div class="separator"></div>

<h3>{translate key="user.role.trackDirectors"}</h3>
<p><span class="instruct">{translate key="manager.tracks.trackDirectorInstructions"}</span></p>
<table class="data" width="100%">
<tr valign="top">
	<td width="20%">&nbsp;</td>
	<td>{translate key="manager.tracks.unassigned"}</td>
	<td>&nbsp;</td>
	<td>{translate key="manager.tracks.assigned"}</td>
</tr>
<tr valign="top">
	<td width="20%">&nbsp;</td>
	<td><select name="unassigned" size="15" style="width: 150px" class="selectMenu">
		{foreach from=$unassignedDirectors item=director}
			<option value="{$director->getId()}">{$director->getFullName()|escape}</option>
		{foreachelse}
			<option value="" disabled="disabled">{translate key="common.none"}</option>
		{/foreach}
	</select></td>
	<td><input type="button" value="{translate key="manager.tracks.assignDirector"} &gt;&gt;" onclick="moveSelectItem(this.form.unassigned, this.form.assigned)" class="button" />
		<br /><br />
		<input type="button" value="&lt;&lt; {translate key="manager.tracks.unassignDirector"}" onclick="moveSelectItem(this.form.assigned, this.form.unassigned)" class="button" /></td>
	<td><select name="assigned" size="15" style="width: 150px" class="selectMenu">
		{foreach from=$assignedDirectors item=director}
			<option value="{$director->getId()}">{$director->getFullName()|escape}</option>
		{foreachelse}
			<option value="" disabled="disabled">{translate key="common.none"}</option>
		{/foreach}
	</select></td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="tracks"}'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
{include file="common/footer.tpl"}
