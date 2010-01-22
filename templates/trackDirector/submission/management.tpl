{**
 * management.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the submission management table.
 *
 * $Id$
 *}
<a name="submission"></a>
<h3>{translate key="paper.submission"}</h3>

{assign var="submissionFile" value=$submission->getSubmissionFile()}
{assign var="suppFiles" value=$submission->getSuppFiles()}

<table width="100%" class="data">
	<tr>
		<td width="20%" class="label">{translate key="paper.presenters"}</td>
		<td width="80%" colspan="2" class="value">
			{url|assign:"url" page="user" op="email" redirectUrl=$currentUrl to=$submission->getPresenterEmails() subject=$submission->getPaperTitle() paperId=$submission->getPaperId()}
			{$submission->getPresenterString()|escape} {icon name="mail" url=$url}
		</td>
	</tr>
	<tr>
		<td class="label">{translate key="paper.title"}</td>
		<td colspan="2" class="value">{$submission->getPaperTitle()|strip_unsafe_html}</td>
	</tr>
{if $submissionFile || $submission->getReviewMode() != REVIEW_MODE_ABSTRACTS_ALONE}
	<tr>
		<td class="label">{translate key="submission.originalFile"}</td>
		<td colspan="2" class="value">
			{if $submissionFile}
				<a href="{url op="downloadFile" path=$submission->getPaperId()|to_array:$submissionFile->getFileId()}" class="file">{$submissionFile->getFileName()|escape}</a>&nbsp;&nbsp;{$submissionFile->getDateModified()|date_format:$dateFormatShort}
			{else}
				{translate key="common.none"}
			{/if}
		</td>
	</tr>
{/if}
	<tr valign="top">
		<td class="label">{translate key="paper.suppFilesAbbrev"}</td>
		<td colspan="2" class="value">
			{foreach name="suppFiles" from=$suppFiles item=suppFile}
				<a href="{url op="downloadFile" path=$submission->getPaperId()|to_array:$suppFile->getFileId()}" class="file">{$suppFile->getFileName()|escape}</a>&nbsp;&nbsp;{$suppFile->getDateModified()|date_format:$dateFormatShort}&nbsp;&nbsp;<a href="{url op="editSuppFile" from="submission" path=$submission->getPaperId()|to_array:$suppFile->getSuppFileId()}" class="action">{translate key="common.edit"}</a>&nbsp;&nbsp;&nbsp;&nbsp;{if !$notFirst}&nbsp;&nbsp;&nbsp;&nbsp;<a href="{url op="addSuppFile" from="submission" path=$submission->getPaperId()}" class="action">{translate key="submission.addSuppFile"}</a>{/if}<br />
				{assign var=notFirst value=1}
			{foreachelse}
				{translate key="common.none"}&nbsp;&nbsp;&nbsp;&nbsp;<a href="{url op="addSuppFile" from="submission" path=$submission->getPaperId()}" class="action">{translate key="submission.addSuppFile"}</a>
			{/foreach}
		</td>
	</tr>
	<tr>
		<td class="label">{translate key="submission.submitter"}</td>
		<td colspan="2" class="value">
			{assign var="submitter" value=$submission->getUser()}
			{assign var=emailString value="`$submitter->getFullName()` <`$submitter->getEmail()`>"}
			{url|assign:"url" page="user" op="email" redirectUrl=$currentUrl to=$emailString|to_array subject=$submission->getPaperTitle|strip_tags paperId=$submission->getPaperId()}
			{$submitter->getFullName()|escape} {icon name="mail" url=$url}
		</td>
	</tr>
	<tr>
		<td class="label">{translate key="common.dateSubmitted"}</td>
		<td>{$submission->getDateSubmitted()|date_format:$dateFormatShort}</td>
	</tr>
	<tr>
		<td class="label">{translate key="track.track"}</td>
		<td class="value">{$submission->getTrackTitle()|escape}</td>
		<td class="value"><form action="{url op="changeTrack" paperId=$submission->getPaperId()}" method="post">{translate key="submission.changeTrack"} <select name="trackId" size="1" class="selectMenu">{html_options options=$tracks selected=$submission->getTrackId()}</select> <input type="submit" value="{translate key="common.record"}" class="button" /></form></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{translate key="submission.paperType"}</td>
		<td class="data">
			{if $submission->getTypeConst() == SUBMISSION_TYPE_PANEL}
				{translate key="submission.paperType.panel"}
			{else}{* SUBMISSION_TYPE_PANEL *}
				{translate key="submission.paperType.single"}
			{/if}
		</td>
		<td class="value">
			<form action="{url op="changeTypeConst" paperId=$submission->getPaperId()}" method="post">{translate key="submission.changeTrack"}
				<select name="typeConst" size="1" class="selectMenu">
					<option value="{$smarty.const.SUBMISSION_TYPE_PANEL}"{if $submission->getTypeConst() == SUBMISSION_TYPE_PANEL} selected="selected"{/if}>{translate key="submission.paperType.panel"}</option>
					<option value="{$smarty.const.SUBMISSION_TYPE_SINGLE}"{if $submission->getTypeConst() == SUBMISSION_TYPE_SINGLE} selected="selected"{/if}>{translate key="submission.paperType.single"}</option>
				</select>
				<input type="submit" value="{translate key="common.record"}" class="button" />
			</form>
		</td>
	</tr>
	{if $submission->getCommentsToDirector()}
	<tr valign="top">
		<td width="20%" class="label">{translate key="paper.commentsToDirector"}</td>
		<td width="80%" colspan="2" class="data">{$submission->getCommentsToDirector()|strip_unsafe_html|nl2br}</td>
	</tr>
	{/if}
	{if $publishedPaper}
	<tr>
		<td class="label">{translate key="submission.abstractViews"}</td>
		<td>{$publishedPaper->getViews()}</td>
	</tr>
	{/if}
</table>
