{**
 * registrationTypeForm.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Registration type form under scheduled conference management.
 *
 * $Id$
 *}

{if $typeId}
	{assign var="pageTitle" value="manager.registrationTypes.edit"}
{else}
	{assign var="pageTitle" value="manager.registrationTypes.create"}
{/if}

{assign var="pageId" value="manager.registrationTypes.registrationTypeForm"}
{assign var="pageCrumbTitle" value=$registrationTypeTitle}
{include file="common/header.tpl"}

{if $registrationTypeCreated}
<br/>
{translate key="manager.registrationTypes.registrationTypeCreatedSuccessfully"}<br />
{/if}

<br/>

<form method="post" action="{url op="updateRegistrationType"}">
{if $typeId}
<input type="hidden" name="typeId" value="{$typeId}" />
{/if}

{include file="common/formErrors.tpl"}
<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="typeName" required="true" key="manager.registrationTypes.form.typeName"}</td>
	<td width="80%" class="value"><input type="text" name="typeName" value="{$typeName|escape}" size="35" maxlength="80" id="typeName" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="description" key="manager.registrationTypes.form.description"}</td>
	<td class="value"><textarea name="description" id="description" cols="40" rows="4" class="textArea" />{$description|escape}</textarea></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="cost" required="true" key="manager.registrationTypes.form.cost"}</td>
	<td class="value">
		<input type="text" name="cost" value="{$cost|escape}" size="5" maxlength="10" id="cost" class="textField" />
		<br />
		<span class="instruct">{translate key="manager.registrationTypes.form.costInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="currency" required="true" key="manager.registrationTypes.form.currency"}</td>
	<td><select name="currency" id="currency" class="selectMenu" />{html_options options=$validCurrencies selected=$currency}</select></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="openDate" required="true" key="manager.registrationTypes.form.openDate"}</td>
	<td class="value">
		{html_select_date prefix="openDate" time=$openDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$dateExtentFuture}
		<br />
		<span class="instruct">{translate key="manager.registrationTypes.form.openDateInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="closeDate" required="true" key="manager.registrationTypes.form.closeDate"}</td>
	<td class="value">
		{html_select_date prefix="closeDate" time=$closeDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$dateExtentFuture}
		<br />
		<span class="instruct">{translate key="manager.registrationTypes.form.closeDateInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="expiryDate" required="true" key="manager.registrationTypes.form.expiryDate"}</td>
	<td class="value">
		{html_select_date prefix="expiryDate" time=$expiryDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$dateExtentFuture}
		<br />
		<span class="instruct">{translate key="manager.registrationTypes.form.expiryDateInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="access" required="true" key="manager.registrationTypes.form.access"}</td>
	<td><select id="access" name="access" class="selectMenu" />{html_options options=$validAccessTypes selected=$access}</select></td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td class="value">
		<input type="checkbox" name="institutional" id="institutional" value="1"{if $institutional} checked="checked"{/if} />
		<label for="institutional">{translate key="manager.registrationTypes.form.institutional"}</label>
	</td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td class="value">
		<input type="checkbox" name="membership" id="membership" value="1"{if $membership} checked="checked"{/if} />
		<label for="membership">{translate key="manager.registrationTypes.form.membership"}</label>
	</td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td class="value">
		<input type="checkbox" name="public" id="public" value="1"{if $public} checked="checked"{/if} />
		<label for="public">{translate key="manager.registrationTypes.form.public"}</label>
	</td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> {if not $typeId}<input type="submit" name="createAnother" value="{translate key="manager.registrationTypes.form.saveAndCreateAnotherType"}" class="button" /> {/if}<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="registrationTypes" escape=false}'" /></p>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
