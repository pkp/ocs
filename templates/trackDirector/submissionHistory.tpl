{**
 * submissionHistory.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show submission history page.
 *
 *
 * $Id$
 *}
{translate|assign:"pageTitleTranslated" key="submission.page.history" id=$submission->getPaperId()}{assign var="pageCrumbTitle" value="submission.history"}
{include file="common/header.tpl"}

{literal}
<script type="text/javascript">
<!--
	var toggleAll = 0;
	var noteArray = new Array();
	function toggleNote(divNoteId) {
		var domStyle = getBrowserObject(divNoteId,1);
		domStyle.display = (domStyle.display == "block") ? "none" : "block";
	}

	function toggleNoteAll() {
		for(var i = 0; i < noteArray.length; i++) {
			var domStyle = getBrowserObject(noteArray[i],1);
			domStyle.display = toggleAll ? "none" : "block";
		}
		toggleAll = toggleAll ? 0 : 1;

		var collapse = getBrowserObject("collapseNotes",1);
		var expand = getBrowserObject("expandNotes",1);
		if (collapse.display == "inline") {
			collapse.display = "none";
			expand.display = "inline";
		} else {
			collapse.display = "inline";
			expand.display = "none";
		}
	}
// -->
</script>
{/literal}

<ul class="menu">
	<li><a href="{url op="submission" path=$submission->getPaperId()}">{translate key="submission.summary"}</a></li>
	{if $reviewMode == REVIEW_MODE_BOTH_SEQUENTIAL}
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
	<li><a href="{url op="submissionEmailLog" path=$submission->getPaperId()}">{translate key="submission.history.submissionEmailLog"}</a></li>
	<li><a href="{url op="submissionNotes" path=$submission->getPaperId()}">{translate key="submission.history.submissionNotes"}</a></li>
</ul>

{include file="trackDirector/submission/summary.tpl"}

<div class="separator"></div>

<a name="eventLogEntries"></a>

<h3>{translate key="submission.history.submissionEventLog"} - {translate key="submission.history.recentLogEntries"}</h3>
<table width="100%" class="listing">
	<tr><td class="headseparator" colspan="6">&nbsp;</td></tr>
	<tr class="heading" valign="bottom">
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
		<td>{$logEntry->getDateLogged()|date_format:$dateFormatShort}</td>
		<td>{$logEntry->getLogLevel()}</td>
		<td>{$logEntry->getAssocTypeString()|escape}</td>
		<td>
			{assign var=emailString value="`$logEntry->getUserFullName()` <`$logEntry->getUserEmail()`>"}
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
				{translate key=$logEntry->getMessage() params=$logEntry->getEntryParams()|strip_unsafe_html|truncate:60:"..."|escape}
			{else}{* Legacy entries *}
				{$logEntry->getMessage()|strip_unsafe_html|truncate:60:"..."|escape}
			{/if}
		</td>
		<td align="right">{if $logEntry->getAssocType()}<a href="{url op="submissionEventLogType" path=$submission->getPaperId()|to_array:$logEntry->getAssocType():$logEntry->getAssocId()}" class="action">{translate key="common.related"}</a>&nbsp;|&nbsp;{/if}<a href="{url op="submissionEventLog" path=$submission->getPaperId()|to_array:$logEntry->getLogId()}" class="action">{translate key="common.view"}</a>{if $isDirector}&nbsp;|&nbsp;<a href="{url op="clearSubmissionEventLog" path=$submission->getPaperId()|to_array:$logEntry->getLogId()}" class="action" onclick="return confirm('{translate|escape:"jsparam" key="submission.event.confirmDeleteLogEntry"}')">{translate key="common.delete"}</a>{/if}</td>
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

<a href="{url op="submissionEventLog" path=$submission->getPaperId()}" class="action">{translate key="submission.history.viewLog"}</a>{if $isDirector} |
<a href="{url op="clearSubmissionEventLog" path=$submission->getPaperId()}" class="action" onclick="return confirm('{translate|escape:"jsparam" key="submission.event.confirmClearLog"}')">{translate key="submission.history.clearLog"}</a>{/if}

<br /><br />

<div class="separator"></div>

<a name="emailLogEntries"></a>

<h3>{translate key="submission.history.submissionEmailLog"} - {translate key="submission.history.recentLogEntries"}</h3>

<table width="100%" class="listing">
	<tr><td class="headseparator" colspan="6">&nbsp;</td></tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.date"}</td>
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
		<td>{if $logEntry->getAssocType()}<a href="{url op="submissionEmailLogType" path=$submission->getPaperId()|to_array:$logEntry->getAssocType():$logEntry->getAssocId()}" class="action">{translate key="common.related"}</a>&nbsp;|&nbsp;{/if}<a href="{url op="submissionEmailLog" path=$submission->getPaperId()|to_array:$logEntry->getLogId()}" class="action">{translate key="common.view"}</a>{if $isDirector}&nbsp;|&nbsp;<a href="{url op="clearSubmissionEmailLog" path=$submission->getPaperId()|to_array:$logEntry->getLogId()}" onclick="return confirm('{translate|escape:"jsparam" key="submission.email.confirmDeleteLogEntry"}')" class="action">{translate key="common.delete"}</a>{/if}</td>
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
		<td colspan="3" align="right">{page_links anchor="emailLogEntries" name="emailLogEntries" iterator=$emailLogEntries}</td>
	</tr>
{/if}
</table>

<a class="action" href="{url op="submissionEmailLog" path=$submission->getPaperId()}">{translate key="submission.history.viewLog"}</a>{if $isDirector} |
<a class="action" href="{url op="clearsubmissionEmailLog" path=$submission->getPaperId()}" onclick="return confirm('{translate|escape:"jsparam" key="submission.email.confirmClearLog"}')">{translate key="submission.history.clearLog"}</a>{/if}

<br /><br />

<div class="separator"></div>

<a name="submissionNotes"></a>

<h3>{translate key="submission.notes"}</h3>

<table width="100%" class="listing">
	<tr><td colspan="6" class="headseparator">&nbsp;</td></tr>
	<tr class="heading" valign="bottom">
		<td width="5%">{translate key="common.date"}</td>
		<td width="60%">{translate key="common.title"}</td>
		<td width="25%">{translate key="submission.notes.attachedFile"}</td>
		<td width="10%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr><td colspan="6" class="headseparator">&nbsp;</td></tr>
{iterate from=submissionNotes item=note}
	<script type="text/javascript">
		<!--
		noteArray.push({$note->getNoteId()});
		// -->
	</script>
	<tr valign="top">
		<td>{$note->getDateCreated()|date_format:$dateFormatShort}</td>
		<td><a class="action" href="javascript:toggleNote({$note->getNoteId()})">{$note->getTitle()|escape}</a><div style="display: none" id="{$note->getNoteId()}" name="{$note->getNoteId()}">{$note->getNote()|strip_unsafe_html|nl2br}</div></td>
		<td>{if $note->getFileId()}<a class="action" href="{url op="downloadFile" path=$submission->getPaperId()|to_array:$note->getFileId()}">{$note->getOriginalFileName()}</a>{else}&mdash;{/if}</td>
		<td align="right"><a href="{url op="submissionNotes" path=$submission->getPaperId()|to_array:"edit":$note->getNoteId()}" class="action">{translate key="common.view"}</a>&nbsp;|&nbsp;<a href="{url op="removeSubmissionNote" paperId=$submission->getPaperId() noteId=$note->getNoteId() fileId=$note->getFileId()}" onclick="return confirm('{translate|escape:"jsparam" key="submission.notes.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
	</tr>
	<tr valign="top">
		<td colspan="6" class="{if $submissionNotes->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $submissionNotes->wasEmpty()}
	<tr valign="top">
		<td colspan="6" class="nodata">{translate key="submission.notes.noSubmissionNotes"}</td>
	</tr>
	<tr valign="top">
		<td colspan="6" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="3" align="left">{page_info iterator=$submissionNotes}</td>
		<td colspan="3" align="right">{page_links anchor="submissionNotes" name="submissionNotes" iterator=$submissionNotes}</td>
	</tr>
{/if}
</table>

<a class="action" href="{url op="submissionNotes" path=$submission->getPaperId()}">{translate key="submission.notes.viewNotes"}</a> |
<a class="action" href="javascript:toggleNoteAll()"><div style="display:inline" id="expandNotes" name="expandNotes">{translate key="submission.notes.expandNotes"}</div><div style="display: none" id="collapseNotes" name="collapseNotes">{translate key="submission.notes.collapseNotes"}</div></a> |
<a class="action" href="{url op="submissionNotes" path=$submission->getPaperId()|to_array:"add"}">{translate key="submission.notes.addNewNote"}</a> |
<a class="action" href="{url op="clearAllSubmissionNotes" paperId=$submission->getPaperId()}" onclick="return confirm('{translate|escape:"jsparam" key="submission.notes.confirmDeleteAll"}')">{translate key="submission.notes.clearAllNotes"}</a>

{include file="common/footer.tpl"}
