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

{assign var="pageTitle" value="eventDirector.setup.submissionReview}
{include file="eventDirector/setup/setupHeader.tpl"}

<form method="post" action="{url op="saveSetup" path="3"}">
{include file="common/formErrors.tpl"}

<h3>3.1 {translate key="eventDirector.setup.reviewerRegistration"}</h3>

<p>{translate key="eventDirector.setup.reviewerRegistrationDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="10%" />
		<td width="90%">
			<input type="checkbox" name="openRegReviewer" id="openRegReviewer" value="1" {if $openRegReviewer}checked="checked"{/if} />
			{fieldLabel name="openRegReviewer" key="eventDirector.setup.openRegReviewerOn"}
			<nobr>
				{html_select_date prefix="openRegReviewerDate" time=$openRegReviewerDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$dateExtentFuture}
			</nobr>
		</td>
	</tr>
	<tr valign="top">
		<td width="10%" />
		<td width="90%">
			<input type="checkbox" name="closeRegReviewer" id="closeRegReviewer" value="1" {if $closeRegReviewer}checked="checked"{/if} />
			{fieldLabel name="closeRegReviewer" key="eventDirector.setup.closeRegReviewerOn"}
			<nobr>
				{html_select_date prefix="closeRegReviewerDate" time=$closeRegReviewerDate all_extra="class=\"selectMenu\"" start_year="+0" end_year=$dateExtentFuture}
			</nobr>
		</td>
	</tr>
</table>

<h3>3.2 {translate key="eventDirector.setup.reviewModel"}</h3>

{*<p>{translate key="eventDirector.setup.reviewModelDescription"}</p>*}

<h4>{translate key="eventDirector.setup.reviewOptions"}</h4>

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
	<strong>{translate key="eventDirector.setup.reviewOptions.reviewTime"}</strong><br/>
	{translate key="eventDirector.setup.reviewOptions.numWeeksPerReview"}: <input type="text" name="numWeeksPerReview" id="numWeeksPerReview" value="{$numWeeksPerReview|escape}" size="2" maxlength="8" class="textField" /> {translate key="common.weeks"}<br/>
	{translate key="common.note"}: {translate key="eventDirector.setup.reviewOptions.noteOnModification"}
</p>

	<p>
		<strong>{translate key="eventDirector.setup.reviewOptions.reviewerReminders"}</strong><br/>
		{translate key="eventDirector.setup.reviewOptions.automatedReminders"}:<br/>
		<input type="checkbox" name="remindForInvite" id="remindForInvite" value="1" onclick="toggleAllowSetInviteReminder(this.form)"{if $remindForInvite} checked="checked"{/if} />&nbsp;
		<label for="remindForInvite">{translate key="eventDirector.setup.reviewOptions.remindForInvite1"}</label>
		<select name="numDaysBeforeInviteReminder" size="1" class="selectMenu"{if not $remindForInvite} disabled="disabled"{/if}>
			{section name="inviteDayOptions" start=3 loop=11}
			<option value="{$smarty.section.inviteDayOptions.index}"{if $numDaysBeforeInviteReminder eq $smarty.section.inviteDayOptions.index or ($smarty.section.inviteDayOptions.index eq 5 and not $remindForInvite)} selected="selected"{/if}>{$smarty.section.inviteDayOptions.index}</option>
			{/section}
		</select>
		{translate key="eventDirector.setup.reviewOptions.remindForInvite2"}
		<br/>

		<input type="checkbox" name="remindForSubmit" id="remindForSubmit" value="1" onclick="toggleAllowSetSubmitReminder(this.form)"{if $remindForSubmit} checked="checked"{/if} />&nbsp;
		<label for="remindForSubmit">{translate key="eventDirector.setup.reviewOptions.remindForSubmit1"}</label>
		<select name="numDaysBeforeSubmitReminder" size="1" class="selectMenu"{if not $remindForSubmit} disabled="disabled"{/if}>
			{section name="submitDayOptions" start=0 loop=11}
				<option value="{$smarty.section.submitDayOptions.index}"{if $numDaysBeforeSubmitReminder eq $smarty.section.submitDayOptions.index} selected="selected"{/if}>{$smarty.section.submitDayOptions.index}</option>
		{/section}
		</select>
		{translate key="eventDirector.setup.reviewOptions.remindForSubmit2"}
	</p>

<p>
	<strong>{translate key="eventDirector.setup.reviewOptions.reviewerRatings"}</strong><br/>
	<input type="checkbox" name="rateReviewerOnQuality" id="rateReviewerOnQuality" value="1"{if $rateReviewerOnQuality} checked="checked"{/if} />&nbsp;
	<label for="rateReviewerOnQuality">{translate key="eventDirector.setup.reviewOptions.onQuality"}</label>
</p>

<p>
	<strong>{translate key="eventDirector.setup.reviewOptions.reviewerAccess"}</strong><br/>
	<input type="checkbox" name="reviewerAccessKeysEnabled" id="reviewerAccessKeysEnabled" value="1"{if $reviewerAccessKeysEnabled} checked="checked"{/if} />&nbsp;
	<label for="reviewerAccessKeysEnabled">{translate key="eventDirector.setup.reviewOptions.reviewerAccessKeysEnabled"}</label><br/>
	<span class="instruct">{translate key="eventDirector.setup.reviewOptions.reviewerAccessKeysEnabled.description"}</span><br/>
	<input type="checkbox" name="restrictReviewerFileAccess" id="restrictReviewerFileAccess" value="1"{if $restrictReviewerFileAccess} checked="checked"{/if} />&nbsp;
	<label for="restrictReviewerFileAccess">{translate key="eventDirector.setup.reviewOptions.restrictReviewerFileAccess"}</label>
</p>

<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="setup" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
