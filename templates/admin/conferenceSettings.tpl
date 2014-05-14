{**
 * conferenceSettings.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Basic conference settings under site administration.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="admin.conferences.conferenceSettings"}
{include file="common/header.tpl"}
{/strip}

<br />
<div id="conferenceSettings">
<form name="conference" method="post" action="{url op="updateConference"}">
{if $conferenceId}
<input type="hidden" name="conferenceId" value="{$conferenceId|escape}" />
{/if}

{include file="common/formErrors.tpl"}

{if not $conferenceId}
<p><span class="instruct">{translate key="admin.conferences.createInstructions"}</span></p>
{/if}

<table class="data" width="100%">
	{if count($formLocales) > 1}
		<tr valign="top">
			<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
			<td width="80%" class="value">
				{url|assign:"settingsUrl" op="editConference" path=$conferenceId escape=false}
				{form_language_chooser form="conference" url=$settingsUrl}
				<span class="instruct">{translate key="form.formLanguage.description"}</span>
			</td>
		</tr>
	{/if}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="title" key="manager.setup.conferenceTitle" required="true"}</td>
		<td width="80%" class="value"><input type="text" id="title" name="title[{$formLocale|escape}]" value="{$title[$formLocale]|escape}" size="40" maxlength="120" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="description" key="admin.conferences.conferenceDescription"}</td>
		<td class="value">
			<textarea name="description[{$formLocale|escape}]" id="description" cols="40" rows="10" class="textArea">{$description[$formLocale]|escape}</textarea>
			<br />
			<span class="instruct">{translate key="admin.conferences.conferenceDescriptionInstructions" sampleUrl=$sampleUrl}</span>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="conferencePath" key="common.path" required="true"}</td>
		<td class="value">
			<input type="text" id="conferencePath" name="conferencePath" value="{$conferencePath|escape}" size="16" maxlength="32" class="textField" />
			<br />
			{translate|assign:"sampleEllipsis" key="common.ellipsis"}
			{url|assign:"sampleUrl" conference="path" schedConf="$sampleEllipsis"}
			<span class="instruct">{translate key="admin.conferences.urlWillBe" sampleUrl=$sampleUrl}</span>
		</td>
	</tr>
	<tr valign="top">
		<td colspan="2" class="label">
			<input type="checkbox" name="enabled" id="enabled" value="1"{if $enabled} checked="checked"{/if} /> <label for="enabled">{translate key="admin.conferences.enableConferenceInstructions"}</label>
		</td>
	</tr>
	<tr valign="top">
		<td colspan="2" class="label">
			<input type="checkbox" name="scheduleConf" id="scheduleConf" value="1"{if $scheduleConf} checked="checked"{/if} /> <label for="scheduleConf">{translate key="admin.conferences.scheduleConferenceInstructions"}</label>
		</td>
	</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="conferences"}'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
{include file="common/footer.tpl"}
