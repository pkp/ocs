{**
 * step1.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 1 of presenter paper submission.
 *
 * $Id$
 *}

{assign var="pageTitle" value="presenter.submit.step1"}
{include file="presenter/submit/submitHeader.tpl"}

<p>{translate key="presenter.submit.howToSubmit"
	supportName=$schedConfSettings.supportName
	supportEmail=$schedConfSettings.supportEmail
	supportPhone=$schedConfSettings.supportPhone}</p>

<div class="separator"></div>

{if count($trackOptions) <= 1}
<p>{translate key="presenter.submit.notAccepting"}</p>
{else}

<script type="text/javascript">
{literal}
<!--
function checkSubmissionChecklist() {
	var elements = document.submit.elements;
	for (var i=0; i < elements.length; i++) {
		if (elements[i].type == 'checkbox' && !elements[i].checked) {
			if (elements[i].name.match('^checklist')) {
				alert({/literal}'{translate|escape:"javascript" key="presenter.submit.verifyChecklist"}'{literal});
				return false;
			} else if (elements[i].name == 'copyrightNoticeAgree') {
				alert({/literal}'{translate|escape:"javascript" key="presenter.submit.copyrightNoticeAgreeRequired"}'{literal});
				return false;
			}
		}
	}
	return true;
}
// -->
{/literal}
</script>

<form name="submit" method="post" action="{url op="saveSubmit" path=$submitStep}" onsubmit="return checkSubmissionChecklist()">

{if $paperId}
<input type="hidden" name="paperId" value="{$paperId}" />
{/if}
<input type="hidden" name="submissionChecklist" value="1" />
{include file="common/formErrors.tpl"}

{if $schedConfSettings.submissionChecklist}

{foreach name=checklist from=$schedConfSettings.submissionChecklist key=checklistId item=checklistItem}
	{if $checklistItem.content}
		{if !$notFirstChecklistItem}
			{assign var=notFirstChecklistItem value=1}
			<h3>{translate key="presenter.submit.submissionChecklist"}</h3>
			<p>{translate key="presenter.submit.submissionChecklistDescription"}</p>
			<table width="100%" class="data">
		{/if}
		<tr valign="top">
			<td width="5%"><input type="checkbox" id="checklist-{$smarty.foreach.checklist.iteration}" name="checklist[]" value="{$checklistId}"{if $paperId || $submissionChecklist} checked="checked"{/if} /></td>
			<td width="95%"><label for="checklist-{$smarty.foreach.checklist.iteration}">{$checklistItem.content|nl2br}</label></td>
		</tr>
	{/if}
{/foreach}

{if $notFirstChecklistItem}
	</table>
	<div class="separator"></div>
{/if}

{/if}

{if !empty($schedConfSettings.copyrightNotice)}
<h3>{translate key="about.copyrightNotice"}</h3>

<p>{$schedConfSettings.copyrightNotice|nl2br}</p>

{if $schedConfSettings.copyrightNoticeAgree}
<table width="100%" class="data">
	<tr valign="top">
		<td width="5%"><input type="checkbox" name="copyrightNoticeAgree" id="copyrightNoticeAgree" value="1"{if $paperId || $copyrightNoticeAgree} checked="checked"{/if} /></td>
		<td width="95%"><label for="copyrightNoticeAgree">{translate key="presenter.submit.copyrightNoticeAgree"}</label></td>
	</tr>
</table>
{/if}

<div class="separator"></div>
{/if}

<h3>{translate key="presenter.submit.conferenceTrack"}</h3>

{url|assign:"url" page="about"}
<p>{translate key="presenter.submit.conferenceTrackDescription" aboutUrl=$url}</p>


<table class="data" width="100%">
<tr valign="top">	
	<td width="20%" class="label">{fieldLabel name="trackId" required="true" key="track.track"}</td>
	<td width="80%" class="value"><select name="trackId" id="trackId" size="1" class="selectMenu">{html_options options=$trackOptions selected=$trackId}</select></td>
</tr>
</table>

<div class="separator"></div>

<h3>{translate key="presenter.submit.commentsForEditor"}</h3>
<table width="100%" class="data">

<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="commentsToEditor" key="presenter.submit.comments"}</td>
	<td width="80%" class="value"><textarea name="commentsToEditor" id="commentsToEditor" rows="3" cols="40" class="textArea">{$commentsToEditor|escape}</textarea></td>
</tr>

</table>

<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="{if $paperId}confirmAction('{url page="presenter"}', '{translate|escape:"javascript" key="presenter.submit.cancelSubmission"}'){else}document.location.href='{url page="presenter" escape=false}'{/if}" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{/if}

{include file="common/footer.tpl"}
