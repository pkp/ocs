{**
 * templates/trackDirector/submission/directorDecision.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the director decision table.
 *
 *}
<div id="directorDecision">
<h3>{translate key="submission.directorDecision"}</h3>

<table width="100%" class="data">
<tr valign="top">
	<td class="label" width="20%">{translate key="director.paper.selectDecision"}</td>
	<td width="80%" class="value" colspan="2">
		<form class="pkp_form" method="post" action="{url op="recordDecision" path=$round}">
			<input type="hidden" name="paperId" value="{$submission->getId()}" />
			<select name="decision" size="1" class="selectMenu"{if not $allowRecommendation} disabled="disabled"{/if}>
				{assign var=availableDirectorDecisionOptions value=$submission->getDirectorDecisionOptions($currentSchedConf,$round)}
				{html_options_translate options=$availableDirectorDecisionOptions selected=$lastDecision}
			</select>
			<input type="submit" onclick="return confirm('{translate|escape:"jsparam" key="director.submissionReview.confirmDecision"}')" name="submit" value="{translate key="director.paper.recordDecision"}" {if not $allowRecommendation}disabled="disabled"{/if} class="button" />
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
	<td class="label">{translate key="submission.notifyAuthor"}</td>
	<td class="value" colspan="2">
		{url|assign:"notifyAuthorUrl" op="emailDirectorDecisionComment" paperId=$submission->getId()}
		{icon name="mail" url=$notifyAuthorUrl}
		&nbsp;&nbsp;&nbsp;&nbsp;
		{translate key="submission.directorAuthorRecord"}
		{if $submission->getMostRecentDirectorDecisionComment()}
			{assign var="comment" value=$submission->getMostRecentDirectorDecisionComment()}
			<a href="javascript:openComments('{url op="viewDirectorDecisionComments" path=$submission->getId() anchor=$comment->getId()}');" class="icon">{icon name="comment"}</a>&nbsp;&nbsp;{$comment->getDatePosted()|date_format:$dateFormatShort}
		{else}
			<a href="javascript:openComments('{url op="viewDirectorDecisionComments" path=$submission->getId()}');" class="icon">{icon name="comment"}</a>{translate key="common.noComments"}
		{/if}
		{if $lastDecision == SUBMISSION_DIRECTOR_DECISION_DECLINE}
			<br />
			{if $submission->getStatus() == STATUS_ARCHIVED}{translate key="submissions.archived"}{else}<a href="{url op="archiveSubmission" path=$submission->getId()}" onclick="return window.confirm('{translate|escape:"jsparam" key="director.submissionReview.confirmToArchive"}')" class="action">{translate key="director.paper.sendToArchive"}</a>{/if}
			{if $submission->getDateToArchive()}{$submission->getDateToArchive()|date_format:$dateFormatShort}{/if}
		{/if}
	</td>
</tr>
</table>
</div>
<form class="pkp_form" method="post" action="{url op="directorReview" path=$round}" enctype="multipart/form-data">
<input type="hidden" name="paperId" value="{$submission->getId()}" />
{assign var=authorFiles value=$submission->getAuthorFileRevisions($round)}
{assign var=directorFiles value=$submission->getDirectorFileRevisions($round)}
{assign var=reviewFile value=$submission->getReviewFile()}
{assign var="authorRevisionExists" value=false}
{assign var="directorRevisionExists" value=false}
{assign var="sendableVersionExists" value=false}

{if not $reviewingAbstractOnly}
	<table class="data" width="100%">
		{if $reviewFile}
			<tr valign="top">
				<td width="20%" class="label">{translate key="submission.reviewVersion"}</td>
				<td width="50%" colspan="2" class="value">
					{if $lastDecision == SUBMISSION_DIRECTOR_DECISION_ACCEPT}
						<input type="radio" name="directorDecisionFile" value="{$reviewFile->getFileId()},{$reviewFile->getRevision()}" />
						{assign var="sendableVersionExists" value=true}
					{/if}
					<a href="{url op="downloadFile" path=$submission->getId()|to_array:$reviewFile->getFileId():$reviewFile->getRevision()}" class="file">{$reviewFile->getFileName()|escape}</a>&nbsp;&nbsp;
					{$reviewFile->getDateModified()|date_format:$dateFormatShort}
				</td>
			</tr>
		{/if}
		{foreach from=$authorFiles item=authorFile key=key}
			<tr valign="top">
				{if !$authorRevisionExists}
					{assign var="authorRevisionExists" value=true}
					<td width="20%" rowspan="{$authorFiles|@count}" class="label">{translate key="submission.authorVersion"}</td>
				{/if}
				<td width="80%" class="value" colspan="2">
					{if $lastDecision == SUBMISSION_DIRECTOR_DECISION_ACCEPT}
						<input type="radio" name="directorDecisionFile" value="{$authorFile->getFileId()},{$authorFile->getRevision()}" />
						{assign var="sendableVersionExists" value=true}
					{/if}
					<a href="{url op="downloadFile" path=$submission->getId()|to_array:$authorFile->getFileId():$authorFile->getRevision()}" class="file">{$authorFile->getFileName()|escape}</a>&nbsp;&nbsp;
						{$authorFile->getDateModified()|date_format:$dateFormatShort}
				</td>
			</tr>
		{foreachelse}
			<tr valign="top">
				<td width="20%" class="label">{translate key="submission.authorVersion"}</td>
				<td width="80%" colspan="2" class="nodata">{translate key="common.none"}</td>
			</tr>
		{/foreach}
		{foreach from=$directorFiles item=directorFile key=key}
			<tr valign="top">
				{if !$directorRevisionExists}
					{assign var="directorRevisionExists" value=true}
					<td width="20%" rowspan="{$directorFiles|@count}" class="label">{translate key="submission.directorVersion"}</td>
				{/if}
				<td width="50%" class="value">
					{if $lastDecision == SUBMISSION_DIRECTOR_DECISION_ACCEPT}
						<input type="radio" name="directorDecisionFile" value="{$directorFile->getFileId()},{$directorFile->getRevision()}" />
						{assign var="sendableVersionExists" value=true}
					{/if}
					<a href="{url op="downloadFile" path=$submission->getId()|to_array:$directorFile->getFileId():$directorFile->getRevision()}" class="file">{$directorFile->getFileName()|escape}</a>&nbsp;&nbsp;
					{$directorFile->getDateModified()|date_format:$dateFormatShort}
				</td>
				<td width="30%" class="value"><a href="{url op="deletePaperFile" path=$submission->getId()|to_array:$directorFile->getFileId():$directorFile->getRevision()}" class="action">{translate key="common.delete"}</a></td>
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

	{if $sendableVersionExists}
		<table class="data" width="100%">
			<tr valign="top">
				<td width="20%">&nbsp;</td>
				<td width="80%">
					{translate key="director.paper.moveToLayout"}
					<input type="submit" name="setEditingFile" onclick="return window.confirm('{translate|escape:"jsparam" key="director.submissionReview.confirmToLayout"}')" value="{translate key="form.send"}" class="button" />
					{if $submission->getDateToPresentations()}{$submission->getDateToPresentations()|date_format:$dateFormatShort}{/if}
					{if !$submission->getGalleys()}
						<br />
						<input type="checkbox" checked="checked" name="createGalley" value="1" />
						{translate key="director.paper.createGalley"}
					{/if}
				</td>
			</tr>
		</table>

	{/if}
{/if}
</form>

{if $isFinalReview}

	<div class="separator"></div>

	{include file="trackDirector/submission/complete.tpl"}

	<div class="separator"></div>

	{include file="trackDirector/submission/layout.tpl"}
{/if}


