{**
 * peerReview.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the presenter's director decision table.
 *
 * $Id$
 *}
<a name="directorDecision"></a>
<h3>{translate key="submission.directorDecision"}</h3>

{assign var=presenterFiles value=$submission->getPresenterFileRevisions($submission->getCurrentStage())}
{assign var=directorFiles value=$submission->getDirectorFileRevisions($submission->getCurrentStage())}

<table width="100%" class="data">
	<tr valign="top">
		<td class="label">{translate key="director.paper.decision"}</td>
		<td>
			{if $lastDirectorDecision}
				{assign var="decision" value=$lastDirectorDecision.decision}
				{translate key=$directorDecisionOptions.$decision} {$lastDirectorDecision.dateDecided|date_format:$dateFormatShort}
			{else}
				&mdash;
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">
			{translate key="submission.notifyDirector"}
		</td>
		<td class="value" width="80%">
			{url|assign:"notifyPresenterUrl" op="emailDirectorDecisionComment" paperId=$submission->getPaperId()}
			{icon name="mail" url=$notifyPresenterUrl}
			&nbsp;&nbsp;&nbsp;&nbsp;
			{translate key="submission.directorPresenterRecord"}
			{if $submission->getMostRecentDirectorDecisionComment()}
				{assign var="comment" value=$submission->getMostRecentDirectorDecisionComment()}
				<a href="javascript:openComments('{url op="viewDirectorDecisionComments" path=$submission->getPaperId() anchor=$comment->getCommentId()}');" class="icon">{icon name="comment"}</a> {$comment->getDatePosted()|date_format:$dateFormatShort}
			{else}
				<a href="javascript:openComments('{url op="viewDirectorDecisionComments" path=$submission->getPaperId()}');" class="icon">{icon name="comment"}</a>
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">
			{translate key="submission.directorVersion"}
		</td>
		<td class="value" width="80%">
			{foreach from=$directorFiles item=directorFile key=key}
				<a href="{url op="downloadFile" path=$submission->getPaperId()|to_array:$directorFile->getFileId():$directorFile->getRevision()}" class="file">{$directorFile->getFileName()|escape}</a>&nbsp;&nbsp;{$directorFile->getDateModified()|date_format:$dateFormatShort}<br />
			{foreachelse}
				{translate key="common.none"}
			{/foreach}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">
			{translate key="submission.presenterVersion"}
		</td>
		<td class="value" width="80%">
			{foreach from=$presenterFiles item=presenterFile key=key}
				<a href="{url op="downloadFile" path=$submission->getPaperId()|to_array:$presenterFile->getFileId():$presenterFile->getRevision()}" class="file">{$presenterFile->getFileName()|escape}</a>&nbsp;&nbsp;{$presenterFile->getDateModified()|date_format:$dateFormatShort}&nbsp;&nbsp;&nbsp;&nbsp;
				<a href="{url op="deletePaperFile" path=$submission->getPaperId()|to_array:$presenterFile->getFileId():$presenterFile->getRevision()}" class="action">{translate key="common.delete"}</a><br />
			{foreachelse}
				{translate key="common.none"}
			{/foreach}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">
			{translate key="presenter.paper.uploadPresenterVersion"}
		</td>
		<td class="value" width="80%">
			<form method="post" action="{url op="uploadRevisedVersion"}" enctype="multipart/form-data">
				<input type="hidden" name="paperId" value="{$submission->getPaperId()}" />
				<input type="file" name="upload" class="uploadField" />
				<input type="submit" name="submit" value="{translate key="common.upload"}" class="button" />
			</form>

		</td>
	</tr>
</table>
