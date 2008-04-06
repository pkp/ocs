{**
 * step1.tpl
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 1 of presenter paper submission.
 *
 * $Id$
 *}
{assign var="pageTitle" value="presenter.submit.step1"}
{include file="presenter/submit/submitHeader.tpl"}

{if $currentSchedConf->getSetting('supportPhone')}
	{assign var="howToKeyName" value="presenter.submit.howToSubmit"}
{else}
	{assign var="howToKeyName" value="presenter.submit.howToSubmitNoPhone"}
{/if}

<p>{translate key=$howToKeyName supportName=$currentSchedConf->getSetting('supportName') supportEmail=$currentSchedConf->getSetting('supportEmail') supportPhone=$currentSchedConf->getSetting('supportPhone')}</p>

<div class="separator"></div>

{if count($trackOptions) <= 1}
<p>{translate key="presenter.submit.notAccepting"}</p>
{else}

<h3>{translate key="presenter.submit.conferenceTrack"}</h3>

{url|assign:"url" page="schedConf" op="trackPolicies"}
<p>{translate key="presenter.submit.conferenceTrackDescription" aboutUrl=$url}</p>

<form name="submit" method="post" action="{url op="saveSubmit" path=$submitStep}" onsubmit="return checkSubmissionChecklist()">

<table class="data" width="100%">
<tr valign="top">	
	<td width="20%" class="label">{fieldLabel name="trackId" required="true" key="track.track"}</td>
	<td width="80%" class="value"><select name="trackId" id="trackId" size="1" class="selectMenu">{html_options options=$trackOptions selected=$trackId}</select></td>
</tr>
</table>

<div class="separator"></div>

<script type="text/javascript">
{literal}
<!--
function checkSubmissionChecklist() {
	var elements = document.submit.elements;
	for (var i=0; i < elements.length; i++) {
		if (elements[i].type == 'checkbox' && !elements[i].checked) {
			if (elements[i].name.match('^checklist')) {
				alert({/literal}'{translate|escape:"jsparam" key="presenter.submit.verifyChecklist"}'{literal});
				return false;
			} else if (elements[i].name == 'copyrightNoticeAgree') {
				alert({/literal}'{translate|escape:"jsparam" key="presenter.submit.copyrightNoticeAgreeRequired"}'{literal});
				return false;
			}
		}
	}
	return true;
}
// -->
{/literal}
</script>

{if $currentSchedConf->getLocalizedSetting('submissionChecklist')}

{foreach name=checklist from=$currentSchedConf->getLocalizedSetting('submissionChecklist') key=checklistId item=checklistItem}
	{if $checklistItem.content}
		{if !$notFirstChecklistItem}
			{assign var=notFirstChecklistItem value=1}
			<h3>{translate key="presenter.submit.submissionChecklist"}</h3>
			<p>{translate key="presenter.submit.submissionChecklistDescription"}</p>
			<table width="100%" class="data">
		{/if}
		<tr valign="top">
			<td width="5%"><input type="checkbox" id="checklist-{$smarty.foreach.checklist.iteration}" name="checklist[]" value="{$checklistId|escape}"{if $paperId || $submissionChecklist} checked="checked"{/if} /></td>
			<td width="95%"><label for="checklist-{$smarty.foreach.checklist.iteration}">{$checklistItem.content|nl2br}</label></td>
		</tr>
	{/if}
{/foreach}

{if $notFirstChecklistItem}
	</table>
	<div class="separator"></div>
{/if}

{/if}

{if !empty($currentSchedConf->getLocalizedSetting('copyrightNotice'))}
<h3>{translate key="about.copyrightNotice"}</h3>

<p>{$currentSchedConf->getLocalizedSetting('copyrightNotice')|nl2br}</p>

{if $currentSchedConf->getSetting('copyrightNoticeAgree')}
<table width="100%" class="data">
	<tr valign="top">
		<td width="5%"><input type="checkbox" name="copyrightNoticeAgree" id="copyrightNoticeAgree" value="1"{if $paperId || $copyrightNoticeAgree} checked="checked"{/if} /></td>
		<td width="95%"><label for="copyrightNoticeAgree">{translate key="presenter.submit.copyrightNoticeAgree"}</label></td>
	</tr>
</table>
{/if}

<div class="separator"></div>
{/if}

<h3>{translate key="presenter.submit.privacyStatement"}</h3>
<br />
{$currentSchedConf->getLocalizedSetting('privacyStatement')|nl2br}

<div class="separator"></div>

<h3>{translate key="presenter.submit.commentsForDirector"}</h3>
<table width="100%" class="data">

<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="commentsToDirector" key="presenter.submit.comments"}</td>
	<td width="80%" class="value"><textarea name="commentsToDirector" id="commentsToDirector" rows="3" cols="40" class="textArea">{$commentsToDirector|escape}</textarea></td>
</tr>

</table>

<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="{if $paperId}confirmAction('{url page="presenter"}', '{translate|escape:"jsparam" key="presenter.submit.cancelSubmission"}'){else}document.location.href='{url page="presenter" escape=false}'{/if}" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{/if}{* If not accepting submissions *}

{include file="common/footer.tpl"}
