{**
 * step4.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 4 of conference setup.
 *
 * $Id$
 *}

{assign var="pageTitle" value="director.setup.managingTheConference}
{include file="director/eventSetup/setupHeader.tpl"}

<form method="post" action="{url op="saveEventSetup" path="4"}" enctype="multipart/form-data">
{include file="common/formErrors.tpl"}

<h3>4.1 {translate key="director.setup.onlineAccessManagement"}</h3>

	<script type="text/javascript">
		{literal}
		<!--
			function toggleEnableRegistration(form) {
				if (form.enableRegistration[0].checked) {
					form.requireRegReader.disabled = false;
					form.registrationName.disabled = true;
					form.registrationEmail.disabled = true;
					form.registrationPhone.disabled = true;
					form.registrationFax.disabled = true;
					form.registrationMailingAddress.disabled = true;
				} else {
					form.requireRegReader.disabled = true;
					form.registrationName.disabled = false;
					form.registrationEmail.disabled = false;
					form.registrationPhone.disabled = false;
					form.registrationFax.disabled = false;
					form.registrationMailingAddress.disabled = false;
				}
			}
		// -->
		{/literal}
	</script>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label" align="right">
			<input type="radio" name="enableRegistration" id="enableRegistration-0" value="0" onclick="toggleEnableRegistration(this.form)"{if not $enableRegistration} checked="checked"{/if} />
		</td>
		<td width="95%" class="value">
			<label for="enableRegistration-0"><strong>{translate key="director.setup.readerAccess"}</strong></label>
			<table width="100%" class="data">
				<tr valign="top">
					<td width="10%" class="value"><input type="checkbox" name="requireRegReader" id="requireRegReader"{if $enableRegistration} disabled="disabled"{/if} {if $requireRegReader}checked="true"{/if} /></td>
					<td width="90%" class="label">{fieldLabel name="requireRegReader" key="director.setup.requireRegReader"}</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label" align="right">
			<input type="radio" name="enableRegistration" id="enableRegistration-1" value="1" onclick="toggleEnableRegistration(this.form)"{if $enableRegistration} checked="checked"{/if} />
		</td>
		<td width="95%" class="value">
			<label for="enableRegistration-1"><strong>{translate key="director.setup.registrantAccess"}</strong></label>
		</td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label" align="right">
		</td>
		<td width="95%" class="value">
			<h4>{translate key="director.setup.openAccessPolicy"}</h4>
			<p><textarea name="openAccessPolicy" id="openAccessPolicy" rows="12" cols="60" class="textArea">{$openAccessPolicy|escape}</textarea></p>
		</td>
	</tr>
</table>


<div class="separator"></div>


<h3>4.2 {translate key="director.setup.conferenceRegistration"}</h3>

<p><span class="instruct">{translate key="director.setup.conferenceRegistrationDescription"}</span></p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="registrationName" key="user.name"}</td>
		<td width="80%" class="value"><input type="text" name="registrationName" id="registrationName"{if not $enableRegistration} disabled="disabled"{/if} value="{$registrationName|escape}" size="30" maxlength="60" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="registrationEmail" key="user.email"}</td>
		<td width="80%" class="value"><input type="text" name="registrationEmail" id="registrationEmail"{if not $enableRegistration} disabled="disabled"{/if} value="{$registrationEmail|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="registrationPhone" key="user.phone"}</td>
		<td width="80%" class="value"><input type="text" name="registrationPhone" id="registrationPhone"{if not $enableRegistration} disabled="disabled"{/if} value="{$registrationPhone|escape}" size="15" maxlength="24" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="registrationFax" key="user.fax"}</td>
		<td width="80%" class="value"><input type="text" name="registrationFax" id="registrationFax"{if not $enableRegistration} disabled="disabled"{/if} value="{$registrationFax|escape}" size="15" maxlength="24" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="registrationMailingAddress" key="common.mailingAddress"}</td>
		<td width="80%" class="value"><textarea name="registrationMailingAddress" id="registrationMailingAddress"{if not $enableRegistration} disabled="disabled"{/if} rows="3" cols="40" class="textArea">{$registrationMailingAddress|escape}</textarea></td>
	</tr>
</table>


<div class="separator"></div>


<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="eventSetup" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
