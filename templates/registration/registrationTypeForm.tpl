{**
 * templates/registration/registrationTypeForm.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Registration type form under scheduled conference management.
 *
 *}
{strip}
{if $typeId}
	{assign var="pageTitle" value="manager.registrationTypes.edit"}
{else}
	{assign var="pageTitle" value="manager.registrationTypes.create"}
{/if}
{assign var="pageId" value="manager.registrationTypes.registrationTypeForm"}
{assign var="pageCrumbTitle" value=$registrationTypeTitle}
{include file="common/header.tpl"}
{/strip}

{if $registrationTypeCreated}
<br/>
{translate key="manager.registrationTypes.registrationTypeCreatedSuccessfully"}<br />
{/if}

<br/>

<form class="pkp_form" id="registrationType" method="post" action="{url op="updateRegistrationType"}">
{if $typeId}
<input type="hidden" name="typeId" value="{$typeId|escape}" />
{/if}

{include file="common/formErrors.tpl"}
<table class="data" width="100%">
{if count($formLocales) > 1}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td colspan="2" width="80%" class="value">
			{if $typeId}{url|assign:"registrationTypeUrl" op="editRegistrationType" path=$typeId escape=false}
			{else}{url|assign:"registrationTypeUrl" op="createRegistrationType" escape=false}
			{/if}
			{form_language_chooser form="registrationType" url=$registrationTypeUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>

		</td>
	</tr>
{/if}
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="name" required="true" key="manager.registrationTypes.form.typeName"}</td>
	<td width="80%" colspan="2" class="value"><input type="text" name="name[{$formLocale|escape}]" value="{$name[$formLocale]|escape}" size="35" maxlength="80" id="name" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="description" key="manager.registrationTypes.form.description"}</td>
	<td colspan="2" class="value"><textarea name="description[{$formLocale|escape}]" id="description" cols="40" rows="4" class="textArea richContent">{$description[$formLocale]|escape}</textarea></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="currency" required="true" key="manager.registrationTypes.form.currency"}</td>
	<td colspan="2"><select name="currency" id="currency" class="selectMenu">{html_options options=$validCurrencies selected=$currency}</select></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="cost" required="true" key="manager.registrationTypes.form.cost"}</td>
	<td colspan="2" class="value">
		<span class="instruct">{translate key="manager.registrationTypes.form.costInstructions"}</span>
		<br />
		<input type="text" name="cost" value="{$cost|escape}" size="5" maxlength="10" id="cost" class="textField" />		
	</td>
</tr>

{foreach from=$registrationOptions item=registrationOption name=registrationOptions}
	<tr valign="top">
		{if $smarty.foreach.registrationOptions.first}
			<td rowspan="{$registrationOptions|@count}" class="label">{translate key="manager.registrationOptions.costs"}</td>
		{/if}
		<td class="value" width="20%">
			{assign var=optionId value=$registrationOption->getOptionId()}
			<label for="optionCost-{$optionId|escape}">{$registrationOption->getRegistrationOptionName()|escape}</label>
		</td>
		<td class="value" width="60%">
			<input type="text" name="registrationOptionCosts[{$optionId|escape}]" class="textField" size="5" maxlength="10" id="optionCost-{$optionId|escape}" value="{$registrationOptionCosts[$optionId]|escape}" />
		</td>
	</tr>
{/foreach}

<tr valign="top">
	<td class="label">{fieldLabel name="code" key="manager.registrationTypes.form.code"}</td>
	<td colspan="2" class="value">
		<input type="text" name="code" value="{$code|escape}" size="15" maxlength="20" id="code" class="textField" />
		<br />
		<span class="instruct">{translate key="manager.registrationTypes.form.code.instructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="openDate" required="true" key="manager.registrationTypes.form.openDate"}</td>
	<td colspan="2" class="value" id="openDate">
		{html_select_date prefix="openDate" time=$openDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$dateExtentFuture}
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="closeDate" required="true" key="manager.registrationTypes.form.closeDate"}</td>
	<td colspan="2" class="value" id="closeDate">
		{html_select_date prefix="closeDate" time=$closeDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$dateExtentFuture}
		<input type="hidden" name="closeDateHour" value="23" />
		<input type="hidden" name="closeDateMinute" value="59" />
		<input type="hidden" name="closeDateSecond" value="59" />
	</td>
</tr>
<tr valign="top">
	<td class="label">&nbsp;</td>
	<td colspan="2" class="value">
		<input id="expiryDate" type="checkbox" name="expiryDate" value="1" {if $expiryDate}checked="checked" {/if} />&nbsp;{fieldLabel name="expiryDate" key="manager.registrationTypes.form.expiryDate"}
		{html_select_date prefix="expiryDate" time=$expiryDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$dateExtentFuture}
		<input type="hidden" name="expiryDateHour" value="23" />
		<input type="hidden" name="expiryDateMinute" value="59" />
		<input type="hidden" name="expiryDateSecond" value="59" />
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="access" required="true" key="manager.registrationTypes.form.access"}</td>
	<td colspan="2"><select id="access" name="access" class="selectMenu">{html_options options=$validAccessTypes selected=$access}</select></td>
</tr>
<tr valign="top">
	<td rowspan="3">{translate key="common.options"}</td>
	<td colspan="2" class="value">
		<input type="checkbox" name="institutional" id="institutional" value="1"{if $institutional} checked="checked"{/if} />
		<label for="institutional">{translate key="manager.registrationTypes.form.institutional"}</label>
	</td>
</tr>
<tr valign="top">
	<td colspan="2" class="value">
		<input type="checkbox" name="membership" id="membership" value="1"{if $membership} checked="checked"{/if} />
		<label for="membership">{translate key="manager.registrationTypes.form.membership"}</label>
	</td>
</tr>
<tr valign="top">
	<td colspan="2" class="value">
		<input type="checkbox" name="notPublic" id="notPublic" value="1"{if $notPublic} checked="checked"{/if} />
		<label for="notPublic">{translate key="manager.registrationTypes.form.notPublic"}</label>
	</td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> {if not $typeId}<input type="submit" name="createAnother" value="{translate key="manager.registrationTypes.form.saveAndCreateAnotherType"}" class="button" /> {/if}<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="registrationTypes"}'" /></p>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}

