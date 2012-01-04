{**
 * submissionEmailLog.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show submission email log page.
 *
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="submission.emailLog"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li><a href="{url op="submission" path=$submission->getPaperId()}">{translate key="submission.summary"}</a></li>
	{if $submission->getReviewMode() == REVIEW_MODE_BOTH_SEQUENTIAL}
		<li><a href="{url op="submissionReview" path=$submission->getPaperId()}">
			{translate key="submission.abstractReview"}</a>
		</li>
		<li><a href="{url op="submissionReview" path=$submission->getPaperId()}">
			{translate key="submission.paperReview"}</a>
		</li>
	{else}
		<li><a href="{url op="submissionReview" path=$submission->getPaperId()}">{translate key="submission.review"}</a></li>
	{/if}
	<li class="current"><a href="{url op="submissionHistory" path=$submission->getPaperId()}">{translate key="submission.history"}</a></li>
</ul>

<ul class="menu">
	<li><a href="{url op="submissionEventLog" path=$submission->getPaperId()}">{translate key="submission.history.submissionEventLog"}</a></li>
	<li class="current"><a href="{url op="submissionEmailLog" path=$submission->getPaperId()}">{translate key="submission.history.submissionEmailLog"}</a></li>
	<li><a href="{url op="submissionNotes" path=$submission->getPaperId()}">{translate key="submission.history.submissionNotes"}</a></li>
</ul>

{include file="trackDirector/submission/summary.tpl"}

<div class="separator"></div>

	<div id="emailLogEntries">

<h3>{translate key="submission.history.submissionEmailLog"}</h3>

<table width="100%" class="listing">
	<tr><td class="headseparator" colspan="6">&nbsp;</td></tr>
	<tr valign="top" class="heading">
		<td width="7%">{translate key="common.date"}</td>
		<td width="5%">{translate key="common.type"}</td>
		<td width="25%">{translate key="email.sender"}</td>
		<td width="20%">{translate key="email.recipients"}</td>
		<td>{translate key="common.subject"}</td>
		<td width="60" align="right">{translate key="common.action"}</td>
	</tr>
	<tr><td class="headseparator" colspan="6">&nbsp;</td></tr>
{iterate from=emailLogEntries item=logEntry}
	<tr valign="top">
		<td>{$logEntry->getDateSent()|date_format:$dateFormatShort}</td>
		<td>{$logEntry->getAssocTypeString()|escape}</td>
		<td>
			{url|assign:"url" page="user" op="email" to=$logEntry->getFrom() redirectUrl=$currentUrl subject=$logEntry->getSubject() body=$logEntry->getBody() paperId=$submission->getPaperId()}
			{$logEntry->getFrom()|truncate:40:"..."|escape} {icon name="mail" url=$url}
		</td>
		<td>{$logEntry->getRecipients()|truncate:40:"..."|escape}</td>
		<td><strong>{$logEntry->getSubject()|truncate:60:"..."|escape}</strong></td>
		<td align="right">{if $logEntry->getAssocType()}<a href="{url op="submissionEmailLogType" path=$submission->getPaperId()|to_array:$logEntry->getAssocType():$logEntry->getAssocId()}" class="action">{translate key="common.related"}</a>&nbsp;|&nbsp;{/if}<a href="{url op="submissionEmailLog" path=$submission->getPaperId()|to_array:$logEntry->getLogId()}" class="action">{translate key="common.view"}</a>{if $isDirector}&nbsp;|&nbsp;<a href="{url op="clearSubmissionEmailLog" path=$submission->getPaperId()|to_array:$logEntry->getLogId()}" onclick="return confirm('{translate|escape:"jsparam" key="submission.email.confirmDeleteLogEntry"}')" class="action">{translate key="common.delete"}</a>{/if}</td>
	</tr>
	<tr valign="top">
		<td colspan="6" class="{if $emailLogEntries->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $emailLogEntries->wasEmpty()}
	<tr valign="top">
		<td colspan="6" class="nodata">{translate key="submission.history.noLogEntries"}</td>
	</tr>
	<tr valign="top">
		<td colspan="6" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="3" align="left">{page_info iterator=$emailLogEntries}</td>
		<td colspan="2" align="right">{page_links anchor="emailLogEntries" name="emailLogEntries" iterator=$emailLogEntries}</td>
	</tr>
{/if}
</table>

{if $isDirector}
<a class="action" href="{url op="clearSubmissionEmailLog" path=$submission->getPaperId()}" onclick="return confirm('{translate|escape:"jsparam" key="submission.email.confirmClearLog"}')">{translate key="submission.history.clearLog"}</a>
{/if}
</div>
{include file="common/footer.tpl"}
