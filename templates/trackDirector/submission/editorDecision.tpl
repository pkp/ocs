{**
 * editorDecision.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the editor decision table.
 *
 * $Id$
 *}

<a name="editorDecision"></a>
<h3>{translate key="submission.editorDecision"}</h3>

<table width="100%" class="data">
<tr valign="top">
	<td class="label" width="20%">{translate key="editor.paper.selectDecision"}</td>
	<td width="80%" class="value" colspan="2">
		<form method="post" action="{url op="recordDecision"}">
			<input type="hidden" name="paperId" value="{$submission->getPaperId()}" />
			<select name="decision" size="1" class="selectMenu"{if not $allowRecommendation} disabled="disabled"{/if}>
				{html_options_translate options=$editorDecisionOptions selected=$lastDecision}
			</select>
			<input type="submit" onclick="return confirm('{translate|escape:"javascript" key="editor.submissionReview.confirmDecision"}')" name="submit" value="{translate key="editor.paper.recordDecision"}" {if not $allowRecommendation}disabled="disabled"{/if} class="button" />
			{if not $allowRecommendation and $isCurrent}<br />{translate key="editor.paper.cannotRecord}{/if}
		</form>
	</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="editor.paper.decision"}</td>
	<td class="value" colspan="2">
		{foreach from=$editorDecisions item=editorDecision key=decisionKey}
			{if $decisionKey neq 0} | {/if}
			{assign var="decision" value=$editorDecision.decision}
			{translate key=$editorDecisionOptions.$decision}&nbsp;&nbsp;{$editorDecision.dateDecided|date_format:$dateFormatShort}
		{foreachelse}
			{translate key="common.none"}
		{/foreach}
	</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="submission.notifyPresenter"}</td>
	<td class="value" colspan="2">
		{url|assign:"notifyPresenterUrl" op="emailEditorDecisionComment" paperId=$submission->getPaperId()}
		{icon name="mail" url=$notifyPresenterUrl}
		&nbsp;&nbsp;&nbsp;&nbsp;
		{translate key="submission.editorPresenterRecord"}
		{if $submission->getMostRecentEditorDecisionComment()}
			{assign var="comment" value=$submission->getMostRecentEditorDecisionComment()}
			<a href="javascript:openComments('{url op="viewEditorDecisionComments" path=$submission->getPaperId() anchor=$comment->getCommentId()}');" class="icon">{icon name="comment"}</a>&nbsp;&nbsp;{$comment->getDatePosted()|date_format:$dateFormatShort}
		{else}
			<a href="javascript:openComments('{url op="viewEditorDecisionComments" path=$submission->getPaperId()}');" class="icon">{icon name="comment"}</a>
		{/if}
	</td>
</tr>
</table>

<form method="post" action="{url op="editorReview"}" enctype="multipart/form-data">
<input type="hidden" name="paperId" value="{$submission->getPaperId()}" />
{assign var=presenterFiles value=$submission->getPresenterFileRevisions($round)}
{assign var=editorFiles value=$submission->getEditorFileRevisions($round)}
{assign var="presenterRevisionExists" value=false}
{assign var="editorRevisionExists" value=false}

{if not $reviewingAbstractOnly}
	<table class="data" width="100%">
		{foreach from=$presenterFiles item=presenterFile key=key}
			<tr valign="top">
				{if !$presenterRevisionExists}
					{assign var="presenterRevisionExists" value=true}
					<td width="20%" rowspan="{$presenterFiles|@count}" class="label">{translate key="submission.presenterVersion"}</td>
				{/if}
				<td width="80%" class="value" colspan="3">
					{if $lastDecision == SUBMISSION_EDITOR_DECISION_RESUBMIT}
						<tr>
							<td width="20%">&nbsp;</td>
							<td width="80%">
								{translate key="editor.paper.resubmitFileForPeerReview"}
								<input type="submit" name="resubmit" {if !($editorRevisionExists or $presenterRevisionExists)}disabled="disabled" {/if}value="{translate key="form.resubmit"}" class="button" />
							</td>
						</tr>
					{elseif $lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT}
						<input type="radio" name="editorDecisionFile" value="{$presenterFile->getFileId()},{$presenterFile->getRevision()}" />
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
		{foreach from=$editorFiles item=editorFile key=key}
			<tr valign="top">
				{if !$editorRevisionExists}
					{assign var="editorRevisionExists" value=true}
					<td width="20%" rowspan="{$editorFiles|@count}" class="label">{translate key="submission.editorVersion"}</td>
				{/if}
				<td width="50%" class="value" colspan="2">
					{if $lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT}<input type="radio" name="editorDecisionFile" value="{$editorFile->getFileId()},{$editorFile->getRevision()}" /> {/if}<a href="{url op="downloadFile" path=$submission->getPaperId()|to_array:$editorFile->getFileId():$editorFile->getRevision()}" class="file">{$editorFile->getFileName()}</a>&nbsp;&nbsp;
					{$editorFile->getDateModified()|date_format:$dateFormatShort}
				</td>
				<td width="30%" class="value"><a href="{url op="deletePaperFile" path=$submission->getPaperId()|to_array:$editorFile->getFileId():$editorFile->getRevision()}" class="action">{translate key="common.delete"}</a></td>
			</tr>
		{foreachelse}
			<tr valign="top">
				<td width="20%" class="label">{translate key="submission.editorVersion"}</td>
				<td width="80%" colspan="3" class="nodata">{translate key="common.none"}</td>
			</tr>
		{/foreach}
	</table>

	{if $isCurrent}
	<div>
		{translate key="editor.paper.uploadEditorVersion"}
		<input type="file" name="upload" class="uploadField" />
		<input type="submit" name="submit" value="{translate key="common.upload"}" class="button" />
	</div>
	{/if}
{/if}

</form>
