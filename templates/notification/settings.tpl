{**
 * index.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Displays the notification settings page and unchecks
 *
 *}
{strip}
{assign var="pageTitle" value="notification.settings"}
{include file="common/header.tpl"}
{/strip}

<p>{translate key="notification.settingsDescription"}</p>

<form id="notificationSettings" method="post" action="{url op="saveSettings"}">

<!-- Submission events -->
{if !$canOnlyRead && !$canOnlyReview}
<h4>{translate key="notification.type.submissions"}</h4>

<ul>
	<li>{translate key="notification.type.paperSubmitted" param=$titleVar}</li>
	<ul class="plain">
		<li><span>
			<input id="notificationPaperSubmitted" type="checkbox" name="notificationPaperSubmitted"{if !$smarty.const.NOTIFICATION_TYPE_PAPER_SUBMITTED|in_array:$notificationSettings} checked="checked"{/if} />
			{fieldLabel name="notificationPaperSubmitted" key="notification.allow"}
		</span></li>
		<li><span>
			<input id="emailNotificationPaperSubmitted" type="checkbox" name="emailNotificationPaperSubmitted"{if $smarty.const.NOTIFICATION_TYPE_PAPER_SUBMITTED|in_array:$emailSettings} checked="checked"{/if} />
			{fieldLabel name="emailNotificationPaperSubmitted" key="notification.email"}
		</span></li>
	</ul>
</ul>

<ul>
	<li>{translate key="notification.type.metadataModified" param=$titleVar}</li>
	<ul class="plain">
		<li><span>
			<input id="notificationMetadataModified" type="checkbox" name="notificationMetadataModified"{if !$smarty.const.NOTIFICATION_TYPE_METADATA_MODIFIED|in_array:$notificationSettings} checked="checked"{/if} />
			{fieldLabel name="notificationMetadataModified" key="notification.allow"}
		</span></li>
		<li><span>
			<input id="emailNotificationMetadataModified" type="checkbox" name="emailNotificationMetadataModified"{if $smarty.const.NOTIFICATION_TYPE_METADATA_MODIFIED|in_array:$emailSettings} checked="checked"{/if} />
			{fieldLabel name="emailNotificationMetadataModified" key="notification.email"}
		</span></li>
	</ul>
</ul>

<ul>
	<li>{translate key="notification.type.suppFileModified" param=$titleVar}</li>
	<ul class="plain">
		<li><span>
			<input id="notificationSuppFileModified" type="checkbox" name="notificationSuppFileModified"{if !$smarty.const.NOTIFICATION_TYPE_SUPP_FILE_MODIFIED|in_array:$notificationSettings} checked="checked"{/if} />
			{fieldLabel name="notificationSuppFileModified" key="notification.allow"}
		</span></li>
		<li><span>
			<input id="emailNotificationSuppFileModified" type="checkbox" name="emailNotificationSuppFileModified"{if $smarty.const.NOTIFICATION_TYPE_SUPP_FILE_MODIFIED|in_array:$emailSettings} checked="checked"{/if} />
			{fieldLabel name="emailNotificationSuppFileModified" key="notification.email"}
		</span></li>
	</ul>
</ul>

<br />

{if !$canOnlyRead}
<!-- Reviewing events -->
<h4>{translate key="notification.type.reviewing"}</h4>

<ul>
	<li>{translate key="notification.type.reviewerComment" param=$titleVar}</li>
	<ul class="plain">
		<li><span>
			<input id="notificationReviewerComment" type="checkbox" name="notificationReviewerComment"{if !$smarty.const.NOTIFICATION_TYPE_REVIEWER_COMMENT|in_array:$notificationSettings} checked="checked"{/if} />
			{fieldLabel name="notificationReviewerComment" key="notification.allow"}
		</span></li>
		<li><span>
			<input id="emailNotificationReviewerComment" type="checkbox" name="emailNotificationReviewerComment"{if $smarty.const.NOTIFICATION_TYPE_REVIEWER_COMMENT|in_array:$emailSettings} checked="checked"{/if} />
			{fieldLabel name="emailNotificationReviewerComment" key="notification.email"}
		</span></li>
	</ul>
</ul>

<ul>
	<li>{translate key="notification.type.reviewerFormComment" param=$titleVar}</li>
	<ul class="plain">
		<li><span>
			<input id="notificationReviewerFormComment" type="checkbox" name="notificationReviewerFormComment"{if !$smarty.const.NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT|in_array:$notificationSettings} checked="checked"{/if} />
			{fieldLabel name="notificationReviewerFormComment" key="notification.allow"}
		</span></li>
		<li><span>
			<input id="emailNotificationReviewerFormComment" type="checkbox" name="emailNotificationReviewerFormComment"{if $smarty.const.NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT|in_array:$emailSettings} checked="checked"{/if} />
			{fieldLabel name="emailNotificationReviewerFormComment" key="notification.email"}
		</span></li>
	</ul>
</ul>

<ul>
	<li>{translate key="notification.type.directorDecisionComment" param=$titleVar}</li>
	<ul class="plain">
		<li><span>
			<input id="notificationDirectorDecisionComment" type="checkbox" name="notificationDirectorDecisionComment"{if !$smarty.const.NOTIFICATION_TYPE_DIRECTOR_DECISION_COMMENT|in_array:$notificationSettings} checked="checked"{/if} />
			{fieldLabel name="notificationDirectorDecisionComment" key="notification.allow"}
		</span></li>
		<li><span>
			<input id="emailNotificationDirectorDecisionComment" type="checkbox" name="emailNotificationDirectorDecisionComment"{if $smarty.const.NOTIFICATION_TYPE_DIRECTOR_DECISION_COMMENT|in_array:$emailSettings} checked="checked"{/if} />
			{fieldLabel name="emailNotificationDirectorDecisionComment" key="notification.email"}
		</span></li>
	</ul>
</ul>

<br />
{/if}

<!-- Editing events -->
<h4>{translate key="notification.type.editing"}</h4>

<ul>
	<li>{translate key="notification.type.galleyModified" param=$titleVar}</li>
	<ul class="plain">
		<li><span>
			<input id="notificationGalleyModified" type="checkbox" name="notificationGalleyModified"{if !$smarty.const.NOTIFICATION_TYPE_GALLEY_MODIFIED|in_array:$notificationSettings} checked="checked"{/if} />
			{fieldLabel name="notificationGalleyModified" key="notification.allow"}
		</span></li>
		<li><span>
			<input id="emailNotificationGalleyModified" type="checkbox" name="emailNotificationGalleyModified"{if $smarty.const.NOTIFICATION_TYPE_GALLEY_MODIFIED|in_array:$emailSettings} checked="checked"{/if} />
			{fieldLabel name="emailNotificationGalleyModified" key="notification.email"}
		</span></li>
	</ul>
</ul>

<ul>
	<li>{translate key="notification.type.submissionComment" param=$titleVar}</li>
	<ul class="plain">
		<li><span>
			<input id="notificationSubmissionComment" type="checkbox" name="notificationSubmissionComment"{if !$smarty.const.NOTIFICATION_TYPE_SUBMISSION_COMMENT|in_array:$notificationSettings} checked="checked"{/if} />
			{fieldLabel name="notificationSubmissionComment" key="notification.allow"}
		</span></li>
		<li><span>
			<input id="emailNotificationSubmissionComment" type="checkbox" name="emailNotificationSubmissionComment"{if $smarty.const.NOTIFICATION_TYPE_SUBMISSION_COMMENT|in_array:$emailSettings} checked="checked"{/if} />
			{fieldLabel name="emailNotificationSubmissionComment" key="notification.email"}
		</span></li>
	</ul>
</ul>

<br />
{/if}

<!-- Site events -->
<h4>{translate key="notification.type.site"}</h4>

<ul>
	<li>{translate key="notification.type.userComment" param=$titleVar}</li>
	<ul class="plain">
		<li><span>
			<input id="notificationUserComment" type="checkbox" name="notificationUserComment"{if !$smarty.const.NOTIFICATION_TYPE_USER_COMMENT|in_array:$notificationSettings} checked="checked"{/if} />
			{fieldLabel name="notificationUserComment" key="notification.allow"}
		</span></li>
		<li><span>
			<input id="emailNotificationUserComment" type="checkbox" name="emailNotificationUserComment"{if $smarty.const.NOTIFICATION_TYPE_USER_COMMENT|in_array:$emailSettings} checked="checked"{/if} />
			{fieldLabel name="emailNotificationUserComment" key="notification.email"}
		</span></li>
	</ul>
</ul>

<ul>
	<li>{translate key="notification.type.newAnnouncement"}</li>
	<ul class="plain">
		<li><span>
			<input id="notificationNewAnnouncement" type="checkbox" name="notificationNewAnnouncement"{if !$smarty.const.NOTIFICATION_TYPE_NEW_ANNOUNCEMENT|in_array:$notificationSettings} checked="checked"{/if} />
			{fieldLabel name="notificationNewAnnouncement" key="notification.allow"}
		</span></li>
		<li><span>
			<input id="emailNotificationNewAnnouncement" type="checkbox" name="emailNotificationNewAnnouncement"{if $smarty.const.NOTIFICATION_TYPE_NEW_ANNOUNCEMENT|in_array:$emailSettings} checked="checked"{/if} />
			{fieldLabel name="emailNotificationNewAnnouncement" key="notification.email"}
		</span></li>
	</ul>
</ul>

<br />

<p><input type="submit" value="{translate key="form.submit"}" class="button defaultButton" />  <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="notification"}'" /></p>

</form>

{include file="common/footer.tpl"}
