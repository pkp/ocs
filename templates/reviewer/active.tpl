{**
 * active.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show reviewer's active submissions.
 *
 * $Id$
 *}

{assign var=cols value=7}

<a name="submissions"></a>

<table class="listing" width="100%">
	<assign var="cols" value=6>
	{if $eventSettings.reviewPapers}
		{assign var="cols" value=$cols+1}
	{/if}
	<tr><td colspan="{$cols}" class="headseparator">&nbsp;</td></tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.id"}</td>
		<td width="5%"><span class="disabled">MM-DD</span><br />{translate key="common.assigned"}</td>
		<td width="5%">{translate key="submissions.track"}</td>
		<td width="60%">{translate key="paper.title"}</td>
		<td width="5%">{translate key="submission.due"}</td>
		{if $eventSettings.reviewPapers}
			<td width="10%">{translate key="submissions.reviewType"}</td>
		{/if}
		<td width="10%">{translate key="submissions.reviewRound"}</td>
	</tr>
	<tr><td colspan="{$cols}" class="headseparator">&nbsp;</td></tr>

{iterate from=submissions item=submission}
	{assign var="paperId" value=$submission->getPaperId()}
	{assign var="reviewId" value=$submission->getReviewId()}

	<tr valign="top">
		<td>{$paperId}</td>
		<td>{$submission->getDateNotified()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getTrackAbbrev()|escape}</td>
		<td><a href="{url op="submission" path=$reviewId}" class="action">{$submission->getPaperTitle()|strip_unsafe_html|truncate:60:"..."}</a></td>
		<td class="nowrap">{$submission->getDateDue()|date_format:$dateFormatTrunc}</td>
		{if $eventSettings.reviewPapers}
			<td>
				{if $submission->getCurrentRound()==1}{* Reviewing abstract *}
					{translate key="submission.abstract"}
				{else}
					{translate key="submission.paper"}
				{/if}
			</td>
		{/if}
		<td>{$submission->getRound()}</td>
	</tr>
	<tr>
		<td colspan="{$cols}" class="{if $submissions->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $submissions->wasEmpty()}
<tr>
		<td colspan="{$cols}" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="{$cols}" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="3" align="left">{page_info iterator=$submissions}</td>
		<td colspan="3" align="right">{page_links anchor="submissions" name="submissions" iterator=$submissions}</td>
	</tr>
{/if}
</table>
