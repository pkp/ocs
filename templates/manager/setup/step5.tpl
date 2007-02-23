{**
 * step5.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 5 of conference setup.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.setup.customizingTheLook}
{include file="manager/setup/setupHeader.tpl"}

<form method="post" action="{url op="saveSetup" path="5"}" enctype="multipart/form-data">
{include file="common/formErrors.tpl"}

<h3>5.6 {translate key="manager.setup.conferenceStyleSheet"}</h3>

<p>{translate key="manager.setup.conferenceStyleSheetDescription"}</p>

<table width="100%" class="data">
<tr>
	<td width="20%" class="label">{translate key="manager.setup.useConferenceStyleSheet"}</td>
	<td width="80%" class="value"><input type="file" name="conferenceStyleSheet" class="uploadField" /> <input type="submit" name="uploadConferenceStyleSheet" value="{translate key="common.upload"}" class="button" /></td>
</tr>
</table>

{if $conferenceStyleSheet}
{translate key="common.fileName"}: <a href="{$publicConferenceFilesDir}/{$conferenceStyleSheet.uploadName}" class="file">{$conferenceStyleSheet.name}</a> {$conferenceStyleSheet.dateUploaded|date_format:$datetimeFormatShort} <input type="submit" name="deleteConferenceStyleSheet" value="{translate key="common.delete"}" class="button" />
{/if}

<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="setup" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
