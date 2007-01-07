{**
 * events.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of events in site administration.
 *
 * $Id$
 *}

{assign var="pageTitle" value="event.scheduledConferences"}
{include file="common/header.tpl"}

<br />

<table width="100%" class="listing">
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	<tr valign="top" class="heading">
		<td width="35%">{translate key="director.events.scheduledConference"}</td>
		<td width="35%">{translate key="director.events.eventStartDate"}</td>
		<td width="10%">{translate key="common.order"}</td>
		<td width="20%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	{iterate from=events item=event}
	<tr valign="top">
		<td><a class="action" href="{url event=$event->getPath() page="director"}">{$event->getTitle()|escape}</a></td>
		<td>{$event->getStartDate()|date_format:$dateFormatShort}</td>
		<td><a href="{url op="moveEvent" d=u eventId=$event->getEventId()}">&uarr;</a> <a href="{url op="moveEvent" d=d eventId=$event->getEventId()}">&darr;</a></td>

		<td align="right">
			<a href="{url op="editEvent" path=$conference->getConferenceId()|to_array:$event->getEventId()}" class="action">
				{translate key="common.edit"}
			</a>
			&nbsp;|&nbsp;
			<a class="action" href="{url op="deleteEvent" path=$event->getEventId()}" onclick="return confirm('{translate|escape:"javascript" key="director.events.confirmDelete"}')">
				{translate key="common.delete"}
			</a>
		</td>
	</tr>
	<tr>
		<td colspan="4" class="{if $smarty.foreach.events.last}end{/if}separator">&nbsp;</td>
	</tr>
	{/iterate}
	{if $events->wasEmpty()}
	<tr>
		<td colspan="4" class="nodata">{translate key="director.events.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="4" class="endseparator">&nbsp;</td>
	<tr>
	{else}
		<tr>
			<td colspan="2" align="left">{page_info iterator=$events}</td>
			<td colspan="2" align="right">{page_links name="events" iterator=$events}</td>
		</tr>
	{/if}
</table>

<p><a href="{url op="createEvent"}" class="action">{translate key="director.events.create"}</a></p>

{include file="common/footer.tpl"}
