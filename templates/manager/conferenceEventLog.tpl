{**
 * conferenceEventLog.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show conference log page.
 *
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="event.eventLog"}
{include file="common/header.tpl"}
{/strip}

<div id="eventLogEntries">
<h3>{translate key="conference.history.conferenceEventLog"}</h3>
<table width="100%" class="listing">
	<tr><td class="headseparator" colspan="7">&nbsp;</td></tr>
	<tr valign="top" class="heading">
		<td width="12%">{translate key="schedConf.schedConf"}</td>
		<td width="5%">{translate key="common.date"}</td>
		<td width="5%">{translate key="event.logLevel"}</td>
		<td width="5%">{translate key="common.type"}</td>
		<td width="25%">{translate key="common.user"}</td>
		<td>{translate key="common.event"}</td>
		<td width="56" align="right">{translate key="common.action"}</td>
	</tr>
	<tr><td class="headseparator" colspan="7">&nbsp;</td></tr>
{iterate from=eventLogEntries item=logEntry}
	<tr valign="top">
		<td>{$logEntry->getSchedConfTitle()|escape}</td>
		<td>{$logEntry->getDateLogged()|date_format:$dateFormatTrunc}</td>
		<td>{$logEntry->getLogLevel()|escape}</td>
		<td>{$logEntry->getAssocTypeString()}</td>
		<td>
			{assign var=emailString value=$logEntry->getUserFullName()|concat:" <":$logEntry->getUserEmail():">"}
			{translate|assign:"bodyContent" key=$logEntry->getMessage() params=$logEntry->getEntryParams()}
			{translate|assign:"titleTrans" key=$logEntry->getEventTitle()}
			{if $logEntry->getIsTranslated()}
				{url|assign:"url" page="user" op="email" to=$emailString|to_array redirectUrl=$currentUrl subject=$titleTrans body=$bodyContent}			
			{else}{* Legacy entries *}
				{url|assign:"url" page="user" op="email" to=$emailString|to_array redirectUrl=$currentUrl subject=$titleTrans|translate body=$logEntry->getMessage()}
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
		<td align="right">{if $logEntry->getAssocType()}<a href="{url op="conferenceEventLogType" path=$logEntry->getAssocType()|to_array:$logEntry->getAssocId()}" class="action">{translate key="common.related"}</a>&nbsp;|&nbsp;{/if}<a href="{url op="conferenceEventLog" path=$logEntry->getLogId()}" class="action">{translate key="common.view"}</a>&nbsp;|&nbsp;<a href="{url op="clearConferenceEventLog" path=$logEntry->getLogId()}" class="action" onclick="return confirm('{translate|escape:"jsparam" key="conference.event.confirmDeleteLogEntry"}')" class="icon">{translate key="common.delete"}</a></td>
	</tr>
	<tr valign="top">
		<td colspan="7" class="{if $eventLogEntries->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $eventLogEntries->wasEmpty()}
	<tr valign="top">
		<td colspan="7" class="nodata">{translate key="conference.history.noLogEntries"}</td>
	</tr>
	<tr valign="top">
		<td colspan="7" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="3" align="left">{page_info iterator=$eventLogEntries}</td>
		<td colspan="4" align="right">{page_links anchor="eventLogEntries" name="eventLogEntries" iterator=$eventLogEntries}</td>
	</tr>
{/if}
</table>

<a href="{url op="clearConferenceEventLog"}" class="action" onclick="return confirm('{translate|escape:"jsparam" key="conference.event.confirmClearLog"}')">{translate key="conference.history.clearLog"}</a>
</div>
{include file="common/footer.tpl"}
