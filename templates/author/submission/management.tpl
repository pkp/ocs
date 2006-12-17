{**
 * management.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the author's submission management table.
 *
 * $Id$
 *}

<a name="submission"></a>
<h3>{translate key="paper.submission"}</h3>
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="paper.authors"}</td>
		<td width="80%" colspan="2" class="data">{$submission->getAuthorString(false)|escape}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="paper.title"}</td>
		<td width="80%" colspan="2" class="data">{$submission->getPaperTitle()|strip_unsafe_html}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="submission.originalFile"}</td>
		<td width="80%" colspan="2" class="data">
			{if $submissionFile}
				<a href="{url op="downloadFile" path=$submission->getPaperId()|to_array:$submissionFile->getFileId():$submissionFile->getRevision()}" class="file">{$submissionFile->getFileName()|escape}</a>&nbsp;&nbsp;{$submissionFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="paper.suppFilesAbbrev"}</td>
		<td width="30%" class="value">
			{foreach name="suppFiles" from=$suppFiles item=suppFile}
				<a href="{url op="editSuppFile" path=$submission->getPaperId()|to_array:$suppFile->getSuppFileId()}" class="file">{$suppFile->getFileName()|escape}</a>&nbsp;&nbsp;{$suppFile->getDateModified()|date_format:$dateFormatShort}<br />
			{foreachelse}
				{translate key="common.none"}
			{/foreach}
		</td>
		<td width="50%" class="value"><a href="{url op="addSuppFile" path=$submission->getPaperId()}" class="action">{translate key="submission.addSuppFile"}</a></td>
	</tr>
	<tr>
		<td class="label">{translate key="submission.submitter"}</td>
		<td colspan="2" class="value">
			{assign var="submitter" value=$submission->getUser()}
			{assign var=emailString value="`$submitter->getFullName()` <`$submitter->getEmail()`>"}
			{url|assign:"url" page="user" op="email" to=$emailString|to_array redirectUrl=$currentUrl subject=$submission->getPaperTitle|strip_tags paperId=$submission->getPaperId()}
			{$submitter->getFullName()|escape} {icon name="mail" url=$url}
		</td>
	</tr>
	<tr>
		<td class="label">{translate key="common.dateSubmitted"}</td>
		<td>{$submission->getDateSubmitted()|date_format:$datetimeFormatLong}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="track.track"}</td>
		<td width="80%" colspan="2" class="data">{$submission->getTrackTitle()|escape}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="user.role.editor"}</td>
		{assign var="editAssignments" value=$submission->getEditAssignments()}
		<td width="80%" colspan="2" class="data">
			{foreach from=$editAssignments item=editAssignment}
				{assign var=emailString value="`$editAssignment->getEditorFullName()` <`$editAssignment->getEditorEmail()`>"}
				{url|assign:"url" page="user" op="email" to=$emailString|to_array redirectUrl=$currentUrl subject=$submission->getPaperTitle|strip_tags paperId=$submission->getPaperId()}
				{$editAssignment->getEditorFullName()|escape} {icon name="mail" url=$url}<br/>
                        {foreachelse}
                                {translate key="common.noneAssigned"}
                        {/foreach}
		</td>
	</tr>
	{if $submission->getCommentsToEditor()}
	<tr valign="top">
		<td width="20%" class="label">{translate key="paper.commentsToEditor"}</td>
		<td width="80%" colspan="2" class="data">{$submission->getCommentsToEditor()|strip_unsafe_html|nl2br}</td>
	</tr>
	{/if}
</table>

