{**
 * conferenceEventLogEntry.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show a single event log entry.
 *
 *
 * $Id$
 *}
{assign var="pageTitle" value="event.eventLog"}
{include file="common/header.tpl"}

<h3>{translate key="conference.history.conferenceEventLog"}</h3>
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="common.id"}</td>
		<td width="80%" class="value">{$logEntry->getLogID()}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="schedConf.schedConf"}</td>
		<td class="value">{$logEntry->getSchedConfTitle()|escape}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.date"}</td>
		<td class="value">{$logEntry->getDateLogged()|date_format:$datetimeFormatLong}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="event.logLevel"}</td>
		<td class="value">{translate key=`$logEntry->getLogLevelString()`}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.type"}</td>
		<td class="value">{translate key=$logEntry->getAssocTypeLongString()}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.user"}</td>
		<td class="value">
			{assign var=emailString value="`$logEntry->getUserFullName()` <`$logEntry->getUserEmail()`>"}
			{url|assign:"url" page="user" op="email" to=$emailString|to_array redirectUrl=$currentUrl subject=$logEntry->getEventTitle()}
			{$logEntry->getUserFullName()|escape} {icon name="mail" url=$url}
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.event"}</td>
		<td class="value">
			<strong>{translate key=$logEntry->getEventTitle()|escape}</strong>
			<br /><br />
			{$logEntry->getMessage()|strip_unsafe_html|nl2br}
		</td>
	</tr>
</table>
{if $isDirector}
	<a href="{url op="clearConferenceEventLog" path=$logEntry->getLogId()}" onclick="return confirm('{translate|escape:"jsparam" key="conference.event.confirmDeleteLogEntry"}')" class="action">{translate key="conference.event.deleteLogEntry"}</a><br/>
{/if}

<a class="action" href="{url op="conferenceEventLog"}">{translate key="conference.event.backToEventLog"}</a>

{include file="common/footer.tpl"}
