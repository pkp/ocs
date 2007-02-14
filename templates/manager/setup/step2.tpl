{**
 * step2.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 2 of conference setup.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.setup.conferencePolicies}
{include file="manager/setup/setupHeader.tpl"}

<form method="post" action="{url op="saveSetup" path="2"}">
{include file="common/formErrors.tpl"}

<h3>2.1 {translate key="manager.setup.reviewPolicy"}</h3>

<p>{translate key="manager.setup.reviewDescription"}</p>

<h4>{translate key="manager.setup.reviewPolicy"}</h4>

<p><textarea name="reviewPolicy" id="reviewPolicy" rows="12" cols="60" class="textArea">{$reviewPolicy|escape}</textarea></p>


<h4>{translate key="manager.setup.reviewGuidelines"}</h4>

<p>{translate key="manager.setup.reviewGuidelinesDescription"}</p>

<p><textarea name="reviewGuidelines" id="reviewGuidelines" rows="12" cols="60" class="textArea">{$reviewGuidelines|escape}</textarea></p>

<div class="separator"></div>


<h3>2.2 {translate key="manager.setup.presenterGuidelines"}</h3>

<p>{translate key="manager.setup.presenterGuidelinesDescription"}</p>

<p>
	<textarea name="presenterGuidelines" id="presenterGuidelines" rows="12" cols="60" class="textArea">{$presenterGuidelines|escape}</textarea>
	<br />
	<span class="instruct">{translate key="manager.setup.htmlSetupInstructions"}</span>
</p>

<h4>{translate key="manager.setup.submissionPreparationChecklist"}</h4>

<p>{translate key="manager.setup.submissionPreparationChecklistDescription"}</p>

{foreach name=checklist from=$submissionChecklist key=checklistId item=checklistItem}
	{if !$notFirstChecklistItem}
		{assign var=notFirstChecklistItem value=1}
		<table width="100%" class="data">
			<tr valign="top">
				<td width="5%">{translate key="common.order"}</td>
				<td width="95%" colspan="2">&nbsp;</td>
			</tr>
	{/if}

	<tr valign="top">
		<td width="5%" class="label"><input type="text" name="submissionChecklist[{$checklistId}][order]" value="{$checklistItem.order|escape}" size="3" maxlength="2" class="textField" /></td>
		<td class="value"><textarea name="submissionChecklist[{$checklistId}][content]" rows="3" cols="40" class="textArea">{$checklistItem.content|escape}</textarea></td>
		<td width="100%"><input type="submit" name="delChecklist[{$checklistId}]" value="{translate key="common.delete"}" class="button" /></td>
	</tr>
{/foreach}
</table>

<p><input type="submit" name="addChecklist" value="{translate key="manager.setup.addChecklistItem"}" class="button" /></p>


<div class="separator"></div>

<h3>2.3 {translate key="manager.setup.presenterCopyrightNotice"}</h3>

<p>{translate key="manager.setup.presenterCopyrightNoticeDescription"}</p>

<p><textarea name="copyrightNotice" id="copyrightNotice" rows="12" cols="60" class="textArea">{$copyrightNotice|escape}</textarea></p>

<p><input type="checkbox" name="copyrightNoticeAgree" id="copyrightNoticeAgree" value="1"{if $copyrightNoticeAgree} checked="checked"{/if} /> <label for="copyrightNoticeAgree">{translate key="manager.setup.presenterCopyrightNoticeAgree"}</label></p>


<div class="separator"></div>


<h3>2.4 {translate key="manager.setup.privacyStatement"}</h3>

<p><textarea name="privacyStatement" id="privacyStatement" rows="12" cols="60" class="textArea">{$privacyStatement|escape}</textarea></p>


<div class="separator"></div>


<h3>2.5 {translate key="manager.setup.addItemtoAboutConference"}</h3>

<table width="100%" class="data">
{foreach name=customAboutItems from=$customAboutItems key=aboutId item=aboutItem}
	<tr valign="top">
		<td width="5%" class="label">{fieldLabel name="customAboutItems-$aboutId-title" key="common.title"}</td>
		<td width="95%" class="value"><input type="text" name="customAboutItems[{$aboutId}][title]" id="customAboutItems-{$aboutId}-title" value="{$aboutItem.title|escape}" size="40" maxlength="255" class="textField" />{if $smarty.foreach.customAboutItems.total > 1} <input type="submit" name="delCustomAboutItem[{$aboutId}]" value="{translate key="common.delete"}" class="button" />{/if}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="customAboutItems-$aboutId-content" key="manager.setup.aboutItemContent"}</td>
		<td width="80%" class="value"><textarea name="customAboutItems[{$aboutId}][content]" id="customAboutItems-{$aboutId}-content" rows="12" cols="40" class="textArea">{$aboutItem.content|escape}</textarea></td>
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
		<td width="20%" class="label">{fieldLabel name="customAboutItems-0-content" key="manager.setup.aboutItemContent"}</td>
		<td width="80%" class="value"><textarea name="customAboutItems[0][content]" id="customAboutItems-0-content" rows="12" cols="40" class="textArea"></textarea></td>
	</tr>
{/foreach}
</table>

<p><input type="submit" name="addCustomAboutItem" value="{translate key="manager.setup.addAboutItem"}" class="button" /></p>

<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="setup" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
