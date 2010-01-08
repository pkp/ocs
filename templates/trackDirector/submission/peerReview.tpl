{**
 * peerReview.tpl
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the peer review table.
 *
 * $Id$
 *}
<div id="submission">
<h3>{translate key="paper.submission"}</h3>

<table width="100%" class="data">
	<tr>
		<td width="20%" class="label">{translate key="paper.authors"}</td>
		<td width="80%">
			{url|assign:"url" page="user" op="email" redirectUrl=$currentUrl to=$submission->getAuthorEmails() subject=$submission->getLocalizedTitle() paperId=$submission->getPaperId()}
			{$submission->getAuthorString()|escape} {icon name="mail" url=$url}
		</td>
	</tr>
	<tr>
		<td class="label">{translate key="paper.title"}</td>
		<td>{$submission->getLocalizedTitle()|strip_unsafe_html}</td>
	</tr>
	<tr>
		<td class="label">{translate key="track.track"}</td>
		<td>{$submission->getTrackTitle()|escape}</td>
	</tr>
	<tr>
		<td class="label">{translate key="user.role.director"}</td>
		<td>
			{assign var=editAssignments value=$submission->getEditAssignments()}
			{foreach from=$editAssignments item=editAssignment}
				{assign var=emailString value="`$editAssignment->getDirectorFullName()` <`$editAssignment->getDirectorEmail()`>"}
				{url|assign:"url" page="user" op="email" redirectUrl=$currentUrl to=$emailString|to_array subject=$submission->getLocalizedTitle()|strip_tags paperId=$submission->getPaperId()}
				{$editAssignment->getDirectorFullName()|escape} {icon name="mail" url=$url}
				<br/>
			{foreachelse}
				{translate key="common.noneAssigned"}
			{/foreach}
		</td>
	</tr>

	{if $reviewingAbstractOnly}
		{* If this review level is for the abstract only, show the abstract. *}
		<tr valign="top">
			<td class="label" width="20%">{translate key="submission.abstract"}</td>
			<td width="80%" class="value">
				{$submission->getLocalizedAbstract()|strip_unsafe_html}
			</td>
		</tr>
	{else}
		{**
		 * This is an abstract-and-paper or paper-only review. Don't
		 * show the abstract, and show any review files or
		 * supplementary files.
		 *}
		<tr valign="top">
			<td class="label" width="20%">{translate key="submission.reviewVersion"}</td>
			{if $reviewFile}
				<td width="80%" class="value">
					<a href="{url op="downloadFile" path=$submission->getPaperId()|to_array:$reviewFile->getFileId():$reviewFile->getRevision()}" class="file">{$reviewFile->getFileName()|escape}</a>&nbsp;&nbsp;
					{$reviewFile->getDateModified()|date_format:$dateFormatShort}<!-- &nbsp;&nbsp;&nbsp;&nbsp;<a class="action" href="javascript:openHelp('{get_help_id key="editorial.trackDirectorsRole.review.blindPeerReview" url="true"}')">{translate key="reviewer.paper.ensuringBlindReview"}</a> -->
				</td>
			{else}
				<td width="80%" class="nodata">{translate key="common.none"}</td>
			{/if}
		</tr>
		{if not $isStageDisabled}
		<tr valign="top">
			<td colspan="2">
				<form method="post" action="{url op="uploadReviewVersion"}" enctype="multipart/form-data">
					{translate key="director.paper.uploadReviewVersion"}
					<input type="hidden" name="paperId" value="{$submission->getPaperId()}" />
					<input type="file" name="upload" class="uploadField" />
					<input type="submit" name="submit" value="{translate key="common.upload"}" class="button" />
				</form>
			</td>
		</tr>
		{/if}
		{foreach from=$suppFiles item=suppFile}
			<tr valign="top">
				{if !$notFirstSuppFile}
					<td class="label" rowspan="{$suppFiles|@count}">{translate key="paper.suppFilesAbbrev"}</td>
						{assign var=notFirstSuppFile value=1}
				{/if}
				<td width="80%" class="value nowrap">
					<form method="post" action="{url op="setSuppFileVisibility"}">
						<input type="hidden" name="paperId" value="{$submission->getPaperId()}" />
						<input type="hidden" name="fileId" value="{$suppFile->getSuppFileId()}" />
						<a href="{url op="downloadFile" path=$submission->getPaperId()|to_array:$suppFile->getFileId():$suppFile->getRevision()}" class="file">{$suppFile->getFileName()|escape}</a>&nbsp;&nbsp;
						{$suppFile->getDateModified()|date_format:$dateFormatShort}
						<label for="show">{translate key="director.paper.showSuppFile"}</label>
						<input type="checkbox" {if !$mayEditPaper}disabled="disabled" {/if}name="show" id="show" value="1"{if $suppFile->getShowReviewers()==1} checked="checked"{/if}/>
						<input type="submit" {if !$mayEditPaper}disabled="disabled" {/if}name="submit" value="{translate key="common.record"}" class="button" />
					</form>
				</td>
			</tr>
		{foreachelse}
			<tr valign="top">
				<td class="label">{translate key="paper.suppFilesAbbrev"}</td>
				<td class="nodata">{translate key="common.none"}</td>
			</tr>
		{/foreach}
	{/if}
</table>

<div class="separator"></div>
</div>

<div id="peerReview">

{if ($stage == REVIEW_STAGE_PRESENTATION && $submission->getCurrentStage() != REVIEW_STAGE_PRESENTATION)}
	{assign var="isStageDisabled" value=true}
{/if}

{if $isStageDisabled}
	<table class="data" width="100%">
		<tr valign="middle">
			<td><h3>{translate key="submission.peerReview"}</h3></td>
		</tr>
		<tr>
			<td><span class="instruct">{translate key="director.paper.stageDisabled"}</span></td>
		</tr>
	</table>
{else}
	<table class="data" width="100%">
		<tr valign="middle">
			<td width="30%">
				{if $submission->getReviewMode() == $smarty.const.REVIEW_MODE_BOTH_SIMULTANEOUS}
					<h3>{translate key="submission.review"}</h3>
				{elseif $stage == REVIEW_STAGE_ABSTRACT}
					<h3>{translate key="submission.abstractReview"}</h3>
				{else}{* REVIEW_STAGE_PRESENTATION *}
					<h3>{translate key="submission.paperReview"}</h3>
				{/if}
			</td>
			<td width="70%" class="nowrap">
				<a href="{url op="selectReviewer" path=$submission->getPaperId()}" class="action">{translate key="director.paper.selectReviewer"}</a>&nbsp;&nbsp;&nbsp;&nbsp;
				<a href="{url op="submissionRegrets" path=$submission->getPaperId()}" class="action">{translate|escape key="trackDirector.regrets.link"}</a>&nbsp;&nbsp;&nbsp;&nbsp;
			</td>
		</tr>
	</table>

	{assign var="start" value="A"|ord}
	{foreach from=$reviewAssignments item=reviewAssignment key=reviewKey}
	{assign var="reviewId" value=$reviewAssignment->getReviewId()}

	{if not $reviewAssignment->getCancelled()}
		{assign var="reviewIndex" value=$reviewIndexes[$reviewId]}
		<div class="separator"></div>

		<table class="data" width="100%">
		<tr>
			<td width="20%"><h4>{translate key="user.role.reviewer"} {$reviewIndex+$start|chr}</h4></td>
			<td width="34%"><h4>{$reviewAssignment->getReviewerFullName()|escape}</h4></td>
			<td width="46%">
					{if not $reviewAssignment->getDateNotified()}
						<a href="{url op="clearReview" path=$submission->getPaperId()|to_array:$reviewAssignment->getReviewId()}" class="action">{translate key="director.paper.clearReview"}</a>
					{elseif $reviewAssignment->getDeclined() or not $reviewAssignment->getDateCompleted()}
						<a href="{url op="cancelReview" paperId=$submission->getPaperId() reviewId=$reviewAssignment->getReviewId()}" class="action">{translate key="director.paper.cancelReview"}</a>
					{/if}
			</td>
		</tr>
		</table>

		<table width="100%" class="data">
		<tr valign="top">
		<td class="label">{translate key="submission.reviewForm"}</td>
		<td>
		{if $reviewAssignment->getReviewFormId()}
			{assign var="reviewFormId" value=$reviewAssignment->getReviewFormId()}
			{$reviewFormTitles[$reviewFormId]}
		{else}
			{translate key="manager.reviewForms.noneChosen"}
		{/if}
		{if !$reviewAssignment->getDateCompleted()}
			&nbsp;&nbsp;&nbsp;&nbsp;<a class="action" href="{url op="selectReviewForm" path=$submission->getPaperId()|to_array:$reviewAssignment->getReviewId()}"{if $reviewFormResponses[$reviewId]} onclick="return confirm('{translate|escape:"jsparam" key="editor.paper.confirmChangeReviewForm"}')"{/if}>{translate key="editor.paper.selectReviewForm"}</a>{if $reviewAssignment->getReviewFormId()}&nbsp;&nbsp;&nbsp;&nbsp;<a class="action" href="{url op="clearReviewForm" path=$submission->getPaperId()|to_array:$reviewAssignment->getReviewId()}"{if $reviewFormResponses[$reviewId]} onclick="return confirm('{translate|escape:"jsparam" key="editor.paper.confirmChangeReviewForm"}')"{/if}>{translate key="editor.paper.clearReviewForm"}</a>{/if}
		{/if}
		</td>
	</tr>
		<tr valign="top">
			<td class="label" width="20%">&nbsp;</td>
			<td width="80%">
				<table width="100%" class="info">
					<tr>
						<td class="heading" width="25%">{translate key="submission.request"}</td>
						<td class="heading" width="25%">{translate key="submission.underway"}</td>
						<td class="heading" width="25%">{translate key="submission.due"}</td>
						<td class="heading" width="25%">{translate key="submission.acknowledge"}</td>
					</tr>
					<tr valign="top">
						<td>
							{url|assign:"reviewUrl" op="notifyReviewer" reviewId=$reviewAssignment->getReviewId() paperId=$submission->getPaperId()}
							{if !$allowRecommendation}
								{icon name="mail" url=$reviewUrl disabled="true"}
							{elseif $reviewAssignment->getDateNotified()}
								{$reviewAssignment->getDateNotified()|date_format:$dateFormatShort}
								{if !$reviewAssignment->getDateCompleted()}
									{icon name="mail" url=$reviewUrl}
								{/if}
							{else}
								{icon name="mail" url=$reviewUrl}
							{/if}
						</td>
						<td>
							{url|assign:"acknowledgeReviewerUnderwayUrl" op="acknowledgeReviewerUnderway" reviewId=$reviewAssignment->getReviewId() paperId=$submission->getPaperId()}
							{if $reviewAssignment->getDateConfirmed()}
								{$reviewAssignment->getDateConfirmed()|date_format:$dateFormatShort}
								{if !$reviewAssignment->getDateCompleted()}
									{icon name="mail" url=$acknowledgeReviewerUnderwayUrl}
								{else}
									{icon name="mail" disabled="disabled" url=$acknowledgeReviewerUnderwayUrl}
								{/if}
							{else}
								{icon name="mail" disabled="disabled" url=$acknowledgeReviewerUnderwayUrl}
							{/if}
						</td>
						<td>
							{if $reviewAssignment->getDeclined()}
								{translate key="trackDirector.regrets"}
							{else}
								<a href="{url op="setDueDate" path=$reviewAssignment->getPaperId()|to_array:$reviewAssignment->getReviewId()}">{if $reviewAssignment->getDateDue()}{$reviewAssignment->getDateDue()|date_format:$dateFormatShort}{else}&mdash;{/if}</a>
							{/if}
						</td>
						<td>
							{url|assign:"thankUrl" op="thankReviewer" reviewId=$reviewAssignment->getReviewId() paperId=$submission->getPaperId()}
							{if $reviewAssignment->getDateAcknowledged()}
								{$reviewAssignment->getDateAcknowledged()|date_format:$dateFormatShort}
							{elseif $reviewAssignment->getDateCompleted()}
								{icon name="mail" url=$thankUrl}
							{else}
								{icon name="mail" disabled="disabled" url=$thankUrl}
							{/if}
						</td>
					</tr>
				</table>
			</td>
		</tr>

		{if $reviewAssignment->getDateConfirmed() && !$reviewAssignment->getDeclined()}
			<tr valign="top">
				<td class="label">{translate key="reviewer.paper.recommendation"}</td>
				<td>
					{if $reviewAssignment->getRecommendation() !== null && $reviewAssignment->getRecommendation() !== ''}
						{assign var="recommendation" value=$reviewAssignment->getRecommendation()}
						{translate key=$reviewerRecommendationOptions.$recommendation}
						&nbsp;&nbsp;{$reviewAssignment->getDateCompleted()|date_format:$dateFormatShort}
					{else}
						{translate key="common.none"}&nbsp;&nbsp;&nbsp;&nbsp;
						<a href="{url op="remindReviewer" paperId=$submission->getPaperId() reviewId=$reviewAssignment->getReviewId()}" class="action">{translate key="reviewer.paper.sendReminder"}</a>
						{if $reviewAssignment->getDateReminded()}
							&nbsp;&nbsp;{$reviewAssignment->getDateReminded()|date_format:$dateFormatShort}
							{if $reviewAssignment->getReminderWasAutomatic()}
								&nbsp;&nbsp;{translate key="reviewer.paper.automatic"}
							{/if}
						{/if}
					{/if}
				</td>
			</tr>
			<tr valign="top">
				<td class="label">{translate key="submission.review"}</td>
				<td>
					{if $reviewAssignment->getMostRecentPeerReviewComment()}
						{assign var="comment" value=$reviewAssignment->getMostRecentPeerReviewComment()}
						<a href="javascript:openComments('{url op="viewPeerReviewComments" path=$submission->getPaperId()|to_array:$reviewAssignment->getReviewId() anchor=$comment->getCommentId()}');" class="icon">{icon name="letter"}</a>&nbsp;&nbsp;{$comment->getDatePosted()|date_format:$dateFormatShort}
					{else}
						<a href="javascript:openComments('{url op="viewPeerReviewComments" path=$submission->getPaperId()|to_array:$reviewAssignment->getReviewId()}');" class="icon">{icon name="letter"}</a>&nbsp;&nbsp;{translate key="submission.comments.noComments"}
					{/if}
				</td>
			</tr>
			{if $reviewFormResponses[$reviewId]}
			<tr valign="top">
				<td class="label">{translate key="submission.reviewFormResponse"}</td>
				<td>
					<a href="javascript:openComments('{url op="viewReviewFormResponse" path=$submission->getPaperId()|to_array:$reviewAssignment->getReviewId()}');" class="icon">{icon name="letter"}</a>
				</td>
			</tr>
			{/if}
			<tr valign="top">
				<td class="label">{translate key="reviewer.paper.uploadedFile"}</td>
				<td>
					<table width="100%" class="data">
						{foreach from=$reviewAssignment->getReviewerFileRevisions() item=reviewerFile key=key}
						<tr valign="top">
							<td valign="middle">
								<form name="authorView{$reviewAssignment->getReviewId()}" method="post" action="{url op="makeReviewerFileViewable"}">
									<a href="{url op="downloadFile" path=$submission->getPaperId()|to_array:$reviewerFile->getFileId():$reviewerFile->getRevision()}" class="file">{$reviewerFile->getFileName()|escape}</a>&nbsp;&nbsp;{$reviewerFile->getDateModified()|date_format:$dateFormatShort}
									<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}" />
									<input type="hidden" name="paperId" value="{$submission->getPaperId()}" />
									<input type="hidden" name="fileId" value="{$reviewerFile->getFileId()}" />
									<input type="hidden" name="revision" value="{$reviewerFile->getRevision()}" />
									{translate key="director.paper.showAuthor"} <input type="checkbox" name="viewable" value="1"{if $reviewerFile->getViewable()} checked="checked"{/if} />
									<input type="submit" value="{translate key="common.record"}" class="button" />
								</form>
							</td>
						</tr>
						{foreachelse}
						<tr valign="top">
							<td>{translate key="common.none"}</td>
						</tr>
						{/foreach}
					</table>
				</td>
			</tr>
		{/if}

		{if (($reviewAssignment->getRecommendation() === null || $reviewAssignment->getRecommendation() === '') || !$reviewAssignment->getDateConfirmed()) && $reviewAssignment->getDateNotified() && !$reviewAssignment->getDeclined()}
			<tr valign="top">
				<td class="label">{translate key="reviewer.paper.directorToEnter"}</td>
				<td>
					{if !$reviewAssignment->getDateConfirmed()}
						<a href="{url op="confirmReviewForReviewer" path=$submission->getPaperId()|to_array:$reviewAssignment->getReviewId() accept=1}" class="action">{translate key="reviewer.paper.canDoReview"}</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="{url op="confirmReviewForReviewer" path=$submission->getPaperId()|to_array:$reviewAssignment->getReviewId() accept=0}" class="action">{translate key="reviewer.paper.cannotDoReview"}</a><br />
					{/if}
					<form method="post" action="{url op="uploadReviewForReviewer"}" enctype="multipart/form-data">
						{translate key="director.paper.uploadReviewForReviewer"}
						<input type="hidden" name="paperId" value="{$submission->getPaperId()}" />
						<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}"/>
						<input type="file" name="upload" class="uploadField" />
						<input type="submit" name="submit" value="{translate key="common.upload"}" class="button" />
					</form>
					{if $reviewAssignment->getDateConfirmed() && !$reviewAssignment->getDeclined()}
						<a class="action" href="{url op="enterReviewerRecommendation" paperId=$submission->getPaperId() reviewId=$reviewAssignment->getReviewId()}">{translate key="director.paper.recommendation"}</a>
					{/if}
				</td>
			</tr>
		{/if}

		{if $reviewAssignment->getDateNotified() && !$reviewAssignment->getDeclined() && $rateReviewerOnQuality}
			<tr valign="top">
				<td class="label">{translate key="director.paper.rateReviewer"}</td>
				<td>
					<form method="post" action="{url op="rateReviewer"}">
					<input type="hidden" name="reviewId" value="{$reviewAssignment->getReviewId()}" />
					<input type="hidden" name="paperId" value="{$submission->getPaperId()}" />
					{translate key="director.paper.quality"}&nbsp;
					<select name="quality" size="1" class="selectMenu">
						{html_options_translate options=$reviewerRatingOptions selected=$reviewAssignment->getQuality()}
					</select>&nbsp;&nbsp;
					<input type="submit" value="{translate key="common.record"}" class="button" />
					{if $reviewAssignment->getDateRated()}
						&nbsp;&nbsp;{$reviewAssignment->getDateRated()|date_format:$dateFormatShort}
					{/if}
				</form>
				</td>
			</tr>
		{/if}
	</table>
	{/if}
	{/foreach}
{/if}
</div>
