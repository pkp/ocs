{**
 * templates/registration/selectRegistrationType.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Attendee page to select registration type.
 *
 *}
{assign var="pageTitle" value="schedConf.registration"}
{include file="common/header.tpl"}

{if $registration}
	{* An unpaid registration already exists. Warn the user. *}
	<div id="registrationWarning">
		<p>{translate key="schedConf.registration.alreadyRegistered"}</p>
	</div>
{/if}

<form class="pkp_form" action="{url op="registration"}" method="post">
<div id="registrationType">
<table class="listing" width="100%">
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
	<tr valign="top" class="heading">
		<td width="60%">{translate key="schedConf.registration.type"}</td>
		<td width="60%">{translate key="schedConf.registration.cost"}</td>
	</tr>
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
	{assign var="isFirstRegistrationType" value=true}
	{iterate from=registrationTypes item=registrationType}
	{assign var="typeId" value=$registrationType->getTypeId()}
	{if $registrationType->getPublic()}
		<tr valign="top">
			<td class="label">
				<strong>{$registrationType->getRegistrationTypeName()|escape}</strong>
			</td>
			<td class="data">
				{if strtotime($registrationType->getOpeningDate()) < time() && strtotime($registrationType->getClosingDate()) > time()}
					{assign var="registrationMethodAvailable" value=1}
					<input id="registrationType-{$typeId|escape}" type="radio" {if (!$registration && $isFirstRegistrationType) || ($registration && $registration->getTypeId() == $typeId)}checked="checked" {/if}name="registrationTypeId" value="{$typeId|escape}" />
					<label for="registrationType-{$typeId|escape}"> {$registrationType->getCost()|string_format:"%.2f"} {$registrationType->getCurrencyCodeAlpha()|escape}</label>
					{translate key="schedConf.registration.typeCloses" closingDate=$registrationType->getClosingDate()|date_format:$dateFormatShort}
					{assign var="isFirstRegistrationType" value=false}
				{elseif strtotime($registrationType->getOpeningDate()) > time()}
					<input type="radio" name="registrationTypeId" value="{$typeId|escape}" disabled="disabled" />
					{$registrationType->getCost()|string_format:"%.2f"} {$registrationType->getCurrencyCodeAlpha()|escape}
					{translate key="schedConf.registration.typeFuture" openingDate=$registrationType->getOpeningDate()|date_format:$dateFormatShort}
				{else}
					<input type="radio" name="registrationTypeId" value="{$typeId|escape}" disabled="disabled" />
					{$registrationType->getCost()|string_format:"%.2f"} {$registrationType->getCurrencyCodeAlpha()|escape}
					{translate key="schedConf.registration.typeClosed" closingDate=$registrationType->getClosingDate()|date_format:$dateFormatShort}
				{/if}
			</td>
		</tr>
		{if $registrationType->getRegistrationTypeDescription()}
			<tr valign="top">
				<td colspan="2">{$registrationType->getRegistrationTypeDescription()|nl2br}</td>
			</tr>
		{/if}
		<tr valign="top">
			<td colspan="2">&nbsp;</td>
		</tr>
	{/if}
	{/iterate}
	{if $registrationTypes->wasEmpty()}
		<tr>
			<td colspan="2" class="nodata">{translate key="schedConf.registrationTypes.noneAvailable"}</td>
		</tr>
	{/if}
	<tr>
		<td colspan="2" class="endseparator">&nbsp;</td>
	</tr>
</table>
</div>
{if $currentSchedConf->getSetting('registrationName')}

<div id="registrationContact">
<h3>{translate key="manager.registrationPolicies.registrationContact"}</h3>

<table class="data" width="100%">
	<tr valign="top">
		<td width="20%" class="label">{translate key="user.name"}</td>
		<td width="80%" class="value">{$currentSchedConf->getSetting('registrationName')|escape}</td>
	</tr>
	{if $currentSchedConf->getSetting('registrationEmail')}<tr valign="top">
		<td class="label">{translate key="about.contact.email"}</td>
		<td class="value">{mailto address=$currentSchedConf->getSetting('registrationEmail')|escape encode="hex"}</td>
	</tr>{/if}
	{if $currentSchedConf->getSetting('registrationPhone')}<tr valign="top">
		<td class="label">{translate key="about.contact.phone"}</td>
		<td class="value">{$currentSchedConf->getSetting('registrationPhone')|escape}</td>
	</tr>{/if}
	{if $currentSchedConf->getSetting('registrationFax')}<tr valign="top">
		<td class="label">{translate key="about.contact.fax"}</td>
		<td class="value">{$currentSchedConf->getSetting('registrationFax')|escape}</td>
	</tr>{/if}
	{if $currentSchedConf->getSetting('registrationMailingAddress')}<tr valign="top">
		<td class="label">{translate key="common.mailingAddress"}</td>
		<td class="value">{$currentSchedConf->getSetting('registrationMailingAddress')|nl2br}</td>
	</tr>{/if}
</table>
</div>
{/if}{* if displaying reg manager info *}

<p><input type="submit" value="{translate key="schedConf.registration.register"}" {if !$registrationMethodAvailable}disabled="disabled" class="button" {else}class="button defaultButton" {/if}/></p>

</form>

{include file="common/footer.tpl"}

