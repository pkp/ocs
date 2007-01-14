{**
 * completed.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show reviewer's submission archive.
 *
 * $Id$
 *}

<a name="submissions"></a>

<table class="listing" width="100%">
	<tr><td colspan="6" class="headseparator">&nbsp;</td></tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.id"}</td>
		<td width="10%"><span class="disabled">MM-DD</span><br />{translate key="common.assigned"}</td>
		<td width="10%">{translate key="submissions.track"}</td>
		<td width="35%">{translate key="paper.title"}</td>
		<td width="20%">{translate key="submission.review"}</td>
		<td width="20%">{translate key="submission.editorDecision"}</td>
	</tr>
	<tr><td colspan="6" class="headseparator">&nbsp;</td></tr>
{iterate from=submissions item=submission}
	{assign var="paperId" value=$submission->getPaperId()}
	{assign var="reviewId" value=$submission->getReviewId()}

	<tr valign="top">
		<td>{$paperId}</td>
		<td>{$submission->getDateNotified()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getTrackAbbrev()|escape}</td>
		<td>{if !$submission->getDeclined()}<a href="{url op="submission" path=$reviewId}" class="action">{/if}{$submission->getPaperTitle()|strip_unsafe_html|truncate:60:"..."}{if !$submission->getDeclined()}</a>{/if}</td>
		<td>
			{if $submission->getDeclined()}
				{translate key="trackEditor.regrets"}
			{else}
				{assign var=recommendation value=$submission->getRecommendation()}
				{if $recommendation == SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT}
					{translate key="reviewer.paper.decision.accept"}
				{elseif $recommendation == SUBMISSION_REVIEWER_RECOMMENDATION_PENDING_REVISIONS}
					{translate key="reviewer.paper.decision.pendingRevisions"}
				{elseif $recommendation == SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_HERE}
					{translate key="reviewer.paper.decision.resubmitHere"}
				{elseif $recommendation == SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_ELSEWHERE}
					{translate key="reviewer.paper.decision.resubmitElsewhere"}
				{elseif $recommendation == SUBMISSION_REVIEWER_RECOMMENDATION_DECLINE}
					{translate key="reviewer.paper.decision.decline"}
				{elseif $recommendation == SUBMISSION_REVIEWER_RECOMMENDATION_SEE_COMMENTS}
					{translate key="reviewer.paper.decision.seeComments"}
				{else}
					&mdash;
				{/if}
			{/if}
		</td>
		<td>
			{if $submission->getCancelled() || $submission->getDeclined()}
				&mdash;
			{else}
			{* Display the most recent editor decision *}
			{assign var=round value=$submission->getRound()}
			{assign var=decisions value=$submission->getDecisions($round)}
			{foreach from=$decisions item=decision name=lastDecisionFinder}
				{if $smarty.foreach.lastDecisionFinder.last and $decision.decision == SUBMISSION_EDITOR_DECISION_ACCEPT}
					{translate key="editor.paper.decision.accept"}
				{elseif $smarty.foreach.lastDecisionFinder.last and $decision.decision == SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS}
					{translate key="editor.paper.decision.pendingRevisions"}
				{elseif $smarty.foreach.lastDecisionFinder.last and $decision.decision == SUBMISSION_EDITOR_DECISION_RESUBMIT}
					{translate key="editor.paper.decision.resubmit"}
				{elseif $smarty.foreach.lastDecisionFinder.last and $decision.decision == SUBMISSION_EDITOR_DECISION_DECLINE}
					{translate key="editor.paper.decision.decline"}
				{/if}
			{foreachelse}
				&mdash;
			{/foreach}
			{/if}
		</td>
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
		<td colspan="3" align="right">{page_links anchor="submissions" name="submissions" iterator=$submissions}</td>
	</tr>
{/if}
</table>
