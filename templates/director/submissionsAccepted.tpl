{**
 * submissionsArchives.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show listing of submission archives.
 *
 * $Id$
 *}

<a name="submissions"></a>

<form action="{url op="updateAcceptedTable"}" method="post">
<table width="100%" class="listing">
	<tr>
		<td colspan="7" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="3%">{translate key="common.id"}</td>
		<td width="4%">{translate key="submissions.track"}</td>
		<td width="15%">{translate key="paper.presenters"}</td>
		<td>{translate key="paper.title"}</td>
		<td width="10%">{translate key="paper.location"}</td>
		<td width="32%">{translate key="paper.time"}</td>
		<td width="10%" align="right">{translate key="common.status"}</td>
	</tr>
	<tr>
		<td colspan="7" class="headseparator">&nbsp;</td>
	</tr>
	
	{iterate from=submissions item=submission}
	{assign var="paperId" value=$submission->getPaperId()}
	<input type="hidden" name="paperIds[]" value="{$paperId}" />
	<tr valign="top">
		<td>{$paperId}</td>
		<td>{$submission->getTrackAbbrev()|escape}</td>
		<td>{$submission->getPresenterString(true)|truncate:40:"..."|escape}</td>
		<td><a href="{url op="submissionEditing" path=$paperId}" class="action">{$submission->getPaperTitle()|strip_unsafe_html|truncate:60:"..."}</a></td>
		<td><input name="location-{$paperId}" size="10" class="textField" id="location-{$paperId}" value="{$submission->getLocation()|escape}" />
		<td>
			{html_select_date prefix="presentStartTime-$paperId" time=$submission->getPresentStartTime() all_extra="class=\"selectMenu\"" start_year="+0" end_year=$yearOffsetFuture}
			{translate key="paper.duration"}:&nbsp;<select name="duration-{$paperId}" class="selectMenu" id="duration-{$paperId}">
				{assign var="presentStartTime" value=$submission->getPresentStartTime()|strtotime}
				{assign var="presentEndTime" value=$submission->getPresentEndTime()|strtotime}
				{assign var="duration" value=`$presentEndTime-$presentStartTime`}
				{html_options options=$durationOptions selected=$duration}
			</select>
		</td>
		<td align="right">
			{assign var="status" value=$submission->getStatus()}
			{if $status == SUBMISSION_STATUS_ARCHIVED}
				{translate key="submissions.archived"}&nbsp;&nbsp;<a href="{url op="deleteSubmission" path=$paperId}" onclick="return confirm('{translate|escape:"javascript" key="director.submissionArchive.confirmDelete"}')" class="action">{translate key="common.delete"}</a>
			{elseif $status == SUBMISSION_STATUS_PUBLISHED}
				{translate key="submissions.published"}
			{elseif $status == SUBMISSION_STATUS_DECLINED}
				{translate key="submissions.declined"}&nbsp;&nbsp;<a href="{url op="deleteSubmission" path=$paperId}" onclick="return confirm('{translate|escape:"javascript" key="director.submissionArchive.confirmDelete"}')" class="action">{translate key="common.delete"}</a>
			{/if}
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
		<td colspan="4" align="left">{page_info iterator=$submissions}</td>
		<td colspan="3" align="right">{page_links anchor="submissions" name="submissions" iterator=$submissions searchField=$searchField searchMatch=$searchMatch search=$search track=$track}</td>
	</tr>
{/if}
</table>
<p><input type="submit" class="button defaultButton" value="{translate key="common.record"}" /></p>
</form>

