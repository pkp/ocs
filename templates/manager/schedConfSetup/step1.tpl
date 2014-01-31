{**
 * step1.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 1 of scheduled conference setup.
 *
 * $Id$
 *}
{assign var="pageTitle" value="manager.schedConfSetup.details.title"}
{include file="manager/schedConfSetup/setupHeader.tpl"}

<form name="setupForm" method="post" action="{url op="saveSchedConfSetup" path="1"}">
{include file="common/formErrors.tpl"}

{if count($formLocales) > 1}
<div id="locales">
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"setupFormUrl" op="schedConfSetup" path="1" escape=false}
			{form_language_chooser form="setupForm" url=$setupFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
</table>
</div>
{/if}
<div id="description">
<h3>1.1 {translate key="manager.schedConfSetup.details.description"}</h3>

<p><label for="introduction">{translate key="manager.schedConfSetup.details.introduction.description"}</label></p>

<p><textarea name="introduction[{$formLocale|escape}]" id="introduction" rows="10" cols="60" class="textArea">{$introduction[$formLocale]|escape}</textarea></p>

<p><label for="overview">{translate key="manager.schedConfSetup.details.overview.description"}</label></p>

<p><textarea name="overview[{$formLocale|escape}]" id="overview" rows="10" cols="60" class="textArea">{$overview[$formLocale]|escape}</textarea></p>
</div>
<div class="separator"></div>
<div id="location">
<h3>1.2 {translate key="manager.schedConfSetup.details.location"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="locationName" key="manager.schedConfSetup.details.location.nameOf"}</td>
		<td width="80%" class="value"><input type="text" name="locationName" id="locationName" value="{$locationName|escape}" size="30" maxlength="60" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="locationAddress" key="common.mailingAddress"}</td>
		<td class="value"><textarea name="locationAddress" id="locationAddress" rows="3" cols="40" class="textArea">{$locationAddress|escape}</textarea></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="locationCity" key="manager.schedConfSetup.details.location.city"}</td>
		<td width="80%" class="value"><input type="text" name="locationCity" id="locationCity" value="{$locationCity|escape}" size="30" maxlength="60" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="locationCountry" key="common.country"}</td>
		<td class="value">
			<select name="locationCountry" id="locationCountry" class="selectMenu">
				<option value=""></option>
				{html_options options=$countries selected=$locationCountry}
			</select>
		</td>
	</tr>
</table>
</div>
<div class="separator"></div>
<div id="principalContact">
<h3>1.3 {translate key="manager.schedConfSetup.details.principalContact"}</h3>

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
		<td width="80%" class="value"><textarea name="contactAffiliation[{$formLocale|escape}]" id="contactAffiliation" rows="5" cols="40" class="textArea">{$contactAffiliation[$formLocale]|escape}</textarea></td>
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
</div>
<div class="separator"></div>
<div id="technicalSupportContact">
<h3>1.4 {translate key="manager.schedConfSetup.details.technicalSupportContact"}</h3>

<p>{translate key="manager.schedConfSetup.details.technicalSupportContact.description"}</p>

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
</div>
<div class="separator"></div>
<div id="emails">
<h3>1.5 {translate key="manager.schedConfSetup.details.emails"}</h3>
<table width="100%" class="data">
	<tr valign="top"><td colspan="2">{translate key="manager.schedConfSetup.details.emailSignature.description"}<br />&nbsp;</td></tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="emailSignature" key="manager.schedConfSetup.details.emailSignature"}</td>
		<td class="value">
			<textarea name="emailSignature[{$formLocale|escape}]" id="emailSignature" rows="3" cols="60" class="textArea">{$emailSignature[$formLocale]|escape}</textarea>
		</td>
	</tr>
	<tr valign="top"><td colspan="2">&nbsp;</td></tr>
	<tr valign="top"><td colspan="2">{translate key="manager.schedConfSetup.details.emailBounceAddress.description"}<br />&nbsp;</td></tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="envelopeSender" key="manager.schedConfSetup.details.emailBounceAddress"}</td>
		<td width="80%" class="value">
			<input type="text" name="envelopeSender" id="envelopeSender" size="40" maxlength="255" class="textField" {if !$envelopeSenderEnabled}disabled="disabled" value=""{else}value="{$envelopeSender|escape}"{/if} />
			{if !$envelopeSenderEnabled}
			<br />
			<span class="instruct">{translate key="manager.schedConfSetup.details.emailBounceAddressDisabled"}</span>
			{/if}
		</td>
	</tr>
</table>
</div>

<div class="separator"></div>
<div id="sponsors">
<h3>1.6 {translate key="manager.schedConfSetup.details.sponsors"}</h3>

<p>{translate key="manager.schedConfSetup.details.sponsors.description"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="sponsorNote" key="manager.schedConfSetup.details.note"}</td>
		<td width="80%" class="value">
			<textarea name="sponsorNote[{$formLocale|escape}]" id="sponsorNote" rows="5" cols="40" class="textArea">{$sponsorNote[$formLocale]|escape}</textarea>
			<br/>
			<span class="instruct">{translate key="manager.schedConfSetup.details.sponsors.noteDescription"}</span>
		</td>
	</tr>
{foreach name=sponsors from=$sponsors key=sponsorId item=sponsor}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="sponsors-$sponsorId-institution" key="manager.schedConfSetup.details.institution"}</td>
		<td width="80%" class="value">
			<input type="text" name="sponsors[{$sponsorId|escape}][institution]" id="sponsors-{$sponsorId|escape}-institution" value="{$sponsor.institution|escape}" size="40" maxlength="90" class="textField" />
			{if $smarty.foreach.sponsors.total > 1}
				<input type="submit" name="delSponsor[{$sponsorId|escape}]" value="{translate key="common.delete"}" class="button" />
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="sponsors-$sponsorId-address" key="common.mailingAddress"}</td>
		<td width="80%" class="value">
			<textarea name="sponsors[{$sponsorId|escape}][address]" id="sponsors-{$sponsorId|escape}-address" rows="4" cols="35" class="textArea">{$sponsor.address|escape}</textarea>
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="sponsors-$sponsorId-url" key="common.url"}</td>
		<td width="80%" class="value"><input type="text" name="sponsors[{$sponsorId|escape}][url]" id="sponsors-{$sponsorId|escape}-url" value="{$sponsor.url|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	{if !$smarty.foreach.sponsors.last}
	<tr valign="top">
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
{foreachelse}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="sponsors-0-institution" key="manager.schedConfSetup.details.institution"}</td>
		<td width="80%" class="value"><input type="text" name="sponsors[0][institution]" id="sponsors-0-institution" size="40" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="sponsors-0-address" key="common.mailingAddress"}</td>
		<td width="80%" class="value"><textarea name="sponsors[0][address]" id="sponsors-0-address" rows="4" cols="35" class="textArea">{$sponsors[0][address]|escape}</textarea>
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="sponsors-0-url" key="common.url"}</td>
		<td width="80%" class="value"><input type="text" name="sponsors[0][url]" id="sponsors-0-url" size="40" maxlength="255" class="textField" /></td>
	</tr>
{/foreach}
</table>
</div>
<p><input type="submit" name="addSponsor" value="{translate key="manager.schedConfSetup.details.addSponsor"}" class="button" /></p>

<div class="separator"></div>

<h3>1.7 {translate key="manager.schedConfSetup.details.contributors"}</h3>

<p>{translate key="manager.schedConfSetup.details.contributors.description"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contributorNote" key="manager.schedConfSetup.details.note"}</td>
		<td width="80%" class="value">
			<textarea name="contributorNote[{$formLocale|escape}]" id="contributorNote" rows="5" cols="40" class="textArea">{$contributorNote[$formLocale]|escape}</textarea>
			<br/>
			<span class="instruct">{translate key="manager.schedConfSetup.details.contributors.noteDescription"}</span>
		</td>
	</tr>
{foreach name=contributors from=$contributors key=contributorId item=contributor}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contributors-$contributorId-name" key="manager.schedConfSetup.details.contributor"}</td>
		<td width="80%" class="value"><input type="text" name="contributors[{$contributorId|escape}][name]" id="contributors-{$contributorId|escape}-name" value="{$contributor.name|escape}" size="40" maxlength="90" class="textField" />{if $smarty.foreach.contributors.total > 1} <input type="submit" name="delContributor[{$contributorId|escape}]" value="{translate key="common.delete"}" class="button" />{/if}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contributors-$contributorId-url" key="common.url"}</td>
		<td width="80%" class="value"><input type="text" name="contributors[{$contributorId|escape}][url]" id="contributors-{$contributorId|escape}-url" value="{$contributor.url|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	{if !$smarty.foreach.contributors.last}
	<tr valign="top">
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
{foreachelse}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contributors-0-name" key="manager.schedConfSetup.details.contributor"}</td>
		<td width="80%" class="value"><input type="text" name="contributors[0][name]" id="contributors-0-name" size="40" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contributors-0-url" key="common.url"}</td>
		<td width="80%" class="value"><input type="text" name="contributors[0][url]" id="contributors-0-url" size="40" maxlength="255" class="textField" /></td>
	</tr>
{/foreach}
</table>

<p><input type="submit" name="addContributor" value="{translate key="manager.schedConfSetup.details.addContributor"}" class="button" /></p>

<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="schedConfSetup"}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
