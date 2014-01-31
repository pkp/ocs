{**
 * stages.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate displaying past stages for a submission.
 *
 * $Id$
 *}
<div id="stages">
<div id="regretsAndCancels">
<h3>{translate|escape key="trackDirector.regrets.regretsAndCancels"}</h3>

<table width="100%" class="listing">
	<tr><td colspan="4" class="headseparator">&nbsp;</td></tr>
	<tr valign="top">
		<td class="heading" width="30%">{translate key="user.name"}</td>
		<td class="heading" width="25%">{translate key="submission.request"}</td>
		<td class="heading" width="25%">{translate key="trackDirector.regrets.result"}</td>
		{if $submission->getReviewMode() == REVIEW_MODE_BOTH_SEQUENTIAL}<td class="heading" width="20%">{translate key="submissions.reviewType"}</td>{/if}
	</tr>
	<tr><td colspan="4" class="headseparator">&nbsp;</td></tr>
{foreach from=$cancelsAndRegrets item=cancelOrRegret name=cancelsAndRegrets}
	<tr valign="top">
		<td>{$cancelOrRegret->getReviewerFullName()|escape}</td>
		<td>
			{if $cancelOrRegret->getDateNotified()}
				{$cancelOrRegret->getDateNotified()|date_format:$dateFormatTrunc}
			{else}
				&mdash;
			{/if}
		</td>
		<td>
			{if $cancelOrRegret->getDeclined()}
				{translate key="trackDirector.regrets"}
			{else}
				{translate key="common.cancelled"}
			{/if}
		</td>
		<td>{$cancelOrRegret->getStage()}</td>
	</tr>
	<tr>
		<td colspan="4" class="{if $smarty.foreach.cancelsAndRegrets.last}end{/if}separator">&nbsp;</td>
	</tr>
{foreachelse}
	<tr valign="top">
		<td colspan="4" class="nodata">{translate key="common.none}</td>
	</tr>
	<tr>
		<td colspan="4" class="endseparator">&nbsp;</td>
	</tr>
{/foreach}
</table>
</div>
{assign var=numStages value=$reviewAssignmentStages|@count}
{section name=stage loop=$numStages}
{assign var=stage value=$smarty.section.stage.index}
{assign var=stagePlusOne value=$stage+1}
{assign var=stageAssignments value=$reviewAssignmentStages[$stagePlusOne]}
{assign var=stageDecisions value=$directorDecisions[$stagePlusOne]}

{if $submission->getCurrentStage() != $stagePlusOne}
<div id="reviewStage">
<h4>{translate key="trackDirector.regrets.reviewStage" stage=$stagePlusOne}</h4>

{if $stage != REVIEW_STAGE_ABSTRACT}
<table width="100%" class="data">
	<tr valign="top">
		<td class="label" width="20%">{translate key="submission.reviewVersion"}</td>
		<td class="value" width="80%">
			{assign var="reviewFile" value=$reviewFilesByStage[$stagePlusOne]}
			{if $reviewFile}
				<a href="{url op="downloadFile" path=$submission->getPaperId()|to_array:$reviewFile->getFileId():$reviewFile->getRevision()}" class="file">{$reviewFile->getFileName()|escape}</a>&nbsp;&nbsp;{$reviewFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
		</td>
	</tr>
</table>
</div>
{/if}

{assign var="start" value="A"|ord}

{foreach from=$stageAssignments item=reviewAssignment key=reviewKey}
{assign var="reviewId" value=$reviewAssignment->getId()}

{if !$reviewAssignment->getCancelled()}
<div class="separator"></div>
<div id="reviewer">
<h5>{translate key="user.role.reviewer"} {$reviewKey+$start|chr} {$reviewAssignment->getReviewerFullName()|escape}</h5>

<table width="100%" class="listing">
	<tr valign="top">
		<td width="20%">{translate key="reviewer.paper.schedule"}</td>
		<td width="20%" class="heading">{translate key="submission.request"}</td>
		<td width="20%" class="heading">{translate key="submission.underway"}</td>
		<td width="20%" class="heading">{translate key="submission.due"}</td>
		<td width="20%" class="heading">{translate key="submission.acknowledge"}</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td>
			{if $reviewAssignment->getDateNotified()}
				{$reviewAssignment->getDateNotified()|date_format:$dateFormatTrunc}
			{else}
				&mdash;
			{/if}
		</td>
		<td>
			{if $reviewAssignment->getDateConfirmed()}
				{$reviewAssignment->getDateConfirmed()|date_format:$dateFormatTrunc}
			{else}
				&mdash;
			{/if}
		</td>
		<td>
			{if $reviewAssignment->getDateDue()}
				{$reviewAssignment->getDateDue()|date_format:$dateFormatTrunc}
			{else}
				&mdash;
			{/if}
		</td>
		<td>
			{if $reviewAssignment->getDateAcknowledged()}
				{$reviewAssignment->getDateAcknowledged()|date_format:$dateFormatTrunc}
			{else}
				&mdash;
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td>{translate key="submission.recommendation"}</td>
		<td colspan="4">
			{if $reviewAssignment->getRecommendation() !== null && $reviewAssignment->getRecommendation() !== ''}
				{assign var="recommendation" value=$reviewAssignment->getRecommendation()}
				{translate key=$reviewerRecommendationOptions.$recommendation}
			{else}
				{translate key="common.none"}
			{/if}
		</td>
	</tr>
	{if $reviewFormResponses[$reviewId]}
		<tr valign="top">
			<td class="label">{translate key="submission.reviewFormResponse"}</td>
			<td>
				<a href="javascript:openComments('{url op="viewReviewFormResponse" path=$submission->getPaperId()|to_array:$reviewAssignment->getId()}');" class="icon">{icon name="letter"}</a>
			</td>
		</tr>
	{/if}
	<tr valign="top">
		<td class="label">{translate key="reviewer.paper.reviewerComments"}</td>
		<td colspan="4">
			{if $reviewAssignment->getMostRecentPeerReviewComment()}
				{assign var="comment" value=$reviewAssignment->getMostRecentPeerReviewComment()}
				<a href="javascript:openComments('{url op="viewPeerReviewComments" path=$submission->getPaperId()|to_array:$reviewAssignment->getId() anchor=$comment->getId()}');" class="icon">{icon name="comment"}</a> {$comment->getDatePosted()|date_format:$dateFormatShort}
			{else}
				<a href="javascript:openComments('{url op="viewPeerReviewComments" path=$submission->getPaperId()|to_array:$reviewAssignment->getId()}');" class="icon">{icon name="comment"}</a>{translate key="common.noComments"}
			{/if}
		</td>
	</tr>
 	<tr valign="top">
		<td class="label">{translate key="reviewer.paper.uploadedFile"}</td>
		<td colspan="4">
			<table width="100%" class="data">
				{foreach from=$reviewAssignment->getReviewerFileRevisions() item=reviewerFile key=key}
				<tr valign="top">
					<td valign="middle">
						<form name="authorView{$reviewAssignment->getId()}" method="post" action="{url op="makeReviewerFileViewable"}">
							<a href="{url op="downloadFile" path=$submission->getPaperId()|to_array:$reviewerFile->getFileId():$reviewerFile->getRevision()}" class="file">{$reviewerFile->getFileName()|escape}</a>&nbsp;&nbsp;{$reviewerFile->getDateModified()|date_format:$dateFormatShort}
							<input type="hidden" name="reviewId" value="{$reviewAssignment->getId()}" />
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
</table>
</div>
{/if}
{/foreach}

<div class="separator"></div>
<div id="decisionStage">
<h4>{translate key="trackDirector.regrets.decisionStage" stage=$stagePlusOne}</h4>

{assign var=authorFiles value=$submission->getAuthorFileRevisions($stagePlusOne)}
{assign var=directorFiles value=$submission->getDirectorFileRevisions($stagePlusOne)}

<table class="data" width="100%">
	<tr valign="top">
		<td class="label" width="20%">{translate key="director.paper.decision"}</td>
		<td class="value" width="80%">
			{foreach from=$stageDecisions item=directorDecision key=decisionKey}
				{if $decisionKey neq 0} | {/if}
				{assign var="decision" value=$directorDecision.decision}
				{translate key=$directorDecisionOptions.$decision} {$directorDecision.dateDecided|date_format:$dateFormatShort}
			{foreachelse}
				{translate key="common.none"}
			{/foreach}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">{translate key="submission.notifyAuthor"}</td>
		<td class="value" width="80%">
			{translate key="submission.directorAuthorRecord"}
			{if $submission->getMostRecentDirectorDecisionComment()}
				{assign var="comment" value=$submission->getMostRecentDirectorDecisionComment()}
				<a href="javascript:openComments('{url op="viewDirectorDecisionComments" path=$submission->getPaperId() anchor=$comment->getId()}');" class="icon">{icon name="comment"}</a> {$comment->getDatePosted()|date_format:$dateFormatShort}
			{else}
				<a href="javascript:openComments('{url op="viewDirectorDecisionComments" path=$submission->getPaperId()}');" class="icon">{icon name="comment"}</a>{translate key="common.noComments"}
			{/if}
		</td>
	</tr>
	{foreach from=$authorFiles item=authorFile key=key}
		<tr valign="top">
			{if !$authorRevisionExists}
				{assign var="authorRevisionExists" value=true}
				<td width="20%" class="label" rowspan="{$authorFiles|@count}" class="label">{translate key="submission.authorVersion"}</td>
			{/if}
			<td width="80%" class="value"><a href="{url op="downloadFile" path=$submission->getPaperId()|to_array:$authorFile->getFileId():$authorFile->getRevision()}" class="file">{$authorFile->getFileName()|escape}</a>&nbsp;&nbsp;{$authorFile->getDateModified()|date_format:$dateFormatShort}</td>
		</tr>
	{foreachelse}
		<tr valign="top">
			<td width="20%" class="label">{translate key="submission.authorVersion"}</td>
			<td width="80%" colspan="4" class="nodata">{translate key="common.none"}</td>
		</tr>
	{/foreach}
	{foreach from=$directorFiles item=directorFile key=key}
		<tr valign="top">
			{if !$directorRevisionExists}
				{assign var="directorRevisionExists" value=true}
				<td width="20%" class="label" rowspan="{$directorFiles|@count}" class="label">{translate key="submission.directorVersion"}</td>
			{/if}

			<td width="30%"><a href="{url op="downloadFile" path=$submission->getPaperId()|to_array:$directorFile->getFileId():$directorFile->getRevision()}" class="file">{$directorFile->getFileName()|escape}</a>&nbsp;&nbsp;{$directorFile->getDateModified()|date_format:$dateFormatShort}</td>
		</tr>
	{foreachelse}
		<tr valign="top">
			<td width="20%" class="label">{translate key="submission.directorVersion"}</td>
			<td width="80%" colspan="4" class="nodata">{translate key="common.none"}</td>
		</tr>
	{/foreach}
</table>
</div>
<div class="separator"></div>

{/if} {* End check to see that this is actually a past review, not the current one *}

{/section} {* End section to loop through all stages *}
</div>