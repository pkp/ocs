{**
 * accommodationSettings.tpl
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to edit scheduled conference accommodation settings.
 *
 * $Id$
 *}
{assign var="pageTitle" value="schedConf.accommodation"}
{include file="common/header.tpl"}

{include file="common/formErrors.tpl"}

<form method="post" action="{url op="saveAccommodationSettings"}" enctype="multipart/form-data">

<p>{translate key="manager.accommodation.form.description"}</p>

<h4>{translate key="manager.accommodation.form.accommodationFiles"}</h4>
<p>{translate key="manager.accommodation.form.accommodationFiles.description"}</p>

<table class="data" width="100%">
{if count($formLocales) > 1}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"setupFormUrl" op="schedConfSetup" path="1"}
			{form_language_chooser form="setupForm" url=$setupFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
{/if}

{foreach from=$accommodationFiles[$formLocale] key=accommodationFileKey item=accommodationFile}
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="accommodationFileTitle-$accommodationFileKey" key="common.title" suppressId="true"}</td>
	<td width="80%" class="value">{$accommodationFile.title|escape}</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="accommodationFile" key="common.file"}</td>
	<td class="value">
		{translate key="common.fileName"}: <a href="{$publicSchedConfFilesDir}/{$accommodationFile.uploadName}" class="file">{$accommodationFile.name|escape}</a> {$accommodationFile.dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deleteAccommodationFile-{$formLocale|escape}-{$accommodationFileKey|escape}" value="{translate key="common.delete"}" class="button" />
	</td>
</tr>
{/foreach}{* accommodationFiles *}

<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="accommodationFileTitle" key="common.title"}</td>
	<td width="80%" class="value"><input type="text" id="accommodationFileTitle" name="accommodationFileTitle" value="{$accommodationFileTitle|escape}" size="40" maxlength="120" class="textField" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="accommodationFile" key="common.file"}</td>
	<td width="80%">
		<input type="file" name="accommodationFile" class="uploadField" /> <input type="submit" name="uploadAccommodationFile" value="{translate key="common.upload"}" class="button" />
	</td>
</tr>

</table>

<h4>{translate key="manager.accommodation.form.accommodationText"}</h4>
<p>{translate key="manager.accommodation.form.accommodationText.description"}</p>

<textarea name="accommodationDescription[{$formLocale|escape}]" id="accommodationDescription" rows="5" cols="60" class="textArea">{$accommodationDescription[$formLocale]|escape}</textarea>

<div class="separator"></div>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="manager"}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
