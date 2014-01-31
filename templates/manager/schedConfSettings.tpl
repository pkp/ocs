{**
 * schedConfSettings.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Basic scheduled conference settings under site administration.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="manager.schedConfs.schedulingAConference"}
{include file="common/header.tpl"}
{/strip}

<br />

<form name="schedConf" method="post" action="{url op="updateSchedConf"}">
{if $schedConfId}
<input type="hidden" name="schedConfId" value="{$schedConfId|escape}" />
{/if}
<input type="hidden" name="conferenceId" value="{$conferenceId|escape}" />

{include file="common/formErrors.tpl"}

{if not $schedConfId}
<p><span class="instruct">{translate key="manager.schedConfs.form.createInstructions"}</span></p>
{/if}

<table class="data" width="100%">
	{if count($formLocales) > 1}
		<tr valign="top">
			<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
			<td width="80%" class="value">
				{if $schedConfId}
					{url|assign:"settingsUrl" op="editSchedConf" path=$conferenceId|to_array:$schedConfId escape=false}
				{else}
					{url|assign:"settingsUrl" op="createSchedConf" escape=false}
				{/if}
				{form_language_chooser form="schedConf" url=$settingsUrl}
				<span class="instruct">{translate key="form.formLanguage.description"}</span>
			</td>
		</tr>
	{/if}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="title" key="manager.schedConfs.form.title" required="true"}</td>
		<td width="80%" class="value"><input type="text" id="title" name="title[{$formLocale|escape}]" value="{$title[$formLocale]|escape}" size="40" maxlength="120" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="acronym" required="true" key="manager.schedConfs.form.acronym"}</td>
		<td width="80%" class="value">
			<input type="text" name="acronym[{$formLocale|escape}]" id="acronym" value="{$acronym[$formLocale]|escape}" size="8" maxlength="16" class="textField" />
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="schedConfPath" key="common.path" required="true"}</td>
		<td class="value">
			<input type="text" id="schedConfPath" name="schedConfPath" value="{$schedConfPath|escape}" size="16" maxlength="32" class="textField" />
			<br />
			{translate|assign:"sampleEllipsis" key="common.ellipsis"}
			{url|assign:"sampleUrl" schedConf="path" page="$sampleEllipsis"}
			<span class="instruct">{translate key="manager.schedConfs.form.urlWillBe" sampleUrl=$sampleUrl}</span>
		</td>
	</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="schedConfs"}'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
