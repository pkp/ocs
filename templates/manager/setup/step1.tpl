{**
 * step1.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 1 of conference setup.
 *
 * $Id$
 *}
{assign var="pageTitle" value="manager.setup.aboutConference.title"}
{include file="manager/setup/setupHeader.tpl"}

<form name="setupForm" method="post" action="{url op="saveSetup" path="1"}">
{include file="common/formErrors.tpl"}

{if count($formLocales) > 1}
<div id="locales">
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"setupFormUrl" op="setup" path="1" escape=false}
			{form_language_chooser form="setupForm" url=$setupFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
</table>
</div>
{/if}
<div id="titleInfo">
<h3>1.1 {translate key="common.title"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="title" key="common.title" required="true"}</td>
		<td width="80%" class="value"><input type="text" name="title[{$formLocale|escape}]" id="title" value="{$title[$formLocale]|escape}" size="30" maxlength="120" class="textField" /></td>
	</tr>
</table>
</div>
<div class="separator"></div>
<div id="descriptionInfo">
<h3><label for="description">1.2 {translate key="manager.setup.aboutConference.conferenceDescription"}</label></h3>
<span class="instruct">{translate key="manager.setup.aboutConference.conferenceDescription.description"}</span>

<textarea name="description[{$formLocale|escape}]" id="description" rows="5" cols="60" class="textArea">{$description[$formLocale]|escape}</textarea>
</div>
<div class="separator"></div>
<div id="principalContact">
<h3>1.3 {translate key="manager.setup.aboutConference.principalContact"}</h3>

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
<div id="copyrightNoticeInfo">
<h3><label for="copyrightNotice">1.4 {translate key="manager.setup.aboutConference.copyrightNotice"}</label></h3>
<p>{translate key="manager.setup.aboutConference.copyrightNotice.description"}</p>

<p><textarea name="copyrightNotice[{$formLocale|escape}]" id="copyrightNotice" rows="10" cols="60" class="textArea">{$copyrightNotice[$formLocale]|escape}</textarea></p>

<p><input type="checkbox" name="copyrightNoticeAgree" id="copyrightNoticeAgree" value="1"{if $copyrightNoticeAgree} checked="checked"{/if} /> {fieldLabel name="copyrightNoticeAgree" key="manager.setup.aboutConference.copyrightNoticeAgree"}<br/>
<input type="checkbox" name="postCreativeCommons" id="postCreativeCommons" value="1"{if $postCreativeCommons} checked="checked"{/if} /> {fieldLabel name="postCreativeCommons" key="manager.setup.aboutConference.postCreativeCommons"}<br/></p>
</div>
<div class="separator"></div>
<div id="archiveAccessPolicyInfo">
<h3>1.5 {translate key="manager.setup.aboutConference.accessPolicy"}</h3>

<p>{translate key="manager.setup.additionalContent.accessPolicy.description"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="radio" name="paperAccess" id="paperAccess-1" value="{$smarty.const.PAPER_ACCESS_OPEN}" {if $paperAccess == PAPER_ACCESS_OPEN || $paperAccess == ""} checked="checked"{/if} /></td>
		<td width="95%" class="value">{fieldLabel name="paperAccess-1" key="manager.setup.additionalContent.archiveAccess.open"}</td>
	</tr>
	<tr valign="top">
		<td class="label"><input type="radio" name="paperAccess" id="paperAccess-2" value="{$smarty.const.PAPER_ACCESS_ACCOUNT_REQUIRED}" {if $paperAccess == PAPER_ACCESS_ACCOUNT_REQUIRED} checked="checked"{/if} /></td>
		<td class="value">{fieldLabel name="paperAccess-2" key="manager.setup.additionalContent.archiveAccess.accountRequired"}</td>
	</tr>
	<tr valign="top">
		<td class="label"><input type="radio" name="paperAccess" id="paperAccess-3" value="{$smarty.const.PAPER_ACCESS_REGISTRATION_REQUIRED}" {if $paperAccess == PAPER_ACCESS_REGISTRATION_REQUIRED} checked="checked"{/if} /></td>
		<td class="value">{fieldLabel name="paperAccess-3" key="manager.setup.additionalContent.archiveAccess.registrationRequired"}</td>
	</tr>
</table>

<p>{translate key="manager.setup.aboutConference.archiveAccessPolicy.description"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="restrictPaperAccess" id="restrictPaperAccess" value="1"{if $restrictPaperAccess} checked="checked"{/if} /></td>
		<td width="95%" colspan="2" class="value">{fieldLabel name="restrictPaperAccess" key="manager.setup.aboutConference.restrictPaperAccess"}</td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="enableComments" id="enableComments" value="1"{if $enableComments} checked="checked"{/if} /></td>
		<td width="95%" colspan="2" class="value">{fieldLabel name="enableComments" key="manager.setup.aboutConference.comments.enable"}</td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label">&nbsp;</td>
		<td width="5%" class="label"><input type="checkbox" name="commentsRequireRegistration" id="commentsRequireRegistration" value="1"{if $commentsRequireRegistration} checked="checked"{/if} /></td>
		<td width="90%" class="value">{fieldLabel name="commentsRequireRegistration" key="manager.setup.aboutConference.comments.requireRegistration"}</td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label">&nbsp;</td>
		<td width="5%" class="label"><input type="checkbox" name="commentsAllowAnonymous" id="commentsAllowAnonymous" value="1"{if $commentsAllowAnonymous} checked="checked"{/if} /></td>
		<td width="90%" class="value">{fieldLabel name="commentsAllowAnonymous" key="manager.setup.aboutConference.comments.allowAnonymous"}</td>
	</tr>
</table>
<div id="policy">
<h4>{translate key="manager.setup.aboutConference.archiveAccessPolicy"}</h4>

<p><textarea name="archiveAccessPolicy[{$formLocale|escape}]" id="archiveAccessPolicy" rows="10" cols="60" class="textArea">{$archiveAccessPolicy[$formLocale]|escape}</textarea></p>
</div>
</div>
<div class="separator"></div>
<div id="privacyStatementInfo">
<h3>1.6 {translate key="manager.setup.aboutConference.privacyStatement"}</h3>

<p><textarea name="privacyStatement[{$formLocale|escape}]" id="privacyStatement" rows="10" cols="60" class="textArea">{$privacyStatement[$formLocale]|escape}</textarea></p>
</div>
<div class="separator"></div>
<div id="addItemtoAboutConference">
<h3>1.7 {translate key="manager.setup.aboutConference.addItemtoAboutConference"}</h3>

<table width="100%" class="data">
{foreach name=customAboutItems from=$customAboutItems[$formLocale] key=aboutId item=aboutItem}
	<tr valign="top">
		<td width="5%" class="label">{fieldLabel name="customAboutItems-$aboutId-title" key="common.title"}</td>
		<td width="95%" class="value"><input type="text" name="customAboutItems[{$formLocale|escape}][{$aboutId|escape}][title]" id="customAboutItems-{$aboutId|escape}-title" value="{$aboutItem.title|escape}" size="40" maxlength="255" class="textField" />{if $smarty.foreach.customAboutItems.total > 1} <input type="submit" name="delCustomAboutItem[{$aboutId|escape}]" value="{translate key="common.delete"}" class="button" />{/if}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="customAboutItems-$aboutId-content" key="manager.setup.aboutConference.aboutItemContent"}</td>
		<td width="80%" class="value"><textarea name="customAboutItems[{$formLocale|escape}][{$aboutId|escape}][content]" id="customAboutItems-{$aboutId|escape}-content" rows="10" cols="40" class="textArea">{$aboutItem.content|escape}</textarea></td>
	</tr>
	{if !$smarty.foreach.customAboutItems.last}
	<tr valign="top">
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
{foreachelse}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="customAboutItems-0-title" key="common.title"}</td>
		<td width="80%" class="value"><input type="text" name="customAboutItems[{$formLocale|escape}][0][title]" id="customAboutItems-0-title" value="" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="customAboutItems-0-content" key="manager.setup.aboutConference.aboutItemContent"}</td>
		<td width="80%" class="value"><textarea name="customAboutItems[{$formLocale|escape}][0][content]" id="customAboutItems-0-content" rows="10" cols="40" class="textArea"></textarea></td>
	</tr>
{/foreach}
</table>

<p><input type="submit" name="addCustomAboutItem" value="{translate key="manager.setup.aboutConference.addAboutItem"}" class="button" /></p>
</div>
<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="setup"}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
