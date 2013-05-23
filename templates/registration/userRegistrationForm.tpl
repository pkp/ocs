{**
 * templates/registration/userRegistrationForm.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Attendee self-registration page.
 *
 *}
{strip}
{assign var="pageTitle" value="schedConf.registration"}
{include file="common/header.tpl"}
{/strip}

<form class="pkp_form" action="{url op="register"}" id="registration" method="post">
<input type="hidden" name="registrationTypeId" value="{$registrationTypeId|escape}" />

{include file="common/formErrors.tpl"}

{if $registration}
	{* An unpaid registration already exists. Warn the user. *}
	<div id="registrationWarning">
		<p>{translate key="schedConf.registration.alreadyRegistered"}</p>
	</div>
{/if}

{if count($formLocales) > 1 && !$existingUser}
<div id="locales">
<table class="data" width="100%">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"registerUrl" op="registration" escape=false}
			{form_language_chooser form="registration" url=$registerUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
</table>
</div>
{/if}

{assign var="registrationAdditionalInformation" value=$schedConf->getLocalizedSetting('registrationAdditionalInformation')}
{if $registrationAdditionalInformation}
<div id="registrationAdditionalInformation">
	<h3>{translate key="manager.registrationPolicies.registrationAdditionalInformation"}</h3>

	<p>{$registrationAdditionalInformation|nl2br}</p>
</div>
	<div class="separator"></div>
{/if}
<div id="conferenceFees">
<h3>{translate key="schedConf.registration.conferenceFees"}</h3>

{if !$registrationOptions->wasEmpty()}
<table class="listing" width="100%">
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
	<tr valign="top" class="heading">
		<td width="60%">{translate key="schedConf.registration.options"}</td>
		<td width="60%">{translate key="schedConf.registration.cost"}</td>
	</tr>
	<tr>
		<td colspan="2" class="headseparator">&nbsp;</td>
	</tr>
	{iterate from=registrationOptions item=registrationOption}
	{assign var=optionId value=$registrationOption->getOptionId()}
	{if $registrationOption->getPublic()}
		<tr valign="top">
			<td class="label">
				<strong>{$registrationOption->getRegistrationOptionName()|escape}</strong>
			</td>
			<td class="data">
				{if strtotime($registrationOption->getOpeningDate()) < time() && strtotime($registrationOption->getClosingDate()) > time()}
					<input id="registrationOption-{$optionId|escape}" type="checkbox" name="registrationOptionId[]" {if in_array($optionId, (array) $registrationOptionId)}checked="checked" {/if} value="{$optionId|escape}" />
					<label for="registrationOption-{$optionId|escape}">{$registrationOptionCosts[$optionId]|string_format:"%.2f"|escape} {$registrationType->getCurrencyCodeAlpha()|escape}</label>
					{translate key="schedConf.registration.optionCloses" closingDate=$registrationOption->getClosingDate()|date_format:$dateFormatShort}
				{elseif strtotime($registrationOption->getOpeningDate()) > time()}
					<input type="checkbox" name="registrationOptionId[]" value="{$optionId|escape}" disabled="disabled" />
					{$registrationOptionCosts[$optionId]|string_format:"%.2f"|escape} {$registrationType->getCurrencyCodeAlpha()|escape}
					{translate key="schedConf.registration.optionFuture" openingDate=$registrationOption->getOpeningDate()|date_format:$dateFormatShort}
				{else}
					<input type="checkbox" name="registrationOptionId[]" value="{$optionId|escape}" disabled="disabled" />
					{$registrationOptionCosts[$optionId]|string_format:"%.2f"|escape} {$registrationType->getCurrencyCodeAlpha()|escape}
					{translate key="schedConf.registration.optionClosed" closingDate=$registrationOption->getClosingDate()|date_format:$dateFormatShort}
				{/if}
			</td>
		</tr>
		{if $registrationOption->getRegistrationOptionDescription()}
			<tr valign="top">
				<td colspan="2">{$registrationOption->getRegistrationOptionDescription()|nl2br}</td>
			</tr>
		{/if}
		<tr valign="top">
			<td colspan="2">&nbsp;</td>
		</tr>
	{/if}
	{/iterate}
	{if $registrationOptions->wasEmpty()}
		<tr>
			<td colspan="2" class="nodata">{translate key="schedConf.registrationOptions.noneAvailable"}</td>
		</tr>
	{/if}
	<tr>
		<td colspan="2" class="endseparator">&nbsp;</td>
	</tr>
</table>
{/if}

<p>
	<label for="feeCode">{translate key="schedConf.registration.feeCode"}</label>&nbsp;&nbsp;<input id="feeCode" name="feeCode" type="text" value="{$feeCode|escape}" class="textField" /><br />
	{translate key="schedConf.registration.feeCode.description"}
</p>
</div>
<div class="separator"></div>
<div id="account">
<h3>{translate key="schedConf.registration.account"}</h3>
{if $userLoggedIn}
	{url|assign:"logoutUrl" page="login" op="signOut" source=$requestUri}
	{url|assign:"profileUrl" page="user" op="profile"}
	<p>{translate key="schedConf.registration.loggedInAs" logoutUrl=$logoutUrl profileUrl=$profileUrl}</p>

	<table class="data" width="100%">
		<tr valign="top">
			<td width="20%" class="label">{translate key="user.name"}</td>
			<td width="80%" class="value">{$user->getFullName()|escape}</td>
		</tr>
		<tr valign="top">
			<td class="label">{translate key="user.email"}</td>
			<td class="value">{$user->getEmail()|escape}</td>
		</tr>
		<tr valign="top">
			<td class="label">{translate key="user.phone"}</td>
			<td class="value">{$user->getPhone()|escape}</td>
		</tr>
		<tr valign="top">
			<td class="label">{translate key="user.fax"}</td>
			<td class="value">{$user->getFax()|escape}</td>
		</tr>
		<tr valign="top">
			<td class="label">{translate key="common.mailingAddress"}</td>
			<td class="value">{$user->getMailingAddress()|strip_unsafe_html|nl2br}</td>
		</tr>
		{if $user->getBillingAddress()}
		<tr valign="top">
			<td class="label">{translate key="common.billingAddress"}</td>
			<td class="value">{$user->getBillingAddress()|strip_unsafe_html|nl2br}</td>
		</tr>
		{/if}
	</table>
{else}
	{url|assign:"loginUrl" page="login" op="index" source=$requestUri}
	<p>{translate key="schedConf.registration.createAccount.description" loginUrl=$loginUrl}</p>

	<table class="data" width="100%">
		<tr valign="top">
			<td width="20%" class="label">{fieldLabel name="username" required="true" key="user.username"}</td>
			<td width="80%" class="value"><input type="text" name="username" value="{$username|escape}" id="username" size="20" maxlength="32" class="textField" /></td>
	</tr>

	<tr valign="top">
		<td class="label">{fieldLabel name="password" required="true" key="user.password"}</td>
		<td class="value"><input type="password" name="password" value="{$password|escape}" id="password" size="20" maxlength="32" class="textField" /></td>
	</tr>

	<tr valign="top">
		<td></td>
		<td class="instruct">{translate key="user.account.passwordLengthRestriction" length=$minPasswordLength}</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="password2" required="true" key="user.repeatPassword"}</td>
		<td class="value"><input type="password" name="password2" id="password2" value="{$password2|escape}" size="20" maxlength="32" class="textField" /></td>
	</tr>

{if $captchaEnabled}
	<tr>
		<td class="label" valign="top">{fieldLabel name="recaptcha_challenge_field" required="true" key="common.captchaField"}</td>
		<td class="value">
			{$reCaptchaHtml}
		</td>
	</tr>
{/if}

<tr valign="top">
	<td class="label">{fieldLabel name="salutation" key="user.salutation"}</td>
	<td class="value"><input type="text" name="salutation" id="salutation" value="{$salutation|escape}" size="20" maxlength="40" class="textField" /></td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="firstName" required="true" key="user.firstName"}</td>
	<td class="value"><input type="text" id="firstName" name="firstName" value="{$firstName|escape}" size="20" maxlength="40" class="textField" /></td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="middleName" key="user.middleName"}</td>
	<td class="value"><input type="text" id="middleName" name="middleName" value="{$middleName|escape}" size="20" maxlength="40" class="textField" /></td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="lastName" required="true" key="user.lastName"}</td>
	<td class="value"><input type="text" id="lastName" name="lastName" value="{$lastName|escape}" size="20" maxlength="90" class="textField" /></td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="initials" key="user.initials"}</td>
	<td class="value"><input type="text" id="initials" name="initials" value="{$initials|escape}" size="5" maxlength="5" class="textField" />&nbsp;&nbsp;{translate key="user.initialsExample"}</td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="gender" key="user.gender"}</td>
	<td class="value"><select name="gender" id="gender" size="1" class="selectMenu">
			{html_options_translate options=$genderOptions selected=$gender}
		</select>
	</td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="affiliation" key="user.affiliation" required="true"}</td>
	<td class="value"><textarea id="affiliation" name="affiliation" rows="5" cols="40" class="textArea">{$affiliation|escape}</textarea></td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="signature" key="user.signature"}</td>
	<td class="value"><textarea name="signature[{$formLocale|escape}]" id="signature" rows="5" cols="40" class="textArea">{$signature[$formLocale]|escape}</textarea></td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="email" required="true" key="user.email"}</td>
	<td class="value"><input type="text" id="email" name="email" value="{$email|escape}" size="30" maxlength="90" class="textField" /></td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="userUrl" key="user.url"}</td>
	<td class="value"><input type="text" id="userUrl" name="userUrl" value="{$userUrl|escape}" size="30" maxlength="90" class="textField" /></td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="phone" key="user.phone"}</td>
	<td class="value"><input type="text" name="phone" id="phone" value="{$phone|escape}" size="15" maxlength="24" class="textField" /></td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="fax" key="user.fax"}</td>
	<td class="value"><input type="text" name="fax" id="fax" value="{$fax|escape}" size="15" maxlength="24" class="textField" /></td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="mailingAddress" required="true" key="common.mailingAddress"}</td>
	<td class="value"><textarea name="mailingAddress" id="mailingAddress" rows="3" cols="40" class="textArea richContent">{$mailingAddress|escape}</textarea></td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="billingAddress" key="common.billingAddress"}</td>
	<td class="value"><textarea name="billingAddress" id="billingAddress" rows="3" cols="40" class="textArea richContent">{$billingAddress|escape}</textarea></td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="country" required="true" key="common.country"}</td>
	<td class="value">
		<select name="country" id="country" class="selectMenu">
			<option value=""></option>
			{html_options options=$countries selected=$country}
		</select>
	</td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="biography" key="user.biography"}<br />{translate key="user.biography.description"}</td>
	<td class="value"><textarea name="biography[{$formLocale|escape}]" id="biography" rows="5" cols="40" class="textArea richContent">{$biography[$formLocale]|escape}</textarea></td>
</tr>

</table>

{/if}{* user is logged in *}
</div>
<div class="separator"></div>
<div id="specialRequests">
<h3>{translate key="schedConf.registration.specialRequests"}</h3>

<p><label for="specialRequests">{translate key="schedConf.registration.specialRequests.description"}</label></p>

<p><textarea name="specialRequests" id="specialRequests" cols="60" rows="10" class="textArea richContent">{$specialRequests|escape}</textarea></p>
</div>
<div class="separator"></div>

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
<div class="separator"></div>
{/if}{* if displaying reg manager info *}

<p><input type="submit" value="{translate key="schedConf.registration.register"}" class="button defaultButton" /></p>

</form>

{include file="common/footer.tpl"}

