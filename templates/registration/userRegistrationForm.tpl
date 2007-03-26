{**
 * userRegistrationForm.tpl
 *
 * Copyright (c) 2006-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Attendee self-registration page.
 *
 * $Id$
 *}

{assign var="pageTitle" value="schedConf.registration"}
{include file="common/header.tpl"}

<form action="{url op="register"}" method="post">

{include file="common/formErrors.tpl"}

{assign var="registrationAdditionalInformation" value=$schedConf->getSetting('registrationAdditionalInformation')}
{if $registrationAdditionalInformation}
	<h3>{translate key="manager.registrationPolicies.registrationAdditionalInformation"}</h3>

	<p>{$registrationAdditionalInformation|nl2br}</p>

	<div class="separator"></div>
{/if}

<h3>{translate key="schedConf.registration.conferenceFees"}</h3>

{assign var="registrationMethodAvailable" value=0}

<table class="listing" width="100%">
	<tr>
		<td colspan="3" class="headseparator">&nbsp;</td>
	</tr>
	<tr valign="top" class="heading">
		<td colspan="2" width="60%">{translate key="schedConf.registration.type"}</td>
		<td width="60%">{translate key="schedConf.registration.cost"}</td>
	</tr>
	<tr>
		<td colspan="3" class="headseparator">&nbsp;</td>
	</tr>
	{assign var="isFirstRegistrationType" value=true}
	{iterate from=registrationTypes item=registrationType}
		<tr valign="top">
			<td colspan="2" class="label">
				<strong>{$registrationType->getTypeName()|escape}</strong>
			</td>
			<td class="data">
				{if strtotime($registrationType->getOpeningDate()) < time() && strtotime($registrationType->getClosingDate()) > time()}
					{assign var="registrationMethodAvailable" value=1}
					<input type="radio" name="registrationTypeId" {if $registrationTypeId == $registrationType->getTypeId() || (!$registrationTypeId && $isFirstRegistrationType)}checked="checked" {/if} value="{$registrationType->getTypeId()|escape}" />
					{assign var="isFirstRegistrationType" value=false}
				{else}
					<input type="radio" name="registrationTypeId" value="{$registrationType->getTypeId()|escape}" disabled="disabled" />&nbsp;{translate key="schedConf.registration.typeClosed"}
				{/if}
				{$registrationType->getCost()|escape} ({$registrationType->getCurrencyCodeAlpha()|escape})
			</td>
		</tr>
		{if $registrationType->getDescription()}
			<tr valign="top">
				<td>&nbsp;</td>
				<td>{$registrationType->getDescription()|nl2br}</td>
			</tr>
		{/if}
	{/iterate}
	{if $registrationTypes->wasEmpty()}
		<tr>
			<td colspan="3" class="nodata">{translate key="schedConf.registration.noneAvailable"}</td>
		</tr>
	{/if}
	<tr>
		<td colspan="3" class="endseparator">&nbsp;</td>
	</tr>
</table>

<p>
	<label for="feeCode">{translate key="schedConf.registration.feeCode"}</label>&nbsp;&nbsp;<input id="feeCode" type="text" value="{$feeCode|escape}" class="textField" /><br />
	{translate key="schedConf.registration.feeCode.description"}
</p>

<div class="separator"></div>

{if !$registrationMethodAvailable}
	{assign var="disableSnippet" value="disabled=\"disabled\""}
{/if}

<h3>{translate key="schedConf.registration.account"}</h3>
{if $userLoggedIn}
	{url|assign:"logoutUrl" page="login" op="signOut" source=$requestUri}
	<p>{translate key="schedConf.registration.loggedInAs" userFullName=$userFullName logoutUrl=$logoutUrl}</p>
{else}
	{url|assign:"loginUrl" page="login" op="index" source=$requestUri}
	<p>{translate key="schedConf.registration.createAccount.description" loginUrl=$loginUrl}</p>

	<table class="data" width="100%">
		<tr valign="top">	
			<td width="20%" class="label">{fieldLabel name="username" required="true" key="user.username"}</td>
			<td width="80%" class="value"><input {$disableSnippet} type="text" name="username" value="{$username|escape}" id="username" size="20" maxlength="32" class="textField" /></td>
	</tr>

	<tr valign="top">
		<td class="label">{fieldLabel name="password" required="true" key="user.password"}</td>
		<td class="value"><input {$disableSnippet} type="password" name="password" value="{$password|escape}" id="password" size="20" maxlength="32" class="textField" /></td>
	</tr>

	<tr valign="top">
		<td></td>
		<td class="instruct">{translate key="user.account.passwordLengthRestriction" length=$minPasswordLength}</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="password2" required="true" key="user.account.repeatPassword"}</td>
		<td class="value"><input {$disableSnippet} type="password" name="password2" id="password2" value="{$password2|escape}" size="20" maxlength="32" class="textField" /></td>
	</tr>

{if $captchaEnabled}
		<tr>
			<td class="label" valign="top">{fieldLabel name="captcha" required="true" key="common.captchaField"}</td>
			<td class="value">
			<img src="{url page="user" op="viewCaptcha" path=$captchaId}" alt="" /><br />
			<span class="instruct">{translate key="common.captchaField.description"}</span><br />
			<input {$disableSnippet} name="captcha" id="captcha" value="" size="20" maxlength="32" class="textField" />
			<input type="hidden" name="captchaId" value="{$captchaId|escape:"quoted"}" />
		</td>
	</tr>
{/if}

<tr valign="top">
	<td class="label">{fieldLabel name="firstName" required="true" key="user.firstName"}</td>
	<td class="value"><input {$disableSnippet} type="text" id="firstName" name="firstName" value="{$firstName|escape}" size="20" maxlength="40" class="textField" /></td>
</tr>
	
<tr valign="top">
	<td class="label">{fieldLabel name="middleName" key="user.middleName"}</td>
	<td class="value"><input {$disableSnippet} type="text" id="middleName" name="middleName" value="{$middleName|escape}" size="20" maxlength="40" class="textField" /></td>
</tr>
	
<tr valign="top">
	<td class="label">{fieldLabel name="lastName" required="true" key="user.lastName"}</td>
	<td class="value"><input {$disableSnippet} type="text" id="lastName" name="lastName" value="{$lastName|escape}" size="20" maxlength="90" class="textField" /></td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="initials" key="user.initials"}</td>
	<td class="value"><input {$disableSnippet} type="text" id="initials" name="initials" value="{$initials|escape}" size="5" maxlength="5" class="textField" />&nbsp;&nbsp;{translate key="user.initialsExample"}</td>
</tr>
	
<tr valign="top">
	<td class="label">{fieldLabel name="affiliation" key="user.affiliation"}</td>
	<td class="value"><input {$disableSnippet} type="text" id="affiliation" name="affiliation" value="{$affiliation|escape}" size="30" maxlength="255" class="textField" /></td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="signature" key="user.signature"}</td>
	<td class="value"><textarea {$disableSnippet} name="signature" id="signature" rows="5" cols="40" class="textArea">{$signature|escape}</textarea></td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="email" required="true" key="user.email"}</td>
	<td class="value"><input {$disableSnippet} type="text" id="email" name="email" value="{$email|escape}" size="30" maxlength="90" class="textField" /></td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="userUrl" key="user.url"}</td>
	<td class="value"><input {$disableSnippet} type="text" id="userUrl" name="userUrl" value="{$userUrl|escape}" size="30" maxlength="90" class="textField" /></td>
</tr>
	
<tr valign="top">
	<td class="label">{fieldLabel name="phone" key="user.phone"}</td>
	<td class="value"><input {$disableSnippet} type="text" name="phone" id="phone" value="{$phone|escape}" size="15" maxlength="24" class="textField" /></td>
</tr>
	
<tr valign="top">
	<td class="label">{fieldLabel name="fax" key="user.fax"}</td>
	<td class="value"><input {$disableSnippet} type="text" name="fax" id="fax" value="{$fax|escape}" size="15" maxlength="24" class="textField" /></td>
</tr>
	
<tr valign="top">
	<td class="label">{fieldLabel name="mailingAddress" key="common.mailingAddress"}</td>
	<td class="value"><textarea {$disableSnippet} name="mailingAddress" id="mailingAddress" rows="3" cols="40" class="textArea">{$mailingAddress|escape}</textarea></td>
</tr>
	
<tr valign="top">
	<td class="label">{fieldLabel name="country" key="common.country"}</td>
	<td class="value">
		<select {$disableSnippet} name="country" id="country" class="selectMenu">
			<option value=""></option>
			{html_options options=$countries selected=$country}
		</select>
	</td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="biography" key="user.biography"}<br />{translate key="user.biography.description"}</td>
	<td class="value"><textarea {$disableSnippet} name="biography" id="biography" rows="5" cols="40" class="textArea">{$biography|escape}</textarea></td>
</tr>

{if $profileLocalesEnabled && count($availableLocales) > 1}
<tr valign="top">
	<td class="label">{translate key="user.workingLanguages"}</td>
	<td class="value">{foreach from=$availableLocales key=localeKey item=localeName}
		<input {$disableSnippet} type="checkbox" name="userLocales[]" id="userLocales-{$localeKey}" value="{$localeKey}"{if in_array($localeKey, $userLocales)} checked="checked"{/if} /> <label for="userLocales-{$localeKey}">{$localeName|escape}</label><br />
	{/foreach}</td>
</tr>

{/if}{* other locales exist *}

</table>

{/if}{* user is logged in *}

<div class="separator"></div>

<h3>{translate key="schedConf.registration.specialRequests"}</h3>

<p><label for="specialRequests">{translate key="schedConf.registration.specialRequests.description"}</label></p>

<p><textarea {$disableSnippet} name="specialRequests" id="specialRequests" cols="60" rows="10" class="textArea">{$specialRequests|escape}</textarea></p>

<input type="submit" value="{translate key="schedConf.registration.register"}" {if !$registrationMethodAvailable}disabled="disabled" class="button" {else}class="button defaultButton" {/if}/>

<div class="separator"></div>

</form>

{include file="common/footer.tpl"}
