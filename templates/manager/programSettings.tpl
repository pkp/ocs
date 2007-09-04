{**
 * programSettings.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to edit scheduled conference program settings.
 *
 * $Id$
 *}
{assign var="pageTitle" value="schedConf.program"}
{include file="common/header.tpl"}

{include file="common/formErrors.tpl"}

<form method="post" action="{url op="saveProgramSettings"}" enctype="multipart/form-data">

<p>{translate key="manager.program.form.description"}</p>

<h4>{translate key="manager.program.form.programFile"}</h4>
<p>{translate key="manager.program.form.programFile.description"}</p>

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="programFileTitle" key="common.title"}</td>
	<td width="80%" class="value"><input type="text" id="programFileTitle" name="programFileTitle" value="{$programFileTitle|escape}" size="40" maxlength="120" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="programFile" key="common.file"}</td>
	<td class="value">
		<input type="file" name="programFile" class="uploadField" /> <input type="submit" name="uploadProgramFile" value="{translate key="common.upload"}" class="button" />
		{if $programFile}
			<br/>
			{translate key="common.fileName"}: <a href="{$publicSchedConfFilesDir}/{$programFile.uploadName}" class="file">{$programFile.name}</a> {$programFile.dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deleteProgramFile" value="{translate key="common.delete"}" class="button" />
		{/if}
	</td>
</tr>
</table>

<h4>{translate key="manager.program.form.programText"}</h4>
<p>{translate key="manager.program.form.programText.description"}</p>

<textarea name="program" id="program" rows="5" cols="60" class="textArea">{$program|escape}</textarea>

<div class="separator"></div>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="manager"}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
