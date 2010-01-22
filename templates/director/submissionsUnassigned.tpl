{**
 * submissionsUnassigned.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show listing of unassigned submissions.
 *
 * $Id$
 *}
<a name="submissions"></a>

<table width="100%" class="listing">
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.id"}</td>
		<td width="5%"><span class="disabled">MM-DD</span><br />{translate key="submissions.submit"}</td>
		<td width="5%">{translate key="submissions.track"}</td>
		<td width="30%">{translate key="paper.presenters"}</td>
		<td width="50%">{translate key="paper.title"}</td>
	</tr>
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	
	{iterate from=submissions item=submission}
	{assign var="currentStage" value=$submission->getCurrentStage()}
	{assign var="paperId" value=$submission->getPaperId()}
	{assign var="submissionProgress" value=$submission->getSubmissionProgress()}

	<tr valign="top">
		<td>{$paperId}</td>
		<td>{$submission->getDateSubmitted()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getTrackAbbrev()|escape}</td>
		<td>{$submission->getPresenterString(true)|truncate:40:"..."|escape}</td>
		{translate|assign:"untitledPaper" key="common.untitled"}
		<td><a href="{url op="submission" path=$submission->getPaperId()}" class="action">{$submission->getPaperTitle()|default:$untitledPaper|strip_unsafe_html|truncate:60:"..."}</a>
			{if $submissionProgress != 0 && ($currentStage == REVIEW_STAGE_ABSTRACT || ($currentStage == REVIEW_STAGE_PRESENTATION && $submissionProgress < 3))}
				(<a href="{url op="deleteSubmission" path=$paperId}" class="action" onclick="return confirm('{translate|escape:"jsparam" key="presenter.submissions.confirmDelete"}')">{translate key="common.delete"}</a>)
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="5" class="{if $submissions->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $submissions->wasEmpty()}
	<tr>
		<td colspan="5" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="5" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="4" align="left">{page_info iterator=$submissions}</td>
		<td align="right">{page_links anchor="submissions" name="submissions" iterator=$submissions searchField=$searchField searchMatch=$searchMatch search=$search track=$track}</td>
	</tr>
{/if}
</table>
