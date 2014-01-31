{**
 * submissionEventLog.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show submission scheduled conference log page.
 *
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="event.eventLog"}
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
	<li class="current"><a href="{url op="submissionEventLog" path=$submission->getPaperId()}">{translate key="submission.history.submissionEventLog"}</a></li>
	<li><a href="{url op="submissionEmailLog" path=$submission->getPaperId()}">{translate key="submission.history.submissionEmailLog"}</a></li>
	<li><a href="{url op="submissionNotes" path=$submission->getPaperId()}">{translate key="submission.history.submissionNotes"}</a></li>
</ul>

{include file="trackDirector/submission/summary.tpl"}

<div class="separator"></div>

<div id="eventLogEntries">
<h3>{translate key="submission.history.submissionEventLog"}</h3>
<table width="100%" class="listing">
	<tr><td class="headseparator" colspan="6">&nbsp;</td></tr>
	<tr valign="top" class="heading">
		<td width="5%">{translate key="common.date"}</td>
		<td width="5%">{translate key="event.logLevel"}</td>
		<td width="5%">{translate key="common.type"}</td>
		<td width="25%">{translate key="common.user"}</td>
		<td>{translate key="common.event"}</td>
		<td width="56" align="right">{translate key="common.action"}</td>
	</tr>
	<tr><td class="headseparator" colspan="6">&nbsp;</td></tr>
{iterate from=eventLogEntries item=logEntry}
	<tr valign="top">
		<td>{$logEntry->getDateLogged()|date_format:$dateFormatTrunc}</td>
		<td>{$logEntry->getLogLevel()|escape}</td>
		<td>{$logEntry->getAssocTypeString()}</td>
		<td>
			{assign var=emailString value=$logEntry->getUserFullName()|concat:" <":$logEntry->getUserEmail():">"}
			{translate|assign:"bodyContent" key=$logEntry->getMessage() params=$logEntry->getEntryParams()}
			{translate|assign:"titleTrans" key=$logEntry->getEventTitle()}
			{if $logEntry->getIsTranslated()}
				{url|assign:"url" page="user" op="email" to=$emailString|to_array redirectUrl=$currentUrl subject=$titleTrans body=$bodyContent paperId=$submission->getPaperId()}
			{else}{* Legacy entries *}
				{url|assign:"url" page="user" op="email" to=$emailString|to_array redirectUrl=$currentUrl subject=$titleTrans|translate body=$logEntry->getMessage() paperId=$submission->getPaperId()}
			{/if}
			{$logEntry->getUserFullName()|escape} {icon name="mail" url=$url}
		</td>
		<td>
			<strong>{translate key=$logEntry->getEventTitle()|escape}</strong>
			<br />
			{if $logEntry->getIsTranslated()}
				{translate key=$logEntry->getMessage() params=$logEntry->getEntryParams()|strip_tags|truncate:60:"..."|escape}
			{else}{* Legacy entries *}
				{$logEntry->getMessage()|strip_tags|truncate:60:"..."|escape}
			{/if}
		</td>
		<td align="right">{if $logEntry->getAssocType()}<a href="{url op="submissionEventLogType" path=$submission->getPaperId()|to_array:$logEntry->getAssocType():$logEntry->getAssocId()}" class="action">{translate key="common.related"}</a>&nbsp;|&nbsp;{/if}<a href="{url op="submissionEventLog" path=$submission->getPaperId()|to_array:$logEntry->getLogId()}" class="action">{translate key="common.view"}</a>{if $isDirector}&nbsp;|&nbsp;<a href="{url op="clearSubmissionEventLog" path=$submission->getPaperId()|to_array:$logEntry->getLogId()}" class="action" onclick="return confirm('{translate|escape:"jsparam" key="submission.event.confirmDeleteLogEntry"}')" class="icon">{translate key="common.delete"}</a>{/if}</td>
	</tr>
	<tr valign="top">
		<td colspan="6" class="{if $eventLogEntries->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $eventLogEntries->wasEmpty()}
	<tr valign="top">
		<td colspan="6" class="nodata">{translate key="submission.history.noLogEntries"}</td>
	</tr>
	<tr valign="top">
		<td colspan="6" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="3" align="left">{page_info iterator=$eventLogEntries}</td>
		<td colspan="3" align="right">{page_links anchor="eventLogEntries" name="eventLogEntries" iterator=$eventLogEntries}</td>
	</tr>
{/if}
</table>

{if $isDirector}
<a href="{url op="clearSubmissionEventLog" path=$submission->getPaperId()}" class="action" onclick="return confirm('{translate|escape:"jsparam" key="submission.event.confirmClearLog"}')">{translate key="submission.history.clearLog"}</a>
{/if}
</div>
{include file="common/footer.tpl"}
