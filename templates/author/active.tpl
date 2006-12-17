{**
 * active.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the details of active submissions.
 *
 * $Id$
 *}

<table class="listing" width="100%">
	<tr><td colspan="6" class="headseparator">&nbsp;</td></tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.id"}</td>
		<td width="5%"><span class="disabled">MM-DD</span><br />{translate key="submissions.submit"}</td>
		<td width="5%">{translate key="submissions.track"}</td>
		<td width="25%">{translate key="paper.authors"}</td>
		<td width="35%">{translate key="paper.title"}</td>
		<td width="25%" align="right">{translate key="common.status"}</td>
	</tr>
	<tr><td colspan="6" class="headseparator">&nbsp;</td></tr>

{iterate from=submissions item=submission}
	{assign var="paperId" value=$submission->getPaperId()}
	{assign var="submissionProgress" value=$submission->getSubmissionProgress()}
	{assign var="reviewProgress" value=$submission->getReviewProgress()}
	{assign var="status" value=$submission->getSubmissionStatus()}

	<tr valign="top">
		<td>{$paperId}</td>
		<td>{if $submission->getDateSubmitted()}{$submission->getDateSubmitted()|date_format:$dateFormatTrunc}{else}&mdash;{/if}</td>
		<td>{$submission->getTrackAbbrev()|escape}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."|escape}</td>
		{if $submissionProgress==0}
			<td><a href="{url op="submission" path=$paperId}" class="action">{if $submission->getPaperTitle()}{$submission->getPaperTitle()|strip_unsafe_html|truncate:60:"..."}{else}{translate key="common.untitled"}{/if}</a></td>
			<td align="right">
				{if $status==SUBMISSION_STATUS_ARCHIVED}{translate key="submissions.archived"}
				{elseif $status==SUBMISSION_STATUS_QUEUED_UNASSIGNED}{translate key="submissions.queuedUnassigned"}
				{elseif $status==SUBMISSION_STATUS_QUEUED_EDITING}<a href="{url op="submissionEditing" path=$paperId}" class="action">{translate key="submissions.queuedEditing"}</a>
				{elseif $status==SUBMISSION_STATUS_QUEUED_REVIEW}
					{if $reviewProgress==REVIEW_PROGRESS_PAPER}
						<a href="{url op="submissionReview" path=$paperId}" class="action">{translate key="submissions.queuedPaperReview"}</a>
					{else}
						<a href="{url op="submissionReview" path=$paperId}" class="action">{translate key="submissions.queuedAbstractReview"}</a>
					{/if}
				{elseif $status==SUBMISSION_STATUS_QUEUED_PAPER_REVIEW}<a href="{url op="submissionReview" path=$paperId}" class="action">{translate key="submissions.queuedPaperReview"}</a>
				{elseif $status==SUBMISSION_STATUS_ACCEPTED}{translate key="submissions.accepted"}
				{elseif $status==SUBMISSION_STATUS_DECLINED}{translate key="submissions.declined"}
				{/if}
			</td>
		{else}
			<td><a href="{url op="submit" path=$submissionProgress paperId=$paperId}" class="action">{if $submission->getPaperTitle()}{$submission->getPaperTitle()|strip_unsafe_html|truncate:60:"..."}{else}{translate key="common.untitled"}{/if}</a></td>
			<td align="right">
				{translate key="submissions.incomplete"}
				{if $reviewProgress==REVIEW_PROGRESS_ABSTRACT}
					<br />
					<a href="{url op="deleteSubmission" path=$paperId}" class="action" onclick="return confirm('{translate|escape:"javascript" key="author.submissions.confirmDelete"}')">
						{translate key="common.delete"}
					</a>
				{/if}
			</td>
		{/if}

	</tr>

	<tr>
		<td colspan="6" class="{if $submissions->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $submissions->wasEmpty()}
	<tr>
		<td colspan="6" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="6" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="4" align="left">{page_info iterator=$submissions}</td>
		<td colspan="2" align="right">{page_links name="submissions" iterator=$submissions}</td>
	</tr>
{/if}
</table>
