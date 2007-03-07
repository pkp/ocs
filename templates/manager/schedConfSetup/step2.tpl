{**
 * step3.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 3 of conference setup.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.schedConfSetup.submissions.title"}
{include file="manager/schedConfSetup/setupHeader.tpl"}

<form method="post" action="{url op="saveSchedConfSetup" path="2"}">
{include file="common/formErrors.tpl"}

<h3>2.1 {translate key="manager.schedConfSetup.submissions.submissionProcess"}</h3>

<p>{translate key="manager.schedConfSetup.submissions.description"}</p>

<h4>{translate key="manager.schedConfSetup.submissions.presentationMaterials"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label">
			<input type="radio" name="reviewMode" id="reviewMode-1" value="{$smarty.const.REVIEW_MODE_ABSTRACTS_ALONE}" {if $reviewMode == REVIEW_MODE_ABSTRACTS_ALONE}checked="checked"{/if} />
		</td>
		<td width="95%" class="value">
			<label for="reviewMode-1">{translate key="manager.schedConfSetup.submissions.abstractsAlone"}</label>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">
			<input type="radio" name="reviewMode" id="reviewMode-2" value="{$smarty.const.REVIEW_MODE_PRESENTATIONS_ALONE}" {if $reviewMode == REVIEW_MODE_PRESENTATIONS_ALONE}checked="checked"{/if} />
		</td>
		<td class="value">
			<label for="reviewMode-2">{translate key="manager.schedConfSetup.submissions.presentationsAlone"}</label>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">
			<input type="radio" name="reviewMode" id="reviewMode-3" value="{$smarty.const.REVIEW_MODE_BOTH_TOGETHER}" {if $reviewMode == REVIEW_MODE_BOTH_TOGETHER}checked="checked"{/if} />
		</td>
		<td class="value">
			<label for="reviewMode-3">{translate key="manager.schedConfSetup.submissions.bothTogether"}</label>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">
			<input type="radio" name="reviewMode" id="reviewMode-4" value="{$smarty.const.REVIEW_MODE_BOTH_SEQUENTIAL}" {if $reviewMode == REVIEW_MODE_BOTH_SEQUENTIAL}checked="checked"{/if} />
		</td>
		<td class="value">
			<label for="reviewMode-4">{translate key="manager.schedConfSetup.submissions.bothSequential"}</label>
		</td>
	</tr>
</table>

<h4>{translate key="manager.schedConfSetup.submissions.presentationMaterials"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label">
			<input type="checkbox" name="allowIndividualSubmissions" id="allowIndividualSubmissions" value="1" {if $allowIndividualSubmissions}checked="checked"{/if} />
		</td>
		<td width="95%" class="value">
			<label for="allowIndividualSubmissions">{translate key="manager.schedConfSetup.submissions.allowIndividualSubmissions"}</label>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">
			<input type="checkbox" name="allowPanelSubmissions" id="allowPanelSubmissions" value="1" {if $allowPanelSubmissions}checked="checked"{/if} />
		</td>
		<td class="value">
			<label for="allowPanelSubmissions">{translate key="manager.schedConfSetup.submissions.allowPanelSubmissions"}</label>
		</td>
	</tr>
</table>

<h4>{translate key="manager.schedConfSetup.submissions.suppFiles"}</h4>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label">
			<input type="checkbox" name="acceptSupplementaryReviewMaterials" id="acceptSupplementaryReviewMaterials" value="1" {if $acceptSupplementaryReviewMaterials}checked="checked"{/if} />
		</td>
		<td width="95%" class="value">
			<label for="acceptSupplementaryReviewMaterials">{translate key="manager.schedConfSetup.submissions.acceptSupplementaryReviewMaterials"}</label>
		</td>
	</tr>
</table>

<h4>{translate key="manager.schedConfSetup.notifications"}</h4>

<p>{translate key="manager.schedConfSetup.submissions.notifications.description"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input {if !$submissionAckEnabled}disabled="disabled" {/if}type="checkbox" name="copySubmissionAckPrimaryContact" id="copySubmissionAckPrimaryContact" value="true" {if $copySubmissionAckPrimaryContact}checked="checked"{/if}/></td>
		<td width="95%" class="value">{fieldLabel name="copySubmissionAckPrimaryContact" key="manager.schedConfSetup.submissions.notifications.copyPrimaryContact"}</td>
	</tr>
	<tr valign="top">
		<td class="label"><input {if !$submissionAckEnabled}disabled="disabled" {/if}type="checkbox" name="copySubmissionAckSpecified" id="copySubmissionAckSpecified" value="true" {if $copySubmissionAckSpecified}checked="checked"{/if}/></td>
		<td class="value">{fieldLabel name="copySubmissionAckAddress" key="manager.schedConfSetup.submissions.notifications.copySpecifiedAddress"}&nbsp;&nbsp;<input {if !$submissionAckEnabled}disabled="disabled" {/if}type="text" class="textField" name="copySubmissionAckAddress" value="{$copySubmissionAckAddress|escape}"/></td>
	</tr>
	{if !$submissionAckEnabled}
	<tr valign="top">
		<td>&nbsp;</td>
		{url|assign:"preparedEmailsUrl" op="emails"}
		<td>{translate key="manager.schedConfSetup.submissions.notifications.submissionAckDisabled" preparedEmailsUrl=$preparedEmailsUrl}</td>
	</tr>
	{/if}
</table>

<div class="separator"></div>

<h3>2.2 {translate key="manager.schedConfSetup.submissions.callForPapers"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="10%" class="label">{fieldLabel name="cfpMessage" key="manager.schedConfSetup.submissions.cfpMessage"}</td>
		<td width="90%" class="value">
			<textarea name="cfpMessage" id="cfpMessage" rows="10" cols="80" class="textArea">{$cfpMessage|escape}</textarea>
			<br />
			<span class="instruct">{translate key="manager.schedConfSetup.submissions.cfpMessageDescription"}</span>
		</td>
	</tr>
</table>

<div class="separator"></div>

<h3>2.3 {translate key="manager.schedConfSetup.submissions.presenterGuidelines"}</h3>

<p>{translate key="manager.schedConfSetup.submissions.presenterGuidelinesDescription"}</p>

<p>
	<textarea name="presenterGuidelines" id="presenterGuidelines" rows="12" cols="60" class="textArea">{$presenterGuidelines|escape}</textarea>
	<br />
	<span class="instruct">{translate key="manager.setup.htmlSetupInstructions"}</span>
</p>

<h4>{translate key="manager.schedConfSetup.submissions.preparationChecklist"}</h4>

<p>{translate key="manager.schedConfSetup.submissions.preparationChecklist.description"}</p>

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

<p><input type="submit" name="addChecklist" value="{translate key="manager.schedConfSetup.submissions.addChecklistItem"}" class="button" /></p>

<div class="separator"></div>

<h3>2.4 {translate key="manager.schedConfSetup.submissions.forPresentersToIndexTheirWork"}</h3>

<p>{translate key="manager.schedConfSetup.submissions.forPresentersToIndexTheirWorkDescription"}</p>

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
			<input type="text" name="metaDisciplineExamples" id="metaDisciplineExamples" value="{$metaDisciplineExamples|escape}" size="60" maxlength="255" class="textField" />
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
					<td width="90%"><input type="text" name="metaSubjectClassTitle" id="metaSubjectClassTitle" value="{$metaSubjectClassTitle|escape}" size="40" maxlength="255" class="textField" /></td>
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
			<input type="text" name="metaSubjectExamples" id="metaSubjectExamples" value="{$metaSubjectExamples|escape}" size="60" maxlength="255" class="textField" />
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
			<input type="text" name="metaCoverageGeoExamples" id="metaCoverageGeoExamples" value="{$metaCoverageGeoExamples|escape}" size="60" maxlength="255" class="textField" />
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
			<input type="text" name="metaCoverageChronExamples" id="metaCoverageChronExamples" value="{$metaCoverageChronExamples|escape}" size="60" maxlength="255" class="textField" />
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
			<input type="text" name="metaCoverageResearchSampleExamples" id="metaCoverageResearchSampleExamples" value="{$metaCoverageResearchSampleExamples|escape}" size="60" maxlength="255" class="textField" />
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
			<input type="text" name="metaTypeExamples" id="metaTypeExamples" value="{$metaTypeExamples|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="manager.schedConfSetup.submissions.typeExamples"}</span>
		</td>
	</tr>
</table>

<h3>2.5 {translate key="manager.schedConfSetup.submissions.publicIdentifier"}</h3>

<p>{translate key="manager.schedConfSetup.submissions.uniqueIdentifierDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="enablePublicPaperId" id="enablePublicPaperId" value="1"{if $enablePublicPaperId} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="enablePublicPaperId">{translate key="manager.schedConfSetup.submissions.enablePublicPaperId"}</label></td>
	</tr>
	<tr valign="top">
		<td width="5%" class="label"><input type="checkbox" name="enablePublicSuppFileId" id="enablePublicSuppFileId" value="1"{if $enablePublicSuppFileId} checked="checked"{/if} /></td>
		<td width="95%" class="value"><label for="enablePublicSuppFileId">{translate key="manager.schedConfSetup.submissions.enablePublicSuppFileId"}</label></td>
	</tr>
</table>

<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="schedConfSetup" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
