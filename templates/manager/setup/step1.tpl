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

{assign var="pageTitle" value="manager.setup.aboutConference.title}
{include file="manager/setup/setupHeader.tpl"}

<br /><span class="instruct">{translate key="manager.setup.conferenceSetupNotes"}</span>

<form method="post" action="{url op="saveSetup" path="1"}">
{include file="common/formErrors.tpl"}

<h3><label for="conferenceDescription">1.1 {translate key="manager.setup.aboutConference.conferenceDescription"}</label></h3>
<span class="instruct">{translate key="manager.setup.aboutConference.conferenceDescription.description"}</span>

<textarea name="conferenceDescription" id="conferenceDescription" rows="5" cols="60" class="textArea">{$conferenceDescription|escape}</textarea>

<div class="separator"></div>

<h3>1.2 {translate key="manager.setup.aboutConference.principalContact"}</h3>

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

<h3><label for="copyrightNotice">1.3 {translate key="manager.setup.aboutConference.copyrightNotice"}</label></h3>
<p>{translate key="manager.setup.aboutConference.copyrightNotice.description"}</p>

<p><textarea name="copyrightNotice" id="copyrightNotice" rows="10" cols="60" class="textArea">{$copyrightNotice|escape}</textarea></p>

<p><input type="checkbox" name="copyrightNoticeAgree" id="copyrightNoticeAgree" value="1"{if $copyrightNoticeAgree} checked="checked"{/if} /> <label for="copyrightNoticeAgree">{translate key="manager.setup.aboutConference.copyrightNoticeAgree"}</label><br/>
<input type="checkbox" name="postCreativeCommons" id="postCreativeCommons" value="1"{if $postCreativeCommons} checked="checked"{/if} /> <label for="postCreativeCommons">{translate key="manager.setup.aboutConference.postCreativeCommons"}</label><br/></p>

<div class="separator"></div>

<h3>1.4 {translate key="manager.setup.aboutConference.archiveAccessPolicy"}</h3>
<p>{translate key="manager.setup.aboutConference.archiveAccessPolicy.description"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="restrictSiteAccess" id="restrictSiteAccess" value="1"{if $restrictSiteAccess} checked="checked"{/if} /></td>
		<td width="95%" colspan="2" class="value"><label for="restrictSiteAccess">{translate key="manager.setup.aboutConference.restrictSiteAccess"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="enableComments" id="enableComments" value="1"{if $enableComments} checked="checked"{/if} /></td>
		<td width="95%" colspan="2" class="value"><label for="enableComments">{translate key="manager.setup.aboutConference.comments.enable"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label">&nbsp;</td>
		<td width="5%" class="label"><input type="checkbox" name="commentsRequireRegistration" id="commentsRequireRegistration" value="1"{if $commentsRequireRegistration} checked="checked"{/if} /></td>
		<td width="90%" class="value"><label for="commentsRequireRegistration">{translate key="manager.setup.aboutConference.comments.requireRegistration"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label">&nbsp;</td>
		<td width="5%" class="label"><input type="checkbox" name="commentsAllowAnonymous" id="commentsAllowAnonymous" value="1"{if $commentsAllowAnonymous} checked="checked"{/if} /></td>
		<td width="90%" class="value"><label for="commentsAllowAnonymous">{translate key="manager.setup.aboutConference.comments.allowAnonymous"}</label></td>
	</tr>
</table>

<p><textarea name="archiveAccessPolicy" id="archiveAccessPolicy" rows="10" cols="60" class="textArea">{$archiveAccessPolicy|escape}</textarea></p>

<div class="separator"></div>

<h3>1.5 {translate key="manager.setup.aboutConference.privacyStatement"}</h3>

<p><textarea name="privacyStatement" id="privacyStatement" rows="10" cols="60" class="textArea">{$privacyStatement|escape}</textarea></p>

<div class="separator"></div>

<h3>1.6 {translate key="manager.setup.aboutConference.addItemtoAboutConference"}</h3>

<table width="100%" class="data">
{foreach name=customAboutItems from=$customAboutItems key=aboutId item=aboutItem}
	<tr valign="top">
		<td width="5%" class="label">{fieldLabel name="customAboutItems-$aboutId-title" key="common.title"}</td>
		<td width="95%" class="value"><input type="text" name="customAboutItems[{$aboutId}][title]" id="customAboutItems-{$aboutId}-title" value="{$aboutItem.title|escape}" size="40" maxlength="255" class="textField" />{if $smarty.foreach.customAboutItems.total > 1} <input type="submit" name="delCustomAboutItem[{$aboutId}]" value="{translate key="common.delete"}" class="button" />{/if}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="customAboutItems-$aboutId-content" key="manager.setup.aboutConference.aboutItemContent"}</td>
		<td width="80%" class="value"><textarea name="customAboutItems[{$aboutId}][content]" id="customAboutItems-{$aboutId}-content" rows="10" cols="40" class="textArea">{$aboutItem.content|escape}</textarea></td>
	</tr>
	{if !$smarty.foreach.customAboutItems.last}
	<tr valign="top">
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
{foreachelse}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="customAboutItems-0-title" key="common.title"}</td>
		<td width="80%" class="value"><input type="text" name="customAboutItems[0][title]" id="customAboutItems-0-title" value="" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="customAboutItems-0-content" key="manager.setup.aboutConference.aboutItemContent"}</td>
		<td width="80%" class="value"><textarea name="customAboutItems[0][content]" id="customAboutItems-0-content" rows="10" cols="40" class="textArea"></textarea></td>
	</tr>
{/foreach}
</table>

<p><input type="submit" name="addCustomAboutItem" value="{translate key="manager.setup.aboutConference.addAboutItem"}" class="button" /></p>

<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="setup" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
