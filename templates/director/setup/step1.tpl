{**
 * step1.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 1 of conference setup.
 *
 * $Id$
 *}

{assign var="pageTitle" value="director.setup.gettingDownTheDetails}
{include file="director/setup/setupHeader.tpl"}

<br /><span class="instruct">{translate key="director.setup.conferenceSetupNotes"}</span>

<form method="post" action="{url op="saveSetup" path="1"}">
{include file="common/formErrors.tpl"}

<h3>1.1 {translate key="director.setup.generalInformation"}</h3>
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="conferenceTitle" required="true" key="director.setup.conferenceTitle"}</td>
		<td width="80%" class="value">
			<input type="text" name="conferenceTitle" id="conferenceTitle" value="{$conferenceTitle|escape}" size="40" maxlength="120" class="textField" />
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="conferenceAcronym" required="true" key="director.setup.conferenceAcronym"}</td>
		<td width="80%" class="value">
			<input type="text" name="conferenceAcronym" id="conferenceAcronym" value="{$conferenceAcronym|escape}" size="8" maxlength="16" class="textField" />
		</td>
	</tr>
</table>

<div class="separator"></div>

<h3>1.2 {translate key="director.setup.conferenceDescription"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="conferenceIntroduction" key="director.setup.conferenceIntroduction"}</td>
		<td width="80%" class="value">
			<textarea name="conferenceIntroduction" id="conferenceIntroduction" rows="5" cols="60" class="textArea">{$conferenceIntroduction|escape}</textarea>
			<br />
			<span class="instruct">{translate key="director.setup.conferenceIntroductionDescription"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="conferenceOverview" key="director.setup.conferenceOverview"}</td>
		<td width="80%" class="value">
			<textarea name="conferenceOverview" id="conferenceOverview" rows="15" cols="60" class="textArea">{$conferenceOverview|escape}</textarea>
			<br />
			<span class="instruct">{translate key="director.setup.conferenceOverviewDescription"}</span>
		</td>
	</tr>
</table>

<div class="separator"></div>


<h3>1.3 {translate key="director.setup.principalContact"}</h3>

<p>{translate key="director.setup.principalContactDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contactName" key="user.name" required="true"}</td>
		<td width="80%" class="value"><input type="text" name="contactName" id="contactName" value="{$contactName|escape}" size="30" maxlength="60" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contactTitle" key="user.title"}</td>
		<td width="80%" class="value"><input type="text" name="contactTitle" id="contactTitle" value="{$contactTitle|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>	
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contactAffiliation" key="user.affiliation"}</td>
		<td width="80%" class="value"><input type="text" name="contactAffiliation" id="contactAffiliation" value="{$contactAffiliation|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contactEmail" key="user.email" required="true"}</td>
		<td width="80%" class="value"><input type="text" name="contactEmail" id="contactEmail" value="{$contactEmail|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contactPhone" key="user.phone"}</td>
		<td width="80%" class="value"><input type="text" name="contactPhone" id="contactPhone" value="{$contactPhone|escape}" size="15" maxlength="24" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contactFax" key="user.fax"}</td>
		<td width="80%" class="value"><input type="text" name="contactFax" id="contactFax" value="{$contactFax|escape}" size="15" maxlength="24" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contactMailingAddress" key="common.mailingAddress"}</td>
		<td width="80%" class="value"><textarea name="contactMailingAddress" id="contactMailingAddress" rows="3" cols="40" class="textArea">{$contactMailingAddress|escape}</textarea></td>
	</tr>
</table>


<div class="separator"></div>


<h3>1.4 {translate key="director.setup.technicalSupportContact"}</h3>

<p>{translate key="director.setup.technicalSupportContactDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="supportName" key="user.name" required="true"}</td>
		<td width="80%" class="value"><input type="text" name="supportName" id="supportName" value="{$supportName|escape}" size="30" maxlength="60" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="supportEmail" key="user.email" required="true"}</td>
		<td width="80%" class="value"><input type="text" name="supportEmail" id="supportEmail" value="{$supportEmail|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="supportPhone" key="user.phone"}</td>
		<td width="80%" class="value"><input type="text" name="supportPhone" id="supportPhone" value="{$supportPhone|escape}" size="15" maxlength="24" class="textField" /></td>
	</tr>
</table>

<div class="separator"></div>

<h3>1.5 {translate key="director.setup.emails"}</h3>
<table width="100%" class="data">
	<tr valign="top"><td colspan="2">{translate key="director.setup.emailSignatureDescription"}<br />&nbsp;</td></tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="emailSignature" key="director.setup.emailSignature"}</td>
		<td class="value">
			<textarea name="emailSignature" id="emailSignature" rows="3" cols="60" class="textArea">{$emailSignature|escape}</textarea>
		</td>
	</tr>
	<tr valign="top"><td colspan="2">&nbsp;</td></tr>
	<tr valign="top"><td colspan="2">{translate key="director.setup.emailBounceAddressDescription"}<br />&nbsp;</td></tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="envelopeSender" key="director.setup.emailBounceAddress"}</td>
		<td width="80%" class="value">
			<input type="text" name="envelopeSender" id="envelopeSender" size="40" maxlength="255" class="textField" {if !$envelopeSenderEnabled}disabled="disabled" value=""{else}value="{$envelopeSender|escape}"{/if} />
			{if !$envelopeSenderEnabled}
			<br />
			<span class="instruct">{translate key="director.setup.emailBounceAddressDisabled"}</span>
			{/if}
		</td>
	</tr>
</table>


<div class="separator"></div>


<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="setup" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
