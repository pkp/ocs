{**
 * index.tpl
 *
 * Copyright (c) 2000-2009 John Willinsky
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
				<input type="checkbox" name="notificationPaperSubmitted"{if !$smarty.const.NOTIFICATION_TYPE_PAPER_SUBMITTED|in_array:$notificationSettings} checked="checked"{/if} />
				{translate key="notification.allow"}
			</span></li>
		<li><span>
				<input type="checkbox" name="emailNotificationArticleSubmitted"{if $smarty.const.NOTIFICATION_TYPE_PAPER_SUBMITTED|in_array:$emailSettings} checked="checked"{/if} />
				{translate key="notification.email"}
			</span></li>
	</ul>
</ul>
			
<ul>
	<li>{translate key="notification.type.metadataModified" param=$titleVar}</li>
	<ul class="plain">
		<li><span>
				<input type="checkbox" name="notificationMetadataModified"{if !$smarty.const.NOTIFICATION_TYPE_METADATA_MODIFIED|in_array:$notificationSettings} checked="checked"{/if} />
				{translate key="notification.allow"}
			</span></li>
		<li><span>
				<input type="checkbox" name="emailNotificationMetadataModified"{if $smarty.const.NOTIFICATION_TYPE_METADATA_MODIFIED|in_array:$emailSettings} checked="checked"{/if} />
				{translate key="notification.email"}
			</span></li>
	</ul>
</ul>	

<ul>
	<li>{translate key="notification.type.suppFileModified" param=$titleVar}</li>
	<ul class="plain">
		<li><span>
				<input type="checkbox" name="notificationSuppFileModified"{if !$smarty.const.NOTIFICATION_TYPE_SUPP_FILE_MODIFIED|in_array:$notificationSettings} checked="checked"{/if} />
				{translate key="notification.allow"}
			</span></li>
		<li><span>
				<input type="checkbox" name="emailNotificationSuppFileModified"{if $smarty.const.NOTIFICATION_TYPE_SUPP_FILE_MODIFIED|in_array:$emailSettings} checked="checked"{/if} />
				{translate key="notification.email"}
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
				<input type="checkbox" name="notificationReviewerComment"{if !$smarty.const.NOTIFICATION_TYPE_REVIEWER_COMMENT|in_array:$notificationSettings} checked="checked"{/if} />
				{translate key="notification.allow"}
			</span></li>
		<li><span>
				<input type="checkbox" name="emailNotificationReviewerComment"{if $smarty.const.NOTIFICATION_TYPE_REVIEWER_COMMENT|in_array:$emailSettings} checked="checked"{/if} />
				{translate key="notification.email"}
			</span></li>
	</ul>
</ul>

<ul>
	<li>{translate key="notification.type.reviewerFormComment" param=$titleVar}</li>
	<ul class="plain">
		<li><span>
				<input type="checkbox" name="notificationReviewerFormComment"{if !$smarty.const.NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT|in_array:$notificationSettings} checked="checked"{/if} />
				{translate key="notification.allow"}
			</span></li>
		<li><span>
				<input type="checkbox" name="emailNotificationReviewerFormComment"{if $smarty.const.NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT|in_array:$emailSettings} checked="checked"{/if} />
				{translate key="notification.email"}
			</span></li>
	</ul>
</ul>

<ul>
	<li>{translate key="notification.type.directorDecisionComment" param=$titleVar}</li>
	<ul class="plain">
		<li><span>
				<input type="checkbox" name="notificationEditorDecisionComment"{if !$smarty.const.NOTIFICATION_TYPE_EDITOR_DECISION_COMMENT|in_array:$notificationSettings} checked="checked"{/if} />
				{translate key="notification.allow"}
			</span></li>
		<li><span>
				<input type="checkbox" name="emailNotificationEditorDecisionComment"{if $smarty.const.NOTIFICATION_TYPE_EDITOR_DECISION_COMMENT|in_array:$emailSettings} checked="checked"{/if} />
				{translate key="notification.email"}
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
				<input type="checkbox" name="notificationGalleyModified"{if !$smarty.const.NOTIFICATION_TYPE_GALLEY_MODIFIED|in_array:$notificationSettings} checked="checked"{/if} />
				{translate key="notification.allow"}
			</span></li>
		<li><span>
				<input type="checkbox" name="emailNotificationGalleyModified"{if $smarty.const.NOTIFICATION_TYPE_GALLEY_MODIFIED|in_array:$emailSettings} checked="checked"{/if} />
				{translate key="notification.email"}
			</span></li>
	</ul>
</ul>

<ul>
	<li>{translate key="notification.type.submissionComment" param=$titleVar}</li>
	<ul class="plain">
		<li><span>
				<input type="checkbox" name="notificationSubmissionComment"{if !$smarty.const.NOTIFICATION_TYPE_SUBMISSION_COMMENT|in_array:$notificationSettings} checked="checked"{/if} />
				{translate key="notification.allow"}
			</span></li>
		<li><span>
				<input type="checkbox" name="emailNotificationSubmissionComment"{if $smarty.const.NOTIFICATION_TYPE_SUBMISSION_COMMENT|in_array:$emailSettings} checked="checked"{/if} />
				{translate key="notification.email"}
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
				<input type="checkbox" name="notificationUserComment"{if !$smarty.const.NOTIFICATION_TYPE_USER_COMMENT|in_array:$notificationSettings} checked="checked"{/if} />
				{translate key="notification.allow"}
			</span></li>
		<li><span>
				<input type="checkbox" name="emailNotificationUserComment"{if $smarty.const.NOTIFICATION_TYPE_USER_COMMENT|in_array:$emailSettings} checked="checked"{/if} />
				{translate key="notification.email"}
			</span></li>
	</ul>
</ul>

<ul>
	<li>{translate key="notification.type.newAnnouncement"}</li>
	<ul class="plain">
		<li><span>
				<input type="checkbox" name="notificationNewAnnouncement"{if !$smarty.const.NOTIFICATION_TYPE_NEW_ANNOUNCEMENT|in_array:$notificationSettings} checked="checked"{/if} />
				{translate key="notification.allow"}
			</span></li>
		<li><span>
				<input type="checkbox" name="emailNotificationNewAnnouncement"{if $smarty.const.NOTIFICATION_TYPE_NEW_ANNOUNCEMENT|in_array:$emailSettings} checked="checked"{/if} />
				{translate key="notification.email"}
			</span></li>
	</ul>
</ul>

<br />

<p><input type="submit" value="{translate key="form.submit"}" class="button defaultButton" />  <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="notification" escape=false}'" /></p>

</form>

{include file="common/footer.tpl"}
