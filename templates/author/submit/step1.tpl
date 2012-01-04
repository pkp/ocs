{**
 * step1.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 1 of author paper submission.
 *
 * $Id$
 *}
{assign var="pageTitle" value="author.submit.step1"}
{include file="author/submit/submitHeader.tpl"}

{if $currentSchedConf->getSetting('supportPhone')}
	{assign var="howToKeyName" value="author.submit.howToSubmit"}
{else}
	{assign var="howToKeyName" value="author.submit.howToSubmitNoPhone"}
{/if}

<p>{translate key=$howToKeyName supportName=$currentSchedConf->getSetting('supportName') supportEmail=$currentSchedConf->getSetting('supportEmail') supportPhone=$currentSchedConf->getSetting('supportPhone')}</p>

<div class="separator"></div>

{if count($trackOptions) <= 1}
	<p>{translate key="author.submit.notAccepting"}</p>
{else}

<form name="submit" method="post" action="{url op="saveSubmit" path=$submitStep}" onsubmit="return checkSubmissionChecklist()">
{if $paperId}<input type="hidden" name="paperId" value="{$paperId|escape}" />{/if}

{if count($trackOptions) == 2}{* Only one track: don't display the selection dropdown *}
	{foreach from=$trackOptions key="trackOptionKey" item="trackOption"}
		{* Get the last key (which will be the single track ID) *}
	{/foreach}
	<input type="hidden" name="trackId" value="{$trackOptionKey|escape}" />
{else}{* More than one track; display the selection dropdown *}
	<h3>{translate key="author.submit.conferenceTrack"}</h3>

	{url|assign:"url" page="schedConf" op="trackPolicies"}
	<p>{translate key="author.submit.conferenceTrackDescription" aboutUrl=$url}</p>

	<table class="data" width="100%">
	<tr valign="top">	
		<td width="20%" class="label">{fieldLabel name="trackId" required="true" key="track.track"}</td>
		<td width="80%" class="value"><select name="trackId" id="trackId" size="1" class="selectMenu">{html_options options=$trackOptions selected=$trackId}</select></td>
	</tr>
	</table>

	<div class="separator"></div>
{/if}{* count($trackOptions) == 2 *}

{if is_array($sessionTypes) && count($sessionTypes) != 0}
	{if count($sessionTypes) == 1}
		{* Only one session type available; force it. *}
		{foreach from=$sessionTypes item=sessionTypeObject}{/foreach}
		<input type="hidden" name="sessionType" value="{$sessionTypeObject->getId()|escape}" />
	{else}{* count($sessionTypes) > 1 *}
		<h3>{translate key="paper.sessionType"}</h3>

		<table width="100%" class="data">
			{assign var=firstSessionType value=1}
			{foreach from=$sessionTypes item=sessionTypeObject}
				<tr valign="top">
					<td rowspan="2" width="20%" class="label">
						{if $firstSessionType}
							{fieldLabel name="sessionType" key="paper.sessionType"}
						{else}
							&nbsp;
						{/if}
					</td>
					<td rowspan="2" width="5%" class="value">
						<input type="radio" class="radioButton" name="sessionType" value="{$sessionTypeObject->getId()}" {if ($sessionType == $sessionTypeObject->getId()) || ($firstSessionType && !$sessionType)}checked="checked" {/if} />
					</td>
					<td class="value" width="75%">
						<strong>{$sessionTypeObject->getLocalizedName()}</strong>
					</td>
				</tr>
				<tr valign="top">
					<td class="value">
						{$sessionTypeObject->getLocalizedDescription()}
						{if $sessionTypeObject->getLength()}
							<br/>
							{translate key="manager.schedConfSetup.submissions.typeOfSubmission.length"}: {$sessionTypeObject->getLength()|escape}
						{/if}
						{if $sessionTypeObject->getAbstractLength()}
							<br/>
							{translate key="manager.schedConfSetup.submissions.typeOfSubmission.abstractLength"}: {$sessionTypeObject->getAbstractLength()|escape}
						{/if}
					</td>
				</tr>
				{assign var=firstSessionType value=0}
			{/foreach}
		</table>
		<div class="separator"></div>
	{/if}{* count($sessionTypes) == 1 *}
{/if}{* is_array($sessionTypes) *}

<script type="text/javascript">
{literal}
<!--
function checkSubmissionChecklist() {
	var elements = document.submit.elements;
	for (var i=0; i < elements.length; i++) {
		if (elements[i].type == 'checkbox' && !elements[i].checked) {
			if (elements[i].name.match('^checklist')) {
				alert({/literal}'{translate|escape:"jsparam" key="author.submit.verifyChecklist"}'{literal});
				return false;
			} else if (elements[i].name == 'copyrightNoticeAgree') {
				alert({/literal}'{translate|escape:"jsparam" key="author.submit.copyrightNoticeAgreeRequired"}'{literal});
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
<div id="submissionChecklist">
{foreach name=checklist from=$currentSchedConf->getLocalizedSetting('submissionChecklist') key=checklistId item=checklistItem}
	{if $checklistItem.content}
		{if !$notFirstChecklistItem}
			{assign var=notFirstChecklistItem value=1}
			<h3>{translate key="author.submit.submissionChecklist"}</h3>
			<p>{translate key="author.submit.submissionChecklistDescription"}</p>
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
</div>
{/if}

{if $currentConference->getLocalizedSetting('copyrightNotice') != ''}
<div id="copyrightNotice">
<h3>{translate key="about.copyrightNotice"}</h3>

<p>{$currentConference->getLocalizedSetting('copyrightNotice')|nl2br}</p>

{if $currentConference->getSetting('copyrightNoticeAgree')}
<table width="100%" class="data">
	<tr valign="top">
		<td width="5%"><input type="checkbox" name="copyrightNoticeAgree" id="copyrightNoticeAgree" value="1"{if $paperId || $copyrightNoticeAgree} checked="checked"{/if} /></td>
		<td width="95%"><label for="copyrightNoticeAgree">{translate key="author.submit.copyrightNoticeAgree"}</label></td>
	</tr>
</table>
{/if}
</div>
<div class="separator"></div>
{/if}

{if ($currentSchedConf->getLocalizedSetting('privacyStatement')) != ''}
<div id="privacyStatement">
<h3>{translate key="author.submit.privacyStatement"}</h3>
<br />
{$currentSchedConf->getLocalizedSetting('privacyStatement')|nl2br}

<div class="separator"></div>
</div>
{/if}
<div id="commentsForDirector">
<h3>{translate key="author.submit.commentsForDirector"}</h3>
<table width="100%" class="data">

<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="commentsToDirector" key="author.submit.comments"}</td>
	<td width="80%" class="value"><textarea name="commentsToDirector" id="commentsToDirector" rows="3" cols="40" class="textArea">{$commentsToDirector|escape}</textarea></td>
</tr>

</table>
</div>
<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="{if $paperId}confirmAction('{url page="author"}', '{translate|escape:"jsparam" key="author.submit.cancelSubmission"}'){else}document.location.href='{url page="author"}'{/if}" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{/if}{* If not accepting submissions *}

{include file="common/footer.tpl"}
