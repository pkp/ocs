{**
 * registrationOptionForm.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Registration option form under scheduled conference management.
 *
 * $Id$
 *}
{if $optionId}
{assign var="pageTitle" value="manager.registrationOptions.edit"}
{else}
{assign var="pageTitle" value="manager.registrationOptions.create"}
{/if}
{assign var="pageId" value="manager.registrationOptions.registrationOptionForm"}
{assign var="pageCrumbTitle" value=$registrationOptionTitle}
{include file="common/header.tpl"}

{if $registrationOptionCreated}
<br/>
{translate key="manager.registrationOptions.registrationOptionCreatedSuccessfully"}<br />
{/if}

<br/>

<form name="registrationOption" method="post" action="{url op="updateRegistrationOption"}">
{if $optionId}
<input type="hidden" name="optionId" value="{$optionId|escape}" />
{/if}

{include file="common/formErrors.tpl"}
<table class="data" width="100%">
{if count($formLocales) > 1}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{if $optionId}{url|assign:"registrationOptionUrl" op="editRegistrationOption" path=$optionId escape=false}
			{else}{url|assign:"registrationOptionUrl" op="createRegistrationOption" escape=false}
			{/if}
			{form_language_chooser form="registrationOption" url=$registrationOptionUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>

		</td>
	</tr>
{/if}
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="name" required="true" key="manager.registrationOptions.form.optionName"}</td>
	<td width="80%" class="value"><input type="text" name="name[{$formLocale|escape}]" value="{$name[$formLocale]|escape}" size="35" maxlength="80" id="name" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="description" key="manager.registrationOptions.form.description"}</td>
	<td class="value"><textarea name="description[{$formLocale|escape}]" id="description" cols="40" rows="4" class="textArea">{$description[$formLocale]|escape}</textarea></td>
</tr>
<tr valign="top">
	<td class="label">{translate key="manager.registrationOptions.cost"}</td>
	<td class="value">{translate key="manager.registrationOptions.costSetInRegistrationType"}</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="code" key="manager.registrationOptions.form.code"}</td>
	<td class="value">
		<input type="text" name="code" value="{$code|escape}" size="15" maxlength="20" id="code" class="textField" />
		<br />
		<span class="instruct">{translate key="manager.registrationOptions.form.code.instructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="openDate" required="true" key="manager.registrationOptions.form.openDate"}</td>
	<td class="value" id="openDate">
		{html_select_date prefix="openDate" time=$openDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$dateExtentFuture}
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="closeDate" required="true" key="manager.registrationOptions.form.closeDate"}</td>
	<td class="value" id="closeDate">
		{html_select_date prefix="closeDate" time=$closeDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$dateExtentFuture}
		<input type="hidden" name="closeDateHour" value="23" />
		<input type="hidden" name="closeDateMinute" value="59" />
		<input type="hidden" name="closeDateSecond" value="59" />
	</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="common.options"}</td>
	<td class="value">
		<input type="checkbox" name="notPublic" id="notPublic" value="1"{if $notPublic} checked="checked"{/if} />
		<label for="notPublic">{translate key="manager.registrationOptions.form.notPublic"}</label>
	</td>
</tr>
</table>
<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> {if not $optionId}<input type="submit" name="createAnother" value="{translate key="manager.registrationOptions.form.saveAndCreateAnotherOption"}" class="button" /> {/if}<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="registrationOptions"}'" /></p>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
