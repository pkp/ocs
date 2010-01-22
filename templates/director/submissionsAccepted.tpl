{**
 * submissionsArchives.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show listing of submission archives.
 *
 * $Id$
 *}
<a name="submissions"></a>

<table width="100%" class="listing">
	<tr>
		<td colspan="6" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="3%">{translate key="common.id"}</td>
		<td width="4%">{translate key="submissions.track"}</td>
		<td width="15%">{translate key="paper.presenters"}</td>
		<td width="60%">{translate key="paper.title"}</td>
		<td width="8%">{translate key="common.order"}</td>
		<td width="10%" align="right">{translate key="common.status"}</td>
	</tr>
	
	{iterate from=submissions item=submission}

	<tr>
		{if $lastTrackId == ''}
			<td colspan="6" class="headseparator">&nbsp;</td>
			{assign var=notFirst value=1}
		{elseif $lastTrackId != $submission->getTrackId()}
			<td colspan="6" class="headseparator">&nbsp;</td>
		{else}
			<td colspan="6" class="separator">&nbsp;</td>
		{/if}
		{assign var=lastTrackId value=$submission->getTrackId()}
	</tr>

	{assign var="paperId" value=$submission->getPaperId()}
	<input type="hidden" name="paperIds[]" value="{$paperId|escape}" />
	<tr valign="top">
		<td>{$paperId|escape}</td>
		<td>{$submission->getTrackAbbrev()|escape}</td>
		<td>{$submission->getPresenterString(true)|truncate:40:"..."|escape}</td>
		<td><a href="{url op="submissionReview" path=$paperId}" class="action">{$submission->getPaperTitle()|strip_unsafe_html|truncate:60:"..."}</a></td>
		<td>
			<a href="{url op="movePaper" d=u paperId=$submission->getPaperId()}" class="plain">&uarr;</a>
			<a href="{url op="movePaper" d=d paperId=$submission->getPaperId()}" class="plain">&darr;</a>
		</td>
		<td align="right">
			{assign var="status" value=$submission->getStatus()}
			{if $status == SUBMISSION_STATUS_ARCHIVED}
				{translate key="submissions.archived"}&nbsp;&nbsp;<a href="{url op="deleteSubmission" path=$paperId}" onclick="return confirm('{translate|escape:"jsparam" key="director.submissionArchive.confirmDelete"}')" class="action">{translate key="common.delete"}</a>
			{elseif $status == SUBMISSION_STATUS_PUBLISHED}
				{translate key="submissions.published"}
			{elseif $status == SUBMISSION_STATUS_DECLINED}
				{translate key="submissions.declined"}&nbsp;&nbsp;<a href="{url op="deleteSubmission" path=$paperId}" onclick="return confirm('{translate|escape:"jsparam" key="director.submissionArchive.confirmDelete"}')" class="action">{translate key="common.delete"}</a>
			{/if}
		</td>
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
		<td colspan="6" class="endseparator">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="3" align="left">{page_info iterator=$submissions}</td>
		<td colspan="3" align="right">{page_links anchor="submissions" name="submissions" iterator=$submissions searchField=$searchField searchMatch=$searchMatch search=$search track=$track}</td>
	</tr>
{/if}
</table>

