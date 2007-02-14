{**
 * submissionsInReview.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show editor's submissions in review.
 *
 * $Id$
 *}

<a name="submissions"></a>

<table width="100%" class="listing">
	<tr>
		<td colspan="8" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.id"}</td>
		<td width="5%"><span class="disabled">MM-DD</span><br />{translate key="submissions.submit"}</td>
		<td width="5%">{translate key="submissions.track"}</td>
		<td width="20%">{translate key="paper.presenters"}</td>
		<td width="20%">{translate key="paper.title"}</td>
		<td width="40%">
			<center>{translate key="submission.peerReview"}</center>
			<table width="100%" class="nested">
				<tr valign="top">
					<td width="20%" style="padding: 0 4px 0 0; font-size: 1.0em">{translate key="submissions.reviewType"}</td>
					<td width="20%" style="padding: 0 4px 0 0; font-size: 1.0em">{translate key="submissions.reviewRound"}</td>
					<td width="20%" style="padding: 0 4px 0 0; font-size: 1.0em">{translate key="submission.ask"}</td>
					<td width="20%" style="padding: 0 4px 0 0; font-size: 1.0em">{translate key="submission.due"}</td>
					<td width="20%" style="padding: 0 4px 0 0; font-size: 1.0em">{translate key="submission.done"}</td>
				</tr>
			</table>
		</td>
		<td width="5%">{translate key="submissions.ruling"}</td>
		<td width="5%">{translate key="paper.trackDirector"}</td>
	</tr>
	<tr>
		<td colspan="8" class="headseparator">&nbsp;</td>
	</tr>
	
	{iterate from=submissions item=submission}
	<tr valign="top">
		<td>{$submission->getPaperId()}</td>
		<td>{$submission->getDateSubmitted()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getTrackAbbrev()|escape}</td>
		<td>{$submission->getPresenterString(true)|truncate:40:"..."|escape}</td>
		<td><a href="{url op="submissionReview" path=$submission->getPaperId()|to_array:$submission->getReviewProgress()}" class="action">{$submission->getPaperTitle()|strip_unsafe_html|truncate:40:"..."}</a></td>
		<td>
		<table width="100%">
			{foreach from=$submission->getReviewAssignments(null, null) item=reviewAssignmentTypes}
				{foreach from=$reviewAssignmentTypes item=reviewAssignments}
					{foreach from=$reviewAssignments item=assignment name=assignmentList}
						{if !$assignment->getCancelled()}
						<tr valign="top">
							{assign var="type" value=$assignment->getType()}
							{assign var="round" value=$assignment->getRound()}
							<td width="20%" style="padding: 0 4px 0 0; font-size: 1.0em">{$reviewType[$type]}</td>
							<td width="20%" style="padding: 0 4px 0 0; font-size: 1.0em">{$round}</td>
							<td width="20%" style="padding: 0 4px 0 0; font-size: 1.0em">{if $assignment->getDateNotified()}{$assignment->getDateNotified()|date_format:$dateFormatTrunc}{else}&mdash;{/if}</td>
							<td width="20%" style="padding: 0 4px 0 0; font-size: 1.0em">{if $assignment->getDateCompleted() || !$assignment->getDateConfirmed()}&mdash;{else}{$assignment->getWeeksDue()}{/if}</td>
							<td width="20%" style="padding: 0 4px 0 0; font-size: 1.0em">{if $assignment->getDateCompleted()}{$assignment->getDateCompleted()|date_format:$dateFormatTrunc}{else}&mdash;{/if}</td>
						</tr>
						{/if}
					{foreachelse}
						<tr valign="top">
							{assign var="type" value=$submission->getReviewProgress()}
							<td width="20%" style="padding: 0 4px 0 0; font-size: 1.0em">{$reviewType[$type]}</td>
							<td width="20%" style="padding: 0 4px 0 0; font-size: 1.0em">&mdash;</td>
							<td width="20%" style="padding: 0 4px 0 0; font-size: 1.0em">&mdash;</td>
							<td width="20%" style="padding: 0 4px 0 0; font-size: 1.0em">&mdash;</td>
							<td width="20%" style="padding: 0 0 0 0; font-size: 1.0em">&mdash;</td>
						</tr>
					{/foreach}
				{foreachelse}
					<tr valign="top">
						{assign var="type" value=$submission->getReviewProgress()}
						<td width="20%" style="padding: 0 4px 0 0; font-size: 1.0em">{$reviewType[$type]}</td>
						<td width="20%" style="padding: 0 4px 0 0; font-size: 1.0em">&mdash;</td>
						<td width="20%" style="padding: 0 4px 0 0; font-size: 1.0em">&mdash;</td>
						<td width="20%" style="padding: 0 4px 0 0; font-size: 1.0em">&mdash;</td>
						<td width="20%" style="padding: 0 0 0 0; font-size: 1.0em">&mdash;</td>
					</tr>
				{/foreach}
			{foreachelse}
				<tr valign="top">
					{assign var="type" value=$submission->getReviewProgress()}
					<td width="20%" style="padding: 0 4px 0 0; font-size: 1.0em">{$reviewType[$type]}</td>
					<td width="20%" style="padding: 0 4px 0 0; font-size: 1.0em">&mdash;</td>
					<td width="20%" style="padding: 0 4px 0 0; font-size: 1.0em">&mdash;</td>
					<td width="20%" style="padding: 0 4px 0 0; font-size: 1.0em">&mdash;</td>
					<td width="20%" style="padding: 0 0 0 0; font-size: 1.0em">&mdash;</td>
				</tr>
			{/foreach}
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
			{foreachelse}
				&mdash;
			{/foreach}
		</td>
		<td>
			{assign var="editAssignments" value=$submission->getEditAssignments()}
			{foreach from=$editAssignments item=editAssignment}{$editAssignment->getEditorInitials()|escape} {/foreach}
		</td>
	</tr>
	<tr>
		<td colspan="8" class="{if $submissions->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $submissions->wasEmpty()}
	<tr>
		<td colspan="8" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="8" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="5" align="left">{page_info iterator=$submissions}</td>
		<td colspan="3" align="right">{page_links anchor="submissions" name="submissions" iterator=$submissions searchField=$searchField searchMatch=$searchMatch search=$search dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth track=$track}</td>
	</tr>
{/if}
</table>
