{**
 * proofread.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the presenter's proofreading table.
 *
 * $Id$
 *}

<a name="proofread"></a>
<h3>{translate key="submission.proofreading"}</h3>

{if $useProofreaders}
<table width="100%" class="data">
	<tr>
		<td class="label" width="20%">{translate key="user.role.proofreader"}</td>
		<td class="value" width="80%">{if $proofAssignment->getProofreaderId()}{$proofAssignment->getProofreaderFullName()|escape}{else}{translate key="common.none"}{/if}</td>
	</tr>
</table>
{/if}

<table width="100%" class="info">
	<tr>
		<td width="40%" colspan="2">&nbsp;</td>
		<td width="20%" class="heading">{translate key="submission.request"}</td>
		<td width="20%" class="heading">{translate key="submission.underway"}</td>
		<td width="20%" class="heading">{translate key="submission.complete"}</td>
	</tr>
	<tr>
		<td width="5%">1.</td>
		<td width="35%">{translate key="user.role.presenter"}</td>
		<td>{$proofAssignment->getDatePresenterNotified()|date_format:$dateFormatShort|default:"&mdash;"}</td>
		<td>{$proofAssignment->getDatePresenterUnderway()|date_format:$dateFormatShort|default:"&mdash;"}</td>
				<td>
			{if not $proofAssignment->getDatePresenterNotified() or $proofAssignment->getDatePresenterCompleted()}
				{icon name="mail" disabled="disabled"}
			{else}
				{url|assign:"url" op="presenterProofreadingComplete" paperId=$submission->getPaperId()}
				{icon name="mail" url=$url}
			{/if}
			{$proofAssignment->getDatePresenterCompleted()|date_format:$dateFormatShort|default:""}
		</td>
	</tr>
	<tr>
		<td>2.</td>
		<td>{translate key="user.role.proofreader"}</td>
		<td>{$proofAssignment->getDateProofreaderNotified()|date_format:$dateFormatShort|default:"&mdash;"}</td>
		<td>{$proofAssignment->getDateProofreaderUnderway()|date_format:$dateFormatShort|default:"&mdash;"}</td>
		<td>{$proofAssignment->getDateProofreaderCompleted()|date_format:$dateFormatShort|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="5" class="separator">&nbsp;</td>
	</tr>
</table>

{translate key="submission.proofread.corrections"}
{if $submission->getMostRecentProofreadComment()}
        {assign var="comment" value=$submission->getMostRecentProofreadComment()}
        <a href="javascript:openComments('{url op="viewProofreadComments" path=$submission->getPaperId() anchor=$comment->getCommentId()}');" class="icon">{icon name="comment"}</a>{$comment->getDatePosted()|date_format:$dateFormatShort}
{else}
        <a href="javascript:openComments('{url op="viewProofreadComments" path=$submission->getPaperId()}');" class="icon">{icon name="comment"}</a>
{/if}
