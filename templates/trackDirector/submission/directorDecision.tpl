{**
 * directorDecision.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the director decision table.
 *
 * $Id$
 *}

<a name="directorDecision"></a>
<h3>{translate key="submission.directorDecision"}</h3>

<table width="100%" class="data">
<tr valign="top">
	<td class="label" width="20%">{translate key="director.paper.selectDecision"}</td>
	<td width="80%" class="value" colspan="2">
		<form method="post" action="{url op="recordDecision"}">
			<input type="hidden" name="paperId" value="{$submission->getPaperId()}" />
			<select name="decision" size="1" class="selectMenu"{if not $allowRecommendation} disabled="disabled"{/if}>
				{assign var=availableDirectorDecisionOptions value=`$submission->getDirectorDecisionOptions($currentSchedConf,$stage)`}
				{html_options_translate options=$availableDirectorDecisionOptions selected=$lastDecision}
			</select>
			<input type="submit" onclick="return confirm('{translate|escape:"javascript" key="director.submissionReview.confirmDecision"}')" name="submit" value="{translate key="director.paper.recordDecision"}" {if not $allowRecommendation}disabled="disabled"{/if} class="button" />
			{if not $allowRecommendation and $isCurrent}<br />{translate key="director.paper.cannotRecord}{/if}
		</form>
	</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="director.paper.decision"}</td>
	<td class="value" colspan="2">
		{foreach from=$directorDecisions item=directorDecision key=decisionKey}
			{if $decisionKey neq 0} | {/if}
			{assign var="decision" value=$directorDecision.decision}
			{translate key=$directorDecisionOptions.$decision}&nbsp;&nbsp;{$directorDecision.dateDecided|date_format:$dateFormatShort}
		{foreachelse}
			{translate key="common.none"}
		{/foreach}
	</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="submission.notifyPresenter"}</td>
	<td class="value" colspan="2">
		{url|assign:"notifyPresenterUrl" op="emailDirectorDecisionComment" paperId=$submission->getPaperId()}
		{icon name="mail" url=$notifyPresenterUrl}
		&nbsp;&nbsp;&nbsp;&nbsp;
		{translate key="submission.directorPresenterRecord"}
		{if $submission->getMostRecentDirectorDecisionComment()}
			{assign var="comment" value=$submission->getMostRecentDirectorDecisionComment()}
			<a href="javascript:openComments('{url op="viewDirectorDecisionComments" path=$submission->getPaperId() anchor=$comment->getCommentId()}');" class="icon">{icon name="comment"}</a>&nbsp;&nbsp;{$comment->getDatePosted()|date_format:$dateFormatShort}
		{else}
			<a href="javascript:openComments('{url op="viewDirectorDecisionComments" path=$submission->getPaperId()}');" class="icon">{icon name="comment"}</a>
		{/if}
	</td>
</tr>
</table>

<form method="post" action="{url op="directorReview"}" enctype="multipart/form-data">
<input type="hidden" name="paperId" value="{$submission->getPaperId()}" />
{assign var=presenterFiles value=$submission->getPresenterFileRevisions($stage)}
{assign var=directorFiles value=$submission->getDirectorFileRevisions($stage)}
{assign var=reviewFile value=$submission->getReviewFile()}
{assign var="presenterRevisionExists" value=false}
{assign var="directorRevisionExists" value=false}
{assign var="publishableRevisionExists" value=false}

{if not $reviewingAbstractOnly}
	<table class="data" width="100%">
		{if $reviewFile}
			<tr valign="top">
				<td width="20%" class="label">{translate key="submission.reviewVersion"}</td>
				<td width="50%" class="value">
					{if $lastDecision == SUBMISSION_DIRECTOR_DECISION_ACCEPT}<input type="radio" name="directorDecisionFile" value="{$reviewFile->getFileId()},{$reviewFile->getRevision()}" /> {/if}<a href="{url op="downloadFile" path=$submission->getPaperId()|to_array:$reviewFile->getFileId():$reviewFile->getRevision()}" class="file">{$reviewFile->getFileName()}</a>&nbsp;&nbsp;
					{$reviewFile->getDateModified()|date_format:$dateFormatShort}
				</td>
			</tr>
		{/if}
		{foreach from=$presenterFiles item=presenterFile key=key}
			<tr valign="top">
				{if !$presenterRevisionExists}
					{assign var="presenterRevisionExists" value=true}
					<td width="20%" rowspan="{$presenterFiles|@count}" class="label">{translate key="submission.presenterVersion"}</td>
				{/if}
				<td width="80%" class="value" colspan="3">
					{if $lastDecision == SUBMISSION_DIRECTOR_DECISION_ACCEPT}
						<input type="radio" name="directorDecisionFile" value="{$presenterFile->getFileId()},{$presenterFile->getRevision()}" />
						{assign var="publishableRevisionExists" value=true}
					{/if}
					<a href="{url op="downloadFile" path=$submission->getPaperId()|to_array:$presenterFile->getFileId():$presenterFile->getRevision()}" class="file">{$presenterFile->getFileName()}</a>&nbsp;&nbsp;
						{$presenterFile->getDateModified()|date_format:$dateFormatShort}
				</td>
			</tr>
		{foreachelse}
			<tr valign="top">
				<td width="20%" class="label">{translate key="submission.presenterVersion"}</td>
				<td width="80%" colspan="3" class="nodata">{translate key="common.none"}</td>
			</tr>
		{/foreach}
		{foreach from=$directorFiles item=directorFile key=key}
			<tr valign="top">
				{if !$directorRevisionExists}
					{assign var="directorRevisionExists" value=true}
					<td width="20%" rowspan="{$directorFiles|@count}" class="label">{translate key="submission.directorVersion"}</td>
				{/if}
				<td width="50%" class="value" colspan="2">
					{if $lastDecision == SUBMISSION_DIRECTOR_DECISION_ACCEPT}
						<input type="radio" name="directorDecisionFile" value="{$directorFile->getFileId()},{$directorFile->getRevision()}" />
						{assign var="publishableRevisionExists" value=true}
					{/if}
					<a href="{url op="downloadFile" path=$submission->getPaperId()|to_array:$directorFile->getFileId():$directorFile->getRevision()}" class="file">{$directorFile->getFileName()}</a>&nbsp;&nbsp;
					{$directorFile->getDateModified()|date_format:$dateFormatShort}
				</td>
				<td width="30%" class="value"><a href="{url op="deletePaperFile" path=$submission->getPaperId()|to_array:$directorFile->getFileId():$directorFile->getRevision()}" class="action">{translate key="common.delete"}</a></td>
			</tr>
		{foreachelse}
			<tr valign="top">
				<td width="20%" class="label">{translate key="submission.directorVersion"}</td>
				<td width="80%" colspan="3" class="nodata">{translate key="common.none"}</td>
			</tr>
		{/foreach}
	</table>

	{if $isCurrent}
	<div>
		{translate key="director.paper.uploadDirectorVersion"}
		<input type="file" name="upload" class="uploadField" />
		<input type="submit" name="submit" value="{translate key="common.upload"}" class="button" />
	</div>
	{/if}

	{if $publishableRevisionExists}
		<table class="data" width="100%">
			<tr valign="top">
				<td width="20%">&nbsp;</td>
				<td width="80%">
					{translate key="director.paper.sendFileToEditing"}
					<input type="submit" name="setEditingFile" value="{translate key="form.send"}" class="button" />
				</td>
			</tr>
		</table>
	{/if}
{/if}

</form>
