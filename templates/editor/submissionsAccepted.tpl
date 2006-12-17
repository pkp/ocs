{**
 * submissionsAccepted.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show editor's submissions in editing.
 *
 * $Id$
 *}

<table width="100%" class="listing">
	<tr>
		<td colspan="9" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.id"}</td>
		<td width="5%"><span class="disabled">MM-DD</span><br />{translate key="submissions.submit"}</td>
		<td width="5%">{translate key="submissions.track"}</td>
		<td width="15%">{translate key="paper.authors"}</td>
		<td width="25%">{translate key="paper.title"}</td>
		<td width="5%">{translate key="paper.trackEditor"}</td>
	</tr>
	<tr>
		<td colspan="9" class="headseparator">&nbsp;</td>
	</tr>

	{iterate from=submissions item=submission}
	<tr valign="top">
		<td>{$submission->getPaperId()}</td>
		<td>{$submission->getDateSubmitted()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getTrackAbbrev()|escape}</td>
		<td>{$submission->getAuthorString(true)|truncate:40:"..."|escape}</td>
		<td><a href="{url op="submissionEditing" path=$submission->getPaperId()}" class="action">{$submission->getPaperTitle()|strip_unsafe_html|truncate:40:"..."}</a></td>
		<td>
			{assign var="editAssignments" value=$submission->getEditAssignments()}
			{foreach from=$editAssignments item=editAssignment}{$editAssignment->getEditorInitials()} {/foreach}
		</td>
	</tr>
	<tr>
		<td colspan="9" class="{if $submissions->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $submissions->wasEmpty()}
	<tr>
		<td colspan="9" class="nodata">{translate key="submissions.noSubmissions"}</td>
	</tr>
	<tr>
		<td colspan="9" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="5" align="left">{page_info iterator=$submissions}</td>
		<td colspan="4" align="right">{page_links name="submissions" iterator=$submissions searchField=$searchField searchMatch=$searchMatch search=$search dateFromDay=$dateFromDay dateFromYear=$dateFromYear dateFromMonth=$dateFromMonth dateToDay=$dateToDay dateToYear=$dateToYear dateToMonth=$dateToMonth track=$track}</td>
	</tr>
{/if}
</table>
