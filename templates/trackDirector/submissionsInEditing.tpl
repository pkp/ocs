{**
 * submissionsInEditing.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show track director's submissions in editing.
 *
 * $Id$
 *}

<a name="submissions"></a>

<table width="100%" class="listing">
	<tr><td colspan="7" class="headseparator">&nbsp;</td></tr>
	<tr class="heading" valign="bottom">
		<td width="7%">{translate key="common.id"}</td>
		<td width="7%"><span class="disabled">MM-DD</span><br />{translate key="submissions.submit"}</td>
		<td width="7%">{translate key="submissions.track"}</td>
		<td width="22%">{translate key="paper.presenters"}</td>
		<td width="27%">{translate key="paper.title"}</td>
	</tr>
	<tr><td colspan="7" class="headseparator">&nbsp;</td></tr>

{iterate from=submissions item=submission}

	{assign var="paperId" value=$submission->getPaperId()}
	<tr valign="top">
		<td>{$submission->getPaperId()}</td>
		<td>{$submission->getDateSubmitted()|date_format:$dateFormatTrunc}</td>
		<td>{$submission->getTrackAbbrev()|escape}</td>
		<td>{$submission->getPresenterString(true)|truncate:40:"..."|escape}</td>
		<td><a href="{url op="submissionEditing" path=$paperId}" class="action">{$submission->getPaperTitle()|strip_unsafe_html|truncate:60:"..."}</a></td>
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
	<tr>
{else}
	<tr>
		<td colspan="3" align="left">{page_info iterator=$submissions}</td>
		<td colspan="4" align="right">{page_links anchor="submissions" name="submissions" iterator=$submissions searchField=$searchField searchMatch=$searchMatch search=$search track=$track}</td>
	</tr>
{/if}
</table>
