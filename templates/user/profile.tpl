{**
 * profile.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User profile form.
 *
 * $Id$
 *}
{assign var="pageTitle" value="user.profile.editProfile"}
{url|assign:"url" op="profile"}{include file="common/header.tpl"}

<form name="profile" method="post" action="{url op="saveProfile"}">

{include file="common/formErrors.tpl"}

<table class="data" width="100%">
{if count($formLocales) > 1}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"userProfileUrl" page="user" op="profile" escape=false}
			{form_language_chooser form="profile" url=$userProfileUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
{/if}
<tr valign="top">
	<td width="20%" class="label">{fieldLabel suppressId="true" name="username" key="user.username"}</td>
	<td width="80%" class="value">{$username|escape}</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="salutation" key="user.salutation"}</td>
	<td class="value"><input type="text" name="salutation" id="salutation" value="{$salutation|escape}" size="20" maxlength="40" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="firstName" required="true" key="user.firstName"}</td>
	<td class="value"><input type="text" name="firstName" id="firstName" value="{$firstName|escape}" size="20" maxlength="40" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="middleName" key="user.middleName"}</td>
	<td class="value"><input type="text" name="middleName" id="middleName" value="{$middleName|escape}" size="20" maxlength="40" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="lastName" required="true" key="user.lastName"}</td>
	<td class="value"><input type="text" name="lastName" id="lastName" value="{$lastName|escape}" size="20" maxlength="90" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="initials" key="user.initials"}</td>
	<td class="value"><input type="text" name="initials" id="initials" value="{$initials|escape}" size="5" maxlength="5" class="textField" />&nbsp;&nbsp;{translate key="user.initialsExample"}</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="gender" key="user.gender"}</td>
	<td class="value"><input type="radio" name="gender" id="gender-m" value="M" {if $gender == 'M'} checked="checked"{/if}/><label for="gender-m">{translate key="user.masculine"}</label> &nbsp;&nbsp;&nbsp; <input type="radio" name="gender" id="gender-f" value="F" {if $gender == 'F'} checked="checked"{/if} /><label for="gender-f">{translate key="user.feminine"}</label></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="affiliation" key="user.affiliation"}</td>
	<td class="value"><input type="text" name="affiliation" id="affiliation" value="{$affiliation|escape}" size="30" maxlength="255" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="signature" key="user.signature"}</td>
	<td class="value"><textarea name="signature[{$formLocale|escape}]" id="signature" rows="5" cols="40" class="textArea">{$signature[$formLocale]|escape}</textarea></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="email" required="true" key="user.email"}</td>
	<td class="value"><input type="text" name="email" id="email" value="{$email|escape}" size="30" maxlength="90" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="userUrl" key="user.url"}</td>
	<td class="value"><input type="text" name="userUrl" id="userUrl" value="{$userUrl|escape}" size="30" maxlength="90" class="textField" /></td>
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
	<td class="label">{fieldLabel name="mailingAddress" key="common.mailingAddress"}</td>
	<td class="value"><textarea name="mailingAddress" id="mailingAddress" rows="3" cols="40" class="textArea">{$mailingAddress|escape}</textarea></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="country" key="common.country"}</td>
	<td class="value">
		<select name="country" id="country" class="selectMenu">
			<option value=""></option>
			{html_options options=$countries selected=$country}
		</select>
	</td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="timeZone" key="common.timeZone"}</td>
	<td class="value">
		<select name="timeZone" id="timeZone" class="selectMenu">
			<option value=""></option>
			{html_options options=$timeZones selected=$timeZone}
		</select>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="interests" key="user.interests"}</td>
	<td class="value"><input type="text" name="interests[{$formLocale|escape}]" id="interests" value="{$interests[$formLocale]|escape}" size="30" maxlength="255" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="biography" key="user.biography"}<br />{translate key="user.biography.description"}</td>
	<td class="value"><textarea name="biography[{$formLocale|escape}]" id="biography" rows="5" cols="40" class="textArea">{$biography[$formLocale]|escape}</textarea></td>
</tr>

{if $allowRegReader || $allowRegPresenter || $allowRegReviewer}
	<tr valign="top">
		<td class="label">{translate key="user.roles"}</td>
		<td class="value">
			{if $allowRegReader}
				<input type="checkbox" id="readerRole" name="readerRole" {if $isReader}checked="checked" {/if}>&nbsp;{fieldLabel name="readerRole" key="user.role.reader"}<br/>
			{/if}
			{if $allowRegPresenter}
				<input type="checkbox" id="presenterRole" name="presenterRole" {if $isPresenter}checked="checked" {/if}>&nbsp;{fieldLabel name="presenterRole" key="user.role.presenter"}<br/>
			{/if}
			{if $allowRegReviewer}
				<input type="checkbox" id="reviewerRole" name="reviewerRole" {if $isReviewer}checked="checked" {/if}>&nbsp;{fieldLabel name="reviewerRole" key="user.role.reviewer"}<br/>
			{/if}
		</td>
	</tr>
{/if}
{foreach from=$schedConfs name=schedConfNotifications key=thisSchedConfId item=thisSchedConf}
	{assign var=thisSchedConfId value=$thisSchedConf->getSchedConfId()}
	{assign var=notificationEnabled value=`$schedConfNotifications.$thisSchedConfId`}
	{if !$notFirstSchedConf}
		{assign var=notFirstSchedConf value=1}
		<tr valign="top">
			<td class="label">{translate key="user.profile.form.publishedNotifications"}</td>
			<td class="value">
	{/if}

			<input type="checkbox" name="schedConfNotify[]" {if $notificationEnabled}checked="checked" {/if}id="schedConfNotify-{$thisSchedConfId|escape}" value="{$thisSchedConfId|escape}" /> <label for="schedConfNotify-{$thisSchedConfId|escape}">{$thisSchedConf->getFullTitle()|escape}</label><br/>

	{if $smarty.foreach.schedConfNotifications.last}
			</td>
		</tr>
	{/if}
{/foreach}

{if $displayOpenAccessNotification}
	{assign var=notFirstSchedConf value=0}
	{foreach from=$schedConfs name=schedConfOpenAccessNotifications key=thisSchedConfId item=thisSchedConf}
		{assign var=thisSchedConfId value=$thisSchedConf->getSchedConfId()}
		{assign var=enableOpenAccessNotification value=$thisSchedConf->getSetting('enableOpenAccessNotification')}
		{assign var=notificationEnabled value=$user->getSetting('openAccessNotification', $thisSchedConfId)}
		{if !$notFirstSchedConf}
			{assign var=notFirstSchedConf value=1}
			<tr valign="top">
				<td class="label">{translate key="user.profile.form.openAccessNotifications"}</td>
				<td class="value">
		{/if}

		{if $enableOpenAccessNotification}
			<input type="checkbox" name="openAccessNotify[]" {if $notificationEnabled}checked="checked" {/if}id="openAccessNotify-{$thisSchedConfId|escape}" value="{$thisSchedConfId|escape}" /> <label for="openAccessNotify-{$thisSchedConfId|escape}">{$thisSchedConf->getFullTitle()|escape}</label><br/>
		{/if}

		{if $smarty.foreach.schedConfOpenAccessNotifications.last}
				</td>
			</tr>
		{/if}
	{/foreach}
{/if}

</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="user" escape=false}'" /></p>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
