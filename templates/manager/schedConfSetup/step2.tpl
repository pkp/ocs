{**
 * step3.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 3 of conference setup.
 *
 * $Id$
 *}
{assign var="pageTitle" value="manager.schedConfSetup.submissions.title"}
{include file="manager/schedConfSetup/setupHeader.tpl"}

<form name="setupForm" method="post" action="{url op="saveSchedConfSetup" path="2"}">
{* For up/down/delete buttons for paper types, it's necessary to perform a
   form submit so that data is kept, but it's not desirable to use buttons
   from a UI perspective. Use two hidden form parameters instead. *}
<input type="hidden" name="paperTypeAction" value="" />
<input type="hidden" name="paperTypeId" value="" />
{include file="common/formErrors.tpl"}

{if count($formLocales) > 1}
<div id="locales">
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"setupFormUrl" op="schedConfSetup" path="2" escape=false}
			{form_language_chooser form="setupForm" url=$setupFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
</table>
</div>
{/if}

<div id="submissionProcess">
<h3>2.1 {translate key="manager.schedConfSetup.submissions.submissionProcess"}</h3>

<p>{translate key="manager.schedConfSetup.submissions.description"}</p>

<h4>{translate key="manager.schedConfSetup.submissions.submissionMaterials"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label">
			<input type="radio" name="reviewMode" id="reviewMode-1" value="{$smarty.const.REVIEW_MODE_ABSTRACTS_ALONE}" {if $reviewMode == REVIEW_MODE_ABSTRACTS_ALONE}checked="checked"{/if} onclick="document.setupForm.previewAbstracts.disabled=true;" />
		</td>
		<td width="95%" class="value">
			{fieldLabel name="reviewMode-1" key="manager.schedConfSetup.submissions.abstractsAlone"}
		</td>
	</tr>
	<tr valign="top">
		<td class="label">
			<input type="radio" name="reviewMode" id="reviewMode-2" value="{$smarty.const.REVIEW_MODE_PRESENTATIONS_ALONE}" {if $reviewMode == REVIEW_MODE_PRESENTATIONS_ALONE}checked="checked"{/if} onclick="document.setupForm.previewAbstracts.disabled=true;" />
		</td>
		<td class="value">
			{fieldLabel name="reviewMode-2" key="manager.schedConfSetup.submissions.presentationsAlone"}
		</td>
	</tr>
	<tr valign="top">
		<td class="label">
			<input type="radio" name="reviewMode" id="reviewMode-3" value="{$smarty.const.REVIEW_MODE_BOTH_SIMULTANEOUS}" {if $reviewMode == REVIEW_MODE_BOTH_SIMULTANEOUS}checked="checked"{/if} onclick="document.setupForm.previewAbstracts.disabled=true;" />
		</td>
		<td class="value">
			{fieldLabel name="reviewMode-3" key="manager.schedConfSetup.submissions.bothTogether"}
		</td>
	</tr>
	<tr valign="top">
		<td class="label">
			<input type="radio" name="reviewMode" id="reviewMode-4" value="{$smarty.const.REVIEW_MODE_BOTH_SEQUENTIAL}" {if $reviewMode == REVIEW_MODE_BOTH_SEQUENTIAL}checked="checked"{/if} onclick="document.setupForm.previewAbstracts.disabled=false;" />
		</td>
		<td class="value">
			{fieldLabel name="reviewMode-4" key="manager.schedConfSetup.submissions.bothSequential"}
		</td>
	</tr>
	<tr valign="top">
		<td class="label">&nbsp;</td>
		<td class="value">
			<input type="checkbox" name="previewAbstracts" id="previewAbstracts" {if $previewAbstracts}checked="checked" {/if}{if $reviewMode != REVIEW_MODE_BOTH_SEQUENTIAL}disabled="disabled" {/if}/>
			{fieldLabel name="previewAbstracts" key="manager.schedConfSetup.submissions.previewAbstracts"}
		</td>
	</tr>
</table>

<div id="typeOfSubmission">
<h4>{translate key="manager.schedConfSetup.submissions.typeOfSubmission"}</h4>

<table width="100%" class="data">
	{assign var=paperTypeNumber value=1}
	{foreach from=$paperTypes item=paperType key=paperTypeId}
		<input type="hidden" name="paperTypes[{$paperTypeId|escape}][seq]" value="{$paperTypeNumber}" />
		<tr valign="top">
			<td rowspan="4" width="5%">{$paperTypeNumber}.</td>
			<td width="15%" class="label">{fieldLabel name="paperTypeName-"|concat:$paperTypeId key="common.title"}</td>
			<td width="80%" colspan="2" class="value">
				<input type="text" size="40" class="textField" name="paperTypes[{$paperTypeId|escape}][name][{$formLocale|escape}]" id="paperTypeName-{$paperTypeId|escape}" value="{$paperType.name[$formLocale]|escape}" />
			</td>
		</tr>
		<tr valign="top">
			<td class="label">{fieldLabel name="paperTypeDescription-"|concat:$paperTypeId key="common.description"}</td>
			<td class="value" colspan="2">
				<textarea cols="40" rows="4" class="textArea" name="paperTypes[{$paperTypeId|escape}][description][{$formLocale|escape}]" id="paperTypeDescription-{$paperTypeId|escape}">{$paperType.description[$formLocale]|escape}</textarea>
			</td>
		</tr>
		<tr valign="top">
			<td class="label">&nbsp;</td>
			<td width="35%" class="value">
				{fieldLabel name="paperTypeAbstractLength-"|concat:$paperTypeId key="manager.schedConfSetup.submissions.typeOfSubmission.abstractLength"}&nbsp;
				<input type="text" size="5" class="textField" name="paperTypes[{$paperTypeId|escape}][abstractLength]" id="paperTypeAbstractLength-{$paperTypeId|escape}" value="{$paperType.abstractLength|escape}" />
			</td>
			<td width="45%" class="value">
				{fieldLabel name="paperTypeLength-"|concat:$paperTypeId key="manager.schedConfSetup.submissions.typeOfSubmission.length"}&nbsp;
				<input type="text" size="5" class="textField" name="paperTypes[{$paperTypeId|escape}][length]" id="paperTypeLength-{$paperTypeId|escape}" value="{$paperType.length|escape}" />
			</td>
		</tr>
		<tr valign="top">
			<td class="label">&nbsp;</td>
			<td colspan="2" class="label">
				{strip}
				<a onclick="document.setupForm.paperTypeAction.value='movePaperTypeUp'; document.setupForm.paperTypeId.value='{$paperTypeId|escape:"jsparam"}'; document.setupForm.submit();" href="#">&uarr;</a>&nbsp;
				<a onclick="document.setupForm.paperTypeAction.value='movePaperTypeDown'; document.setupForm.paperTypeId.value='{$paperTypeId|escape:"jsparam"}'; document.setupForm.submit();" href="#">&darr;</a>&nbsp;|&nbsp;
				<a onclick="document.setupForm.paperTypeAction.value='deletePaperType'; document.setupForm.paperTypeId.value='{$paperTypeId|escape:"jsparam"}'; document.setupForm.submit();" href="#" class="action">{translate key="common.delete"}</a>
				{/strip}
			</td>
		</tr>
		{assign var=paperTypeNumber value=$paperTypeNumber+1}
	{/foreach}
	<tr valign="top">
		<td width="5%" class="label">&nbsp;</td>
		<td width="95%" colspan="3" class="value">
			<input type="button" onclick="document.setupForm.paperTypeAction.value='createPaperType'; document.setupForm.submit();" value="{translate key="manager.schedConfSetup.submissions.typeOfSubmission.create"}" />
		</td>
	</tr>
</table>
</div>

<div id="suppFiles">
<h4>{translate key="manager.schedConfSetup.submissions.suppFiles"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label">
			<input type="checkbox" name="acceptSupplementaryReviewMaterials" id="acceptSupplementaryReviewMaterials" value="1" {if $acceptSupplementaryReviewMaterials}checked="checked"{/if} />
		</td>
		<td width="95%" class="value">
			{fieldLabel name="acceptSupplementaryReviewMaterials" key="manager.schedConfSetup.submissions.acceptSupplementaryReviewMaterials"}
		</td>
	</tr>
</table>
</div>

<div id="notifications">
<h4>{translate key="manager.schedConfSetup.notifications"}</h4>

<p>{translate key="manager.schedConfSetup.submissions.notifications.description"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input {if !$submissionAckEnabled}disabled="disabled" {/if}type="checkbox" name="copySubmissionAckPrimaryContact" id="copySubmissionAckPrimaryContact" value="true" {if $copySubmissionAckPrimaryContact}checked="checked"{/if}/></td>
		<td width="95%" class="value">{fieldLabel name="copySubmissionAckPrimaryContact" key="manager.schedConfSetup.submissions.notifications.copyPrimaryContact"}</td>
	</tr>
	<tr valign="top">
		<td class="label"><input {if !$submissionAckEnabled}disabled="disabled" {/if}type="checkbox" name="copySubmissionAckSpecified" id="copySubmissionAckSpecified" value="true" {if $copySubmissionAckSpecified}checked="checked"{/if}/></td>
		<td class="value">{fieldLabel name="copySubmissionAckAddress" key="manager.schedConfSetup.submissions.notifications.copySpecifiedAddress"}&nbsp;&nbsp;<input {if !$submissionAckEnabled}disabled="disabled" {/if}type="text" class="textField" name="copySubmissionAckAddress" id="copySubmissionAckAddress" value="{$copySubmissionAckAddress|escape}"/></td>
	</tr>
	{if !$submissionAckEnabled}
	<tr valign="top">
		<td>&nbsp;</td>
		{url|assign:"preparedEmailsUrl" op="emails" clearPageContext=1}
		<td>{translate key="manager.schedConfSetup.submissions.notifications.submissionAckDisabled" preparedEmailsUrl=$preparedEmailsUrl}</td>
	</tr>
	{/if}
</table>
</div>
</div>
<div class="separator"></div>

<div id="callForPapers">
<h3>2.2 {translate key="manager.schedConfSetup.submissions.callForPapers"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="10%" class="label">{fieldLabel name="cfpMessage" key="manager.schedConfSetup.submissions.cfpMessage"}</td>
		<td width="90%" class="value">
			<textarea name="cfpMessage[{$formLocale|escape}]" id="cfpMessage" rows="10" cols="80" class="textArea">{$cfpMessage[$formLocale]|escape}</textarea>
			<br />
			<span class="instruct">{translate key="manager.schedConfSetup.submissions.cfpMessageDescription"}</span>
		</td>
	</tr>
</table>
</div>

<div class="separator"></div>

<div id="authorGuidelinesInfo">
<h3>2.3 {translate key="manager.schedConfSetup.submissions.authorGuidelines"}</h3>

<p>{translate key="manager.schedConfSetup.submissions.authorGuidelinesDescription"}</p>

<p>
	<textarea name="authorGuidelines[{$formLocale|escape}]" id="authorGuidelines" rows="12" cols="60" class="textArea">{$authorGuidelines[$formLocale]|escape}</textarea>
</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label">
			<input type="checkbox" name="metaCitations" id="metaCitations" value="1"{if $metaCitations} checked="checked"{/if} />
		</td>
		<td width="95%" class="value"><label for="metaCitations">{translate key="manager.setup.citations"}</label>
		</td>
	</tr>
</table>

<div id="preparationChecklist">
<h4>{translate key="manager.schedConfSetup.submissions.preparationChecklist"}</h4>

<p>{translate key="manager.schedConfSetup.submissions.preparationChecklist.description"}</p>

{foreach name=checklist from=$submissionChecklist[$formLocale] key=checklistId item=checklistItem}
	{if !$notFirstChecklistItem}
		{assign var=notFirstChecklistItem value=1}
		<table width="100%" class="data">
			<tr valign="top">
				<td width="5%">{translate key="common.order"}</td>
				<td width="95%" colspan="2">&nbsp;</td>
			</tr>
	{/if}

	<tr valign="top">
		<td width="5%" class="label"><input type="text" name="submissionChecklist[{$formLocale|escape}][{$checklistId|escape}][order]" value="{$checklistItem.order|escape}" size="3" maxlength="2" class="textField" /></td>
		<td class="value"><textarea name="submissionChecklist[{$formLocale|escape}][{$checklistId|escape}][content]" id="submissionChecklist-{$checklistId|escape}" rows="3" cols="40" class="textArea">{$checklistItem.content|escape}</textarea></td>
		<td width="100%"><input type="submit" name="delChecklist[{$checklistId|escape}]" value="{translate key="common.delete"}" class="button" /></td>
	</tr>
{/foreach}
</table>

<p><input type="submit" name="addChecklist" value="{translate key="manager.schedConfSetup.submissions.addChecklistItem"}" class="button" /></p>
</div>
</div>
<div class="separator"></div>

<div id="forAuthorsToIndexTheirWork">
<h3>2.4 {translate key="manager.schedConfSetup.submissions.forAuthorsToIndexTheirWork"}</h3>

<p>{translate key="manager.schedConfSetup.submissions.forAuthorsToIndexTheirWorkDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="metaDiscipline" id="metaDiscipline" value="1"{if $metaDiscipline} checked="checked"{/if} /></td>
		<td width="95%" class="value">
			<strong>{fieldLabel name="metaDiscipline" key="manager.schedConfSetup.submissions.discipline"}</strong>
			<br />
			<span class="instruct">{translate key="manager.schedConfSetup.submissions.disciplineDescription"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="manager.schedConfSetup.submissions.disciplineProvideExamples"}:</span>
			<br />
			<input type="text" name="metaDisciplineExamples[{$formLocale|escape}]" id="metaDisciplineExamples" value="{$metaDisciplineExamples[$formLocale]|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="manager.schedConfSetup.submissions.disciplineExamples"}</span>
		</td>
	</tr>
	
	<tr>
		<td class="separator" colspan="2"><br />&nbsp;</td>
	</tr>
	
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="metaSubjectClass" id="metaSubjectClass" value="1"{if $metaSubjectClass} checked="checked"{/if} /></td>
		<td width="95%" class="value">
			<strong>{fieldLabel name="metaSubjectClass" key="manager.schedConfSetup.submissions.subjectClassification"}</strong>
		</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<table width="100%">
				<tr valign="top">
					<td width="10%">{fieldLabel name="metaSubjectClassTitle" key="common.title"}</td>
					<td width="90%"><input type="text" name="metaSubjectClassTitle[{$formLocale|escape}]" id="metaSubjectClassTitle" value="{$metaSubjectClassTitle[$formLocale]|escape}" size="40" maxlength="255" class="textField" /></td>
				</tr>
				<tr valign="top">
					<td width="10%">{fieldLabel name="metaSubjectClassUrl" key="common.url"}</td>
					<td width="90%"><input type="text" name="metaSubjectClassUrl" id="metaSubjectClassUrl" value="{if $metaSubjectClassUrl}{$metaSubjectClassUrl|escape}{else}http://{/if}" size="40" maxlength="255" class="textField" /></td>
				</tr>
			</table>
			<span class="instruct">{translate key="manager.schedConfSetup.submissions.subjectClassificationExamples"}</span>
		</td>
	</tr>
	
	<tr>
		<td class="separator" colspan="2"><br />&nbsp;</td>
	</tr>
	
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="metaSubject" id="metaSubject" value="1"{if $metaSubject} checked="checked"{/if} /></td>
		<td width="95%" class="value">
			<strong>{fieldLabel name="metaSubject" key="manager.schedConfSetup.submissions.subjectKeywordTopic"}</strong>
		</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="manager.schedConfSetup.submissions.subjectProvideExamples"}:</span>
			<br />
			<input type="text" name="metaSubjectExamples[{$formLocale|escape}]" id="metaSubjectExamples" value="{$metaSubjectExamples[$formLocale]|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="manager.schedConfSetup.submissions.subjectExamples"}</span>
		</td>
	</tr>
	
	<tr>
		<td class="separator" colspan="2"><br />&nbsp;</td>
	</tr>
	
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="metaCoverage" id="metaCoverage" value="1"{if $metaCoverage} checked="checked"{/if} /></td>
		<td width="95%" class="value">
			<strong>{fieldLabel name="metaCoverage" key="manager.schedConfSetup.submissions.coverage"}</strong>
			<br />
			<span class="instruct">{translate key="manager.schedConfSetup.submissions.coverageDescription"}</span>
		</td>
	</tr>
	<tr>
		<td class="separator" colspan="2">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="manager.schedConfSetup.submissions.coverageGeoProvideExamples"}:</span>
			<br />
			<input type="text" name="metaCoverageGeoExamples[{$formLocale|escape}]" id="metaCoverageGeoExamples" value="{$metaCoverageGeoExamples[$formLocale]|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="manager.schedConfSetup.submissions.coverageGeoExamples"}</span>
		</td>
	</tr>
	<tr>
		<td class="separator" colspan="2">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="manager.schedConfSetup.submissions.coverageChronProvideExamples"}:</span>
			<br />
			<input type="text" name="metaCoverageChronExamples[{$formLocale|escape}]" id="metaCoverageChronExamples" value="{$metaCoverageChronExamples[$formLocale]|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="manager.schedConfSetup.submissions.coverageChronExamples"}</span>
		</td>
	</tr>
	<tr>
		<td class="separator" colspan="2">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="manager.schedConfSetup.submissions.coverageResearchSampleProvideExamples"}:</span>
			<br />
			<input type="text" name="metaCoverageResearchSampleExamples[{$formLocale|escape}]" id="metaCoverageResearchSampleExamples" value="{$metaCoverageResearchSampleExamples[$formLocale]|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="manager.schedConfSetup.submissions.coverageResearchSampleExamples"}</span>
		</td>
	</tr>
	
	<tr>
		<td class="separator" colspan="2"><br />&nbsp;</td>
	</tr>
	
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="metaType" id="metaType" value="1"{if $metaType} checked="checked"{/if} /></td>
		<td width="95%" class="value">
			<strong>{fieldLabel name="metaType" key="manager.schedConfSetup.submissions.typeMethodApproach"}</strong>
		</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="manager.schedConfSetup.submissions.typeProvideExamples"}:</span>
			<br />
			<input type="text" name="metaTypeExamples[{$formLocale|escape}]" id="metaTypeExamples" value="{$metaTypeExamples[$formLocale]|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="manager.schedConfSetup.submissions.typeExamples"}</span>
		</td>
	</tr>
</table>
</div>
<div class="separator"></div>

<div id="publicIdentifier">
<h3>2.5 {translate key="manager.schedConfSetup.submissions.publicIdentifier"}</h3>

<p>{translate key="manager.schedConfSetup.submissions.uniqueIdentifierDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="enablePublicPaperId" id="enablePublicPaperId" value="1"{if $enablePublicPaperId} checked="checked"{/if} /></td>
		<td width="95%" class="value">{fieldLabel name="enablePublicPaperId" key="manager.schedConfSetup.submissions.enablePublicPaperId"}</td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="enablePublicSuppFileId" id="enablePublicSuppFileId" value="1"{if $enablePublicSuppFileId} checked="checked"{/if} /></td>
		<td width="95%" class="value">{fieldLabel name="enablePublicSuppFileId" key="manager.schedConfSetup.submissions.enablePublicSuppFileId"}</td>
	</tr>
</table>
</div>
<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="schedConfSetup"}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
