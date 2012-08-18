{**
 * templates/trackDirector/submissionsInReview.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show track director's submissions in review.
 *
 *}
<div id="submissions">
<table width="100%" class="listing">
	<tr><td colspan="7" class="headseparator">&nbsp;</td></tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{sort_search key="common.id" sort="id"}</td>
		<td width="5%"><span class="disabled">{translate key="submission.date.mmdd"}</span><br />{sort_search key="submissions.submit" sort="submitDate"}</td>
		<td width="5%">{sort_search key="submissions.track" sort="track"}</td>
		<td width="20%">{sort_search key="paper.authors" sort="authors"}</td>
		<td width="20%">{sort_search key="paper.title" sort="title"}</td>
		<td width="40%">
			<center>{translate key="submission.peerReview"}</center>
			<table width="100%">
				<tr valign="top">
					<td width="20%" style="padding: 0 4px 0 0; font-size: 1.0em">{translate key="submissions.reviewStage"}</td>
					<td width="20%" style="padding: 0 4px 0 0; font-size: 1.0em">{translate key="submission.ask"}</td>
					<td width="20%" style="padding: 0 4px 0 0; font-size: 1.0em">{translate key="submission.due"}</td>
					<td width="20%" style="padding: 0 4px 0 0; font-size: 1.0em">{translate key="submission.done"}</td>
				</tr>
			</table>
		</td>
		<td width="5%">{translate key="submissions.ruling"}</td>
	</tr>
	<tr><td colspan="7" class="headseparator">&nbsp;</td></tr>

{iterate from=submissions item=submission}

	{assign var="paperId" value=$submission->getId()}
	<tr valign="top">
		<td>{$submission->getId()}</td>
		<td>{$submission->getDateSubmitted()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getTrackAbbrev()|escape}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."|escape}</td>
		<td><a href="{url op="submissionReview" path=$submission->getId()|to_array:$submission->getCurrentRound()}" class="action">{$submission->getLocalizedTitle()|strip_tags|truncate:40:"..."}</a></td>
		<td>
		<table width="100%">
			{assign var=displayedRound value=0}
			{foreach from=$submission->getReviewAssignments(null) item=reviewAssignmentTypes}
				{foreach from=$reviewAssignmentTypes item=assignment name=assignmentList}
					{assign var=displayedRound value=1}
					{if not $assignment->getCancelled() and not $assignment->getDeclined()}
					<tr valign="top">
						{assign var="round" value=$assignment->getRound()}
						<td width="25%" style="padding: 0 4px 0 0; font-size: 1.0em">{$round|escape}</td>
						<td width="25%" style="padding: 0 4px 0 0; font-size: 1.0em">{if $assignment->getDateNotified()}{$assignment->getDateNotified()|date_format:$dateFormatTrunc}{else}&mdash;{/if}</td>
						<td width="25%" style="padding: 0 4px 0 0; font-size: 1.0em">{if $assignment->getDateCompleted() || !$assignment->getDateConfirmed()}&mdash;{else}{$assignment->getWeeksDue()|default:"&mdash;"}{/if}</td>
						<td width="25%" style="padding: 0 4px 0 0; font-size: 1.0em">{if $assignment->getDateCompleted()}{$assignment->getDateCompleted()|date_format:$dateFormatTrunc}{else}&mdash;{/if}</td>
					</tr>
					{/if}
				{/foreach}
			{/foreach}
			{if !$displayedRound}
				<tr valign="top">
					<td width="25%" style="padding: 0 4px 0 0; font-size: 1.0em">&mdash;</td>
					<td width="25%" style="padding: 0 4px 0 0; font-size: 1.0em">&mdash;</td>
					<td width="25%" style="padding: 0 4px 0 0; font-size: 1.0em">&mdash;</td>
					<td width="25%" style="padding: 0 0 0 0; font-size:1.0em">&mdash;</td>
				</tr>
			{/if}
		</table>
		</td>
		<td>
			{foreach from=$submission->getDecisions() item=decisions}
				{foreach from=$decisions item=decision name=decisionList}
					{if $smarty.foreach.decisionList.last}
						{$decision.dateDecided|date_format:$dateFormatTrunc}				
					{/if}
				{foreachelse}
					&mdash;
				{/foreach}
			{/foreach}			
		</td>
	</tr>
	<tr>
		<td colspan="7" class="{if $submissions->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $submissions->wasEmpty()}
	<tr>
		<td colspan="7" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="7" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="5" align="left">{page_info iterator=$submissions}</td>
		<td colspan="2" align="right">{page_links anchor="submissions" name="submissions" iterator=$submissions searchField=$searchField searchMatch=$searchMatch search=$search track=$track sort=$sort sortDirection=$sortDirection}</td>
	</tr>
{/if}
</table>
</div>

