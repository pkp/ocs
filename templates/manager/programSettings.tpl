{**
 * programSettings.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to edit scheduled conference program settings.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="schedConf.program"}
{include file="common/header.tpl"}
{/strip}

{include file="common/formErrors.tpl"}

<form name="programForm" method="post" action="{url op="saveProgramSettings"}" enctype="multipart/form-data">

<div id="programForm">
<p>{translate key="manager.program.form.description"}</p>

<br />

{if count($formLocales) > 1}
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"programFormUrl" op="program" escape=false}
			{form_language_chooser form="programForm" url=$programFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
</table>
{/if}

<h4>{translate key="manager.program.form.programFile"}</h4>
<p>{translate key="manager.program.form.programFile.description"}</p>

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="programFileTitle" key="common.title"}</td>
	<td width="80%" class="value"><input type="text" id="programFileTitle" name="programFileTitle[{$formLocale|escape}]" value="{$programFileTitle[$formLocale]|escape}" size="40" maxlength="120" class="textField" /></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="programFile" key="common.file"}</td>
	<td width="80%">
		<input type="file" id="programFile" name="programFile" class="uploadField" /> <input type="submit" name="uploadProgramFile" value="{translate key="common.upload"}" class="button" />
		{if $programFile[$formLocale]}
			<br/>
			{translate key="common.fileName"}: <a href="{$publicSchedConfFilesDir}/{$programFile[$formLocale].uploadName}" target="_new" class="file">{$programFile[$formLocale].name|escape}</a> {$programFile[$formLocale].dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deleteProgramFile" value="{translate key="common.delete"}" class="button" />
		{/if}
	</td>
</tr>
</table>
</div>

<div id="programText">
<h4>{translate key="manager.program.form.programText"}</h4>
<p>{translate key="manager.program.form.programText.description"}</p>

<textarea name="program[{$formLocale|escape}]" id="program" rows="5" cols="60" class="textArea">{$program[$formLocale]|escape}</textarea>

<div class="separator"></div>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="manager"}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
</form>

{include file="common/footer.tpl"}
