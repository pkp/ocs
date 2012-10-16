{**
 * peerReview.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the author's director decision table.
 *
 *}
<div id="directorDecision">
<h3>{translate key="submission.directorDecision"}</h3>

{assign var=authorFiles value=$submission->getAuthorFileRevisions($submission->getCurrentRound())}
{assign var=directorFiles value=$submission->getDirectorFileRevisions($submission->getCurrentRound())}

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
			{url|assign:"notifyAuthorUrl" op="emailDirectorDecisionComment" paperId=$submission->getId()}
			{icon name="mail" url=$notifyAuthorUrl}
			&nbsp;&nbsp;&nbsp;&nbsp;
			{translate key="submission.directorAuthorRecord"}
			{if $submission->getMostRecentDirectorDecisionComment()}
				{assign var="comment" value=$submission->getMostRecentDirectorDecisionComment()}
				<a href="javascript:openComments('{url op="viewDirectorDecisionComments" path=$submission->getId() anchor=$comment->getId()}');" class="icon">{icon name="comment"}</a> {$comment->getDatePosted()|date_format:$dateFormatShort}
			{else}
				<a href="javascript:openComments('{url op="viewDirectorDecisionComments" path=$submission->getId()}');" class="icon">{icon name="comment"}</a>{translate key="common.noComments"}
			{/if}
		</td>
	</tr>
{**
 If files are allowed in this round:
	1) Review mode supports files (i.e. not "abstracts alone"),
	2) If review mode is "both sequential", we're not looking at the abstract round, and
	3) If the current round of the submission is not the abstract round
 *}
{if $submission->getReviewMode() != REVIEW_MODE_ABSTRACTS_ALONE && ($submission->getReviewMode() != REVIEW_MODE_BOTH_SEQUENTIAL || $round != REVIEW_ROUND_ABSTRACT) && $submission->getCurrentRound() != REVIEW_ROUND_ABSTRACT}
	<tr valign="top">
		<td class="label" width="20%">
			{translate key="submission.directorVersion"}
		</td>
		<td class="value" width="80%">
			{foreach from=$directorFiles item=directorFile key=key}
				<a href="{url op="downloadFile" path=$submission->getId()|to_array:$directorFile->getFileId():$directorFile->getRevision()}" class="file">{$directorFile->getFileName()|escape}</a>&nbsp;&nbsp;{$directorFile->getDateModified()|date_format:$dateFormatShort}<br />
			{foreachelse}
				{translate key="common.none"}
			{/foreach}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">
			{translate key="submission.authorVersion"}
		</td>
		<td class="value" width="80%">
			{foreach from=$authorFiles item=authorFile key=key}
				<a href="{url op="downloadFile" path=$submission->getId()|to_array:$authorFile->getFileId():$authorFile->getRevision()}" class="file">{$authorFile->getFileName()|escape}</a>&nbsp;&nbsp;{$authorFile->getDateModified()|date_format:$dateFormatShort}
				{if $mayEditPaper}
					&nbsp;&nbsp;&nbsp;&nbsp;
					<a href="{url op="deletePaperFile" path=$submission->getId()|to_array:$authorFile->getFileId():$authorFile->getRevision()}" class="action">{translate key="common.delete"}</a>
				{/if}
				<br />
			{foreachelse}
				{translate key="common.none"}
			{/foreach}
		</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">
			{translate key="author.paper.uploadAuthorVersion"}
		</td>
		<td class="value" width="80%">
			<form class="pkp_form" method="post" action="{url op="uploadRevisedVersion"}" enctype="multipart/form-data">
				<input type="hidden" name="paperId" value="{$submission->getId()}" />
				<input type="file" {if !$mayEditPaper}disabled="disabled" {/if}name="upload" class="uploadField" />
				<input type="submit" {if !$mayEditPaper}disabled="disabled" {/if}name="submit" value="{translate key="common.upload"}" class="button" />
			</form>

		</td>
	</tr>
{/if}{* If files are allowed in this round *}
</table>
</div>
<div class="separator"></div>

{include file="author/submission/layout.tpl"}


