{**
 * registrationForm.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Registration form under scheduled conference management.
 *
 * $Id$
 *}
{strip}
{assign var="pageCrumbTitle" value="$registrationTitle"}
{if $registrationId}
	{assign var="pageTitle" value="manager.registration.edit"}
{else}
	{assign var="pageTitle" value="manager.registration.create"}
{/if}
{assign var="pageId" value="manager.registration.registrationForm"}
{include file="common/header.tpl"}
{/strip}

<br/>

<form method="post" action="{url op="updateRegistration"}">
{if $registrationId}
<input type="hidden" name="registrationId" value="{$registrationId|escape}" />
{/if}

{include file="common/formErrors.tpl"}

	<script type="text/javascript">
		{literal}
		<!--
			function toggleAllowNotifyPaymentEmail(form) {
				form.notifyPaymentEmail.disabled = !form.notifyPaymentEmail.disabled;
			}
		// -->
		{/literal}
	</script>

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="userId" required="true" key="manager.registration.form.userId"}</td>
	<td width="80%" class="value" id="userId">
		{$user->getFullName()|escape}&nbsp;&nbsp;<a href="{if $registrationId}{url op="selectRegistrant" registrationId=$registrationId}{else}{url op="selectRegistrant"}{/if}" class="action">{translate key="common.select"}</a>
		<input type="hidden" name="userId" value="{$user->getId()}"/>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="typeId" required="true" key="manager.registration.form.typeId"}</td>
	<td class="value"><select name="typeId" id="typeId" class="selectMenu">
		{iterate from=registrationTypes item=registrationType}
		<option value="{$registrationType->getTypeId()}"{if $typeId == $registrationType->getTypeId()} selected="selected"{/if}>{$registrationType->getSummaryString()|escape}</option>
		{/iterate} 
	</select></td>
</tr>
{foreach from=$registrationOptions item=registrationOption name=registrationOptions}
{assign var=optionId value=$registrationOption->getOptionId()}
<tr valign="top">
	{if $smarty.foreach.registrationOptions.first}
		<td rowspan="{$registrationOptions|@count}" class="label">{translate key="schedConf.registration.options"}</td>
	{/if}
	<td class="value">
	<input id="registrationOptions-{$optionId|escape}" {if $registrationOptionIds && in_array($optionId, $registrationOptionIds)}checked="checked" {/if}type="checkbox" name="registrationOptionIds[]" value="{$optionId|escape}]"/> <label for="registrationOptions-{$optionId|escape}">{$registrationOption->getRegistrationOptionName()}</label>	
	</td>
</tr>
{/foreach}
<tr valign="top">
	<td class="label">{fieldLabel name="membership" key="manager.registration.form.membership"}</td>
	<td class="value">
		<input type="text" name="membership" value="{$membership|escape}" id="membership" size="40" maxlength="40" class="textField" />
		<br />
		<span class="instruct">{translate key="manager.registration.form.membershipInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td class="value">
		<table width="100%">
			<tr valign="top">
				<td width="5%"><input type="checkbox" name="notifyEmail" id="notifyEmail" value="1"{if $notifyEmail} checked="checked"{/if} /></td>
				<td width="95%"><label for="notifyEmail">{translate key="manager.registration.form.notifyEmail"}</label></td>
			</tr>
		</table>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="domain" key="manager.registration.form.domain"}</td>
	<td class="value">
		<input type="text" name="domain" value="{$domain|escape}" size="40" id="domain" maxlength="255" class="textField" />
		<br />
		<span class="instruct">{translate key="manager.registration.form.domainInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="ipRange" key="manager.registration.form.ipRange"}</td>
	<td class="value">
		<input type="text" id="ipRange" name="ipRange" value="{$ipRange|escape}" size="40" maxlength="255" class="textField" />
		<br />
		<span class="instruct">{translate key="manager.registration.form.ipRangeInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="specialRequests" key="manager.registration.form.specialRequests"}</td>
	<td class="value">
		<textarea id="specialRequests" name="specialRequests" cols="40" rows="5" class="textArea">{$specialRequests|escape}</textarea>
		<br />
		<span class="instruct">{translate key="manager.registration.form.specialRequestsInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="paid" key="manager.registration.form.payment"}</td>
	<td class="value">
		<input type="checkbox" id="paid" name="paid" value="1" {if $datePaid}checked="checked"{/if} onclick="toggleAllowNotifyPaymentEmail(this.form)"/>&nbsp;&nbsp;{html_select_date prefix="datePaid" time=$datePaid all_extra="class=\"selectMenu\"" start_year=$yearOffsetPast end_year=$yearOffsetFuture}
		<br />
		<span class="instruct">{translate key="manager.registration.form.payment.description"}</span>
	</td>
</tr>
<tr valign="top">
	<td>&nbsp;</td>
	<td class="value">
		<table width="100%">
			<tr valign="top">
				<td width="5%"><input type="checkbox" name="notifyPaymentEmail" id="notifyPaymentEmail" value="1"{if $notifyPaymentEmail} checked="checked"{/if} {if !$datePaid} disabled="true"{/if}/></td>
				<td width="95%"><label for="notifyPaymentEmail">{translate key="manager.registration.form.notifyPaymentEmail"}</label></td>
			</tr>
		</table>
	</td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> {if not $registrationId}<input type="submit" name="createAnother" value="{translate key="manager.registration.form.saveAndCreateAnother"}" class="button" /> {/if}<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="registration"}'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
