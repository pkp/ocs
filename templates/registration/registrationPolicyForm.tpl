{**
 * registrationPolicyForm.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Setup registration policies.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="manager.registrationPolicies"}
{assign var="pageId" value="manager.registrationPolicies"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li><a href="{url op="registration" clearPageContext=1}">{translate key="manager.registration"}</a></li>
	<li><a href="{url op="registrationTypes" clearPageContext=1}">{translate key="manager.registrationTypes"}</a></li>
	<li class="current"><a href="{url op="registrationPolicies"}">{translate key="manager.registrationPolicies"}</a></li>
	<li><a href="{url op="registrationOptions"}">{translate key="manager.registrationOptions"}</a></li>
</ul>

<form name="registrationPolicies" method="post" action="{url op="saveRegistrationPolicies"}">
{include file="common/formErrors.tpl"}

	<script type="text/javascript">
		{literal}
		<!--
			function toggleAllowSetBeforeMonthsReminder(form) {
				form.numMonthsBeforeRegistrationExpiryReminder.disabled = !form.numMonthsBeforeRegistrationExpiryReminder.disabled;
			}
			function toggleAllowSetBeforeWeeksReminder(form) {
				form.numWeeksBeforeRegistrationExpiryReminder.disabled = !form.numWeeksBeforeRegistrationExpiryReminder.disabled;
			}
			function toggleAllowSetAfterMonthsReminder(form) {
				form.numMonthsAfterRegistrationExpiryReminder.disabled = !form.numMonthsAfterRegistrationExpiryReminder.disabled;
			}
			function toggleAllowSetAfterWeeksReminder(form) {
				form.numWeeksAfterRegistrationExpiryReminder.disabled = !form.numWeeksAfterRegistrationExpiryReminder.disabled;
			}
		// -->
		{/literal}
	</script>
<div id="registrationContact">
<h3>{translate key="manager.registrationPolicies.registrationContact"}</h3>
<p>{translate key="manager.registrationPolicies.registrationContactDescription"}</p>
<table width="100%" class="data">
{if count($formLocales) > 1}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"registrationPoliciesUrl" op="registrationPolicies" escape=false}
			{form_language_chooser form="registrationPolicies" url=$registrationPoliciesUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
{/if}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="registrationName" key="user.name"}</td>
		<td width="80%" class="value"><input type="text" name="registrationName" id="registrationName" value="{$registrationName|escape}" size="30" maxlength="60" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="registrationEmail" key="user.email"}</td>
		<td width="80%" class="value"><input type="text" name="registrationEmail" id="registrationEmail" value="{$registrationEmail|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="registrationPhone" key="user.phone"}</td>
		<td width="80%" class="value"><input type="text" name="registrationPhone" id="registrationPhone" value="{$registrationPhone|escape}" size="15" maxlength="24" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="registrationFax" key="user.fax"}</td>
		<td width="80%" class="value"><input type="text" name="registrationFax" id="registrationFax" value="{$registrationFax|escape}" size="15" maxlength="24" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="registrationMailingAddress" key="common.mailingAddress"}</td>
		<td width="80%" class="value"><textarea name="registrationMailingAddress" id="registrationMailingAddress" rows="6" cols="40" class="textArea">{$registrationMailingAddress|escape}</textarea></td>
	</tr>
</table>
</div>

<div class="separator"></div>

<div id="registrationAdditionalInformationInfo">
<h3>{translate key="manager.registrationPolicies.registrationAdditionalInformation"}</h3>
<p>{translate key="manager.registrationPolicies.registrationAdditionalInformationDescription"}</p>
<p>
	<textarea name="registrationAdditionalInformation[{$formLocale|escape}]" id="registrationAdditionalInformation" rows="12" cols="60" class="textArea">{$registrationAdditionalInformation[$formLocale]|escape}</textarea>
	<br />
	<span class="instruct">{translate key="manager.registrationPolicies.htmlInstructions"}</span>
</p>
</div>

<div class="separator"></div>

<div id="expiryReminders">
<h3>{translate key="manager.registrationPolicies.expiryReminders"}</h3>
<p>{translate key="manager.registrationPolicies.expiryRemindersDescription"}</p>

<p>
	<input type="checkbox" name="enableRegistrationExpiryReminderBeforeMonths" id="enableRegistrationExpiryReminderBeforeMonths" value="1" onclick="toggleAllowSetBeforeMonthsReminder(this.form)"{if !$scheduledTasksEnabled} disabled="disabled" {elseif $enableRegistrationExpiryReminderBeforeMonths} checked="checked"{/if} />&nbsp;
	<label for="enableRegistrationExpiryReminderBeforeMonths">{translate key="manager.registrationPolicies.expiryReminderBeforeMonths1"}</label>
	<select name="numMonthsBeforeRegistrationExpiryReminder" id="numMonthsBeforeRegistrationExpiryReminder" class="selectMenu"{if not $enableRegistrationExpiryReminderBeforeMonths || !$scheduledTasksEnabled} disabled="disabled"{/if}>{html_options options=$validNumMonthsBeforeExpiry selected=$numMonthsBeforeRegistrationExpiryReminder}</select>
	{translate key="manager.registrationPolicies.expiryReminderBeforeMonths2"}
</p>
<p>
	<input type="checkbox" name="enableRegistrationExpiryReminderBeforeWeeks" id="enableRegistrationExpiryReminderBeforeWeeks" value="1" onclick="toggleAllowSetBeforeWeeksReminder(this.form)"{if !$scheduledTasksEnabled} disabled="disabled" {elseif $enableRegistrationExpiryReminderBeforeWeeks} checked="checked"{/if} />&nbsp;
	<label for="enableRegistrationExpiryReminderBeforeWeeks">{translate key="manager.registrationPolicies.expiryReminderBeforeWeeks1"}</label>
	<select name="numWeeksBeforeRegistrationExpiryReminder" id="numWeeksBeforeRegistrationExpiryReminder" class="selectMenu"{if not $enableRegistrationExpiryReminderBeforeWeeks || !$scheduledTasksEnabled} disabled="disabled"{/if}>{html_options options=$validNumWeeksBeforeExpiry selected=$numWeeksBeforeRegistrationExpiryReminder}</select>
	{translate key="manager.registrationPolicies.expiryReminderBeforeWeeks2"}
</p>
<p>
	<input type="checkbox" name="enableRegistrationExpiryReminderAfterWeeks" id="enableRegistrationExpiryReminderAfterWeeks" value="1" onclick="toggleAllowSetAfterWeeksReminder(this.form)"{if !$scheduledTasksEnabled} disabled="disabled" {elseif $enableRegistrationExpiryReminderAfterWeeks} checked="checked"{/if} />&nbsp;
	<label for="enableRegistrationExpiryReminderAfterWeeks">{translate key="manager.registrationPolicies.expiryReminderAfterWeeks1"}</label>
	<select name="numWeeksAfterRegistrationExpiryReminder" id="numWeeksAfterRegistrationExpiryReminder" class="selectMenu"{if not $enableRegistrationExpiryReminderAfterWeeks || !$scheduledTasksEnabled} disabled="disabled"{/if}>{html_options options=$validNumWeeksAfterExpiry selected=$numWeeksAfterRegistrationExpiryReminder}</select>
	{translate key="manager.registrationPolicies.expiryReminderAfterWeeks2"}
</p>
<p>
	<input type="checkbox" name="enableRegistrationExpiryReminderAfterMonths" id="enableRegistrationExpiryReminderAfterMonths" value="1" onclick="toggleAllowSetAfterMonthsReminder(this.form)"{if !$scheduledTasksEnabled} disabled="disabled" {elseif $enableRegistrationExpiryReminderAfterMonths} checked="checked"{/if} />&nbsp;
	<label for="enableRegistrationExpiryReminderAfterMonths">{translate key="manager.registrationPolicies.expiryReminderAfterMonths1"}</label>
	<select name="numMonthsAfterRegistrationExpiryReminder" id="numMonthsAfterRegistrationExpiryReminder" class="selectMenu"{if not $enableRegistrationExpiryReminderAfterMonths || !$scheduledTasksEnabled} disabled="disabled"{/if}>{html_options options=$validNumMonthsAfterExpiry selected=$numMonthsAfterRegistrationExpiryReminder}</select>
	{translate key="manager.registrationPolicies.expiryReminderAfterMonths2"}
</p>

{if !$scheduledTasksEnabled}
	<br/>
	{translate key="manager.registrationPolicies.expiryRemindersDisabled"}
{/if}
</div>

<div class="separator"></div>

<div id="openAccessOptions">
<h3>{translate key="manager.registrationPolicies.openAccessOptions"}</h3>
<p>{translate key="manager.registrationPolicies.openAccessOptionsDescription"}</p>

	<h4>{translate key="manager.registrationPolicies.delayedOpenAccess"}</h4>
	<p>
	<input type="checkbox" name="enableOpenAccessNotification" id="enableOpenAccessNotification" value="1"{if !$scheduledTasksEnabled} disabled="disabled" {elseif $enableOpenAccessNotification} checked="checked"{/if} />&nbsp;
	<label for="enableOpenAccessNotification">{translate key="manager.registrationPolicies.openAccessNotificationDescription"}</label>
	{if !$scheduledTasksEnabled}
		<br/>
		{translate key="manager.registrationPolicies.openAccessNotificationDisabled"}
	{/if}
	</p>

	<p>{translate key="manager.registrationPolicies.delayedOpenAccessPolicyDescription"}</p>
	<p>
	<textarea name="delayedOpenAccessPolicy[{$formLocale|escape}]" id="delayedOpenAccessPolicy" rows="12" cols="60" class="textArea">{$delayedOpenAccessPolicy[$formLocale]|escape}</textarea>
	<br />
	<span class="instruct">{translate key="manager.registrationPolicies.htmlInstructions"}</span>
	</p>

	<h4>{translate key="manager.registrationPolicies.authorSelfArchive"}</h4>
	<p>
	<input type="checkbox" name="enableAuthorSelfArchive" id="enableAuthorSelfArchive" value="1"{if $enableAuthorSelfArchive} checked="checked"{/if} />&nbsp;
	<label for="enableAuthorSelfArchive">{translate key="manager.registrationPolicies.authorSelfArchiveDescription"}</label>
	</p>
	<p>
	<textarea name="authorSelfArchivePolicy[{$formLocale|escape}]" id="authorSelfArchivePolicy" rows="12" cols="60" class="textArea">{$authorSelfArchivePolicy[$formLocale]|escape}</textarea>
	<br />
	<span class="instruct">{translate key="manager.registrationPolicies.htmlInstructions"}</span>
	</p>
</div>

<div class="separator"></div>


<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="registrationPolicies"}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
