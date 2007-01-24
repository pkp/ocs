{**
 * registrationForm.tpl
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Registration form under event management.
 *
 * $Id$
 *}

{assign var="pageCrumbTitle" value="$registrationTitle"}
{if $registrationId}
	{assign var="pageTitle" value="director.registrations.edit"}
{else}
	{assign var="pageTitle" value="director.registrations.create"}
{/if}

{assign var="pageId" value="director.registration.registrationForm"}
{include file="common/header.tpl"}

<br/>

<form method="post" action="{url op="updateRegistration"}">
{if $registrationId}
<input type="hidden" name="registrationId" value="{$registrationId}" />
{/if}

{include file="common/formErrors.tpl"}

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="userId" required="true" key="director.registrations.form.userId"}</td>
	<td width="80%" class="value">
		{$user->getFullName()|escape}&nbsp;&nbsp;<a href="{if $registrationId}{url op="selectRegistrant" registrationId=$registrationId}{else}{url op="selectRegistrant"}{/if}" class="action">{translate key="common.select"}</a>
		<input type="hidden" name="userId" value="{$user->getUserId()}"/>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="typeId" required="true" key="director.registrations.form.typeId"}</td>
	<td class="value"><select name="typeId" id="typeId" class="selectMenu" />
		{iterate from=registrationTypes item=registrationType}
		<option value="{$registrationType->getTypeId()}"{if $typeId == $registrationType->getTypeId()} selected="selected"{/if}>{$registrationType->getSummaryString()|escape}</option>
		{/iterate} 
	</select></td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td class="value">
		<table width="100%">
			<tr valign="top">
				<td width="5%"><input type="checkbox" name="notifyEmail" id="notifyEmail" value="1"{if $notifyEmail} checked="checked"{/if} /></td>
				<td width="95%"><label for="">{translate key="director.registrations.form.notifyEmail"}</label></td>
			</tr>
		</table>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="membership" key="director.registrations.form.membership"}</td>
	<td class="value">
		<input type="text" name="membership" value="{$membership|escape}" id="membership" size="40" maxlength="40" class="textField" />
		<br />
		<span class="instruct">{translate key="director.registrations.form.membershipInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="domain" key="director.registrations.form.domain"}</td>
	<td class="value">
		<input type="text" name="domain" value="{$domain|escape}" size="40" id="domain" maxlength="255" class="textField" />
		<br />
		<span class="instruct">{translate key="director.registrations.form.domainInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="ipRange" key="director.registrations.form.ipRange"}</td>
	<td class="value">
		<input type="text" id="ipRange" name="ipRange" value="{$ipRange|escape}" size="40" maxlength="255" class="textField" />
		<br />
		<span class="instruct">{translate key="director.registrations.form.ipRangeInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="specialRequests" key="director.registrations.form.specialRequests"}</td>
	<td class="value">
		<input type="text" id="specialRequests" name="specialRequests" value="{$specialRequests|escape}" size="40" maxlength="255" class="textField" />
		<br />
		<span class="instruct">{translate key="director.registrations.form.specialRequestsInstructions"}</span>
	</td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> {if not $registrationId}<input type="submit" name="createAnother" value="{translate key="director.registrations.form.saveAndCreateAnother"}" class="button" /> {/if}<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="registrations" escape=false}'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
