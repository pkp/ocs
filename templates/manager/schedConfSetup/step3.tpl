{**
 * templates/manager/schedConfSetup/step3.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 3 of conference setup.
 *
 *}
{assign var="pageTitle" value="manager.schedConfSetup.review.title"}
{include file="manager/schedConfSetup/setupHeader.tpl"}

<form class="pkp_form" id="setupForm" method="post" action="{url op="saveSchedConfSetup" path="3"}">
{include file="common/formErrors.tpl"}

{if count($formLocales) > 1}
<div id="locales">
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"setupFormUrl" op="schedConfSetup" path="3" escape=false}
			{form_language_chooser form="setupForm" url=$setupFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
</table>
</div>
{/if}
<div id="reviewPolicyInfo">
<h3>3.1 {translate key="manager.schedConfSetup.review.reviewPolicy"}</h3>

<p>{translate key="manager.schedConfSetup.review.reviewDescription"}</p>

<h4>{translate key="manager.schedConfSetup.review.reviewPolicy"}</h4>

<p><textarea name="reviewPolicy[{$formLocale|escape}]" id="reviewPolicy" rows="12" cols="60" class="textArea richContent">{$reviewPolicy[$formLocale]|escape}</textarea></p>
</div>
<div class="separator"></div>
<div id="peerReview">
<h3>3.2 {translate key="manager.schedConfSetup.review.peerReview"}</h3>

<p>{translate key="manager.schedConfSetup.review.reviewGuidelinesDescription"}</p>

<p><textarea name="reviewGuidelines[{$formLocale|escape}]" id="reviewGuidelines" rows="12" cols="60" class="textArea richContent">{$reviewGuidelines[$formLocale]|escape}</textarea></p>

<script type="text/javascript">
	{literal}
	<!--
		function toggleAllowSetInviteReminder(form) {
			form.numDaysBeforeInviteReminder.disabled = !form.numDaysBeforeInviteReminder.disabled;
		}
		function toggleAllowSetSubmitReminder(form) {
			form.numDaysBeforeSubmitReminder.disabled = !form.numDaysBeforeSubmitReminder.disabled;
		}
	// -->
	{/literal}
</script>

<p>
	<input type="radio" name="reviewDeadlineType" id="reviewDeadline-1" value="{$smarty.const.REVIEW_DEADLINE_TYPE_RELATIVE}" {if $reviewDeadlineType == $smarty.const.REVIEW_DEADLINE_TYPE_RELATIVE} checked="checked"{/if} />
		{translate key="manager.schedConfSetup.review.numWeeksPerReview1"}&nbsp;
		<input type="text" name="numWeeksPerReviewRelative" id="numWeeksPerReview" {if $numWeeksPerReviewRelative > 0} value="{$numWeeksPerReviewRelative|escape}" {/if} size="2" maxlength="8" class="textField" />&nbsp;
		{translate key="manager.schedConfSetup.review.numWeeksPerReview2"}<br/>
	<input type="radio" name="reviewDeadlineType" id="reviewDeadline-2" value="{$smarty.const.REVIEW_DEADLINE_TYPE_ABSOLUTE}" {if $reviewDeadlineType == $smarty.const.REVIEW_DEADLINE_TYPE_ABSOLUTE} checked="checked"{/if} />
		{translate key="manager.schedConfSetup.review.numWeeksPerReview1b"}&nbsp;
		{html_select_date prefix="numWeeksPerReviewAbsolute" time=$absoluteReviewDate all_extra="class=\"selectMenu\"" start_year=$firstYear end_year=$lastYear}
		{translate key="manager.schedConfSetup.review.numWeeksPerReview2b"}<br/>
	<input type="checkbox" name="restrictReviewerFileAccess" id="restrictReviewerFileAccess" value="1"{if $restrictReviewerFileAccess} checked="checked"{/if} />&nbsp;<label for="restrictReviewerFileAccess">{translate key="manager.schedConfSetup.review.restrictReviewerFileAccess"}</label>
</p>

<p>
	<input type="checkbox" name="reviewerAccessKeysEnabled" id="reviewerAccessKeysEnabled" value="1"{if $reviewerAccessKeysEnabled} checked="checked"{/if} />&nbsp;<label for="reviewerAccessKeysEnabled">{translate key="manager.schedConfSetup.review.reviewerAccessKeysEnabled"}</label><br/>
	<span class="instruct">{translate key="manager.schedConfSetup.review.reviewerAccessKeysEnabled.description"}</span>
</p>

<p>
	{translate key="manager.schedConfSetup.review.automatedReminders"}:<br/>
	<input type="checkbox" {if !$scheduledTasksEnabled}disabled="disabled" {/if} name="remindForInvite" id="remindForInvite" value="1" onclick="toggleAllowSetInviteReminder(this.form)"{if $remindForInvite} checked="checked"{/if} />&nbsp;
	<label for="remindForInvite">{translate key="manager.schedConfSetup.review.remindForInvite1"}</label>
	<select name="numDaysBeforeInviteReminder" size="1" class="selectMenu"{if not $remindForInvite} disabled="disabled"{/if}>
		{section name="inviteDayOptions" start=3 loop=11}
		<option value="{$smarty.section.inviteDayOptions.index}"{if $numDaysBeforeInviteReminder eq $smarty.section.inviteDayOptions.index or ($smarty.section.inviteDayOptions.index eq 5 and not $remindForInvite)} selected="selected"{/if}>{$smarty.section.inviteDayOptions.index}</option>
		{/section}
	</select>
	{translate key="manager.schedConfSetup.review.remindForInvite2"}
	<br/>

	<input type="checkbox" {if !$scheduledTasksEnabled}disabled="disabled" {/if}name="remindForSubmit" id="remindForSubmit" value="1" onclick="toggleAllowSetSubmitReminder(this.form)"{if $remindForSubmit} checked="checked"{/if} />&nbsp;
	<label for="remindForSubmit">{translate key="manager.schedConfSetup.review.remindForSubmit1"}</label>
	<select name="numDaysBeforeSubmitReminder" size="1" class="selectMenu"{if not $remindForSubmit} disabled="disabled"{/if}>
		{section name="submitDayOptions" start=0 loop=11}
			<option value="{$smarty.section.submitDayOptions.index}"{if $numDaysBeforeSubmitReminder eq $smarty.section.submitDayOptions.index} selected="selected"{/if}>{$smarty.section.submitDayOptions.index}</option>
	{/section}
	</select>
	{translate key="manager.schedConfSetup.review.remindForSubmit2"}

	{if !$scheduledTasksEnabled}
	<br/>
	{translate key="manager.schedConfSetup.review.automatedRemindersDisabled"}
	{/if}
</p>

<p>
	<input type="checkbox" name="rateReviewerOnQuality" id="rateReviewerOnQuality" value="1"{if $rateReviewerOnQuality} checked="checked"{/if} />&nbsp;<label for="rateReviewerOnQuality">{translate key="manager.schedConfSetup.review.onQuality"}</label>
</p>
</div>
<div class="separator"></div>
<div id="directorDecision">
<h3>3.3 {translate key="manager.schedConfSetup.review.directorDecision"}</h3>

<p>
	<input type="checkbox" name="notifyAllAuthorsOnDecision" id="notifyAllAuthorsOnDecision" value="1"{if $notifyAllAuthorsOnDecision} checked="checked"{/if} />&nbsp;<label for="notifyAllAuthorsOnDecision">{translate key="manager.schedConfSetup.review.notifyAllAuthorsOnDecision"}</label>
</p>
</div>
<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="schedConfSetup"}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}

