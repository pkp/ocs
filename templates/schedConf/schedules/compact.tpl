{**
 * compact.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Scheduled conference schedule page (compact version).
 *
 * $Id$
 *}
{strip}
{translate|assign:"pageTitleTranslated" key="schedConf.schedule.title"}
{include file="common/header.tpl"}
{/strip}

{if !empty($buildingsAndRooms)}
	{* Display navigation options at the top of the page if buildings and
	 * rooms are used.
	 *}
	{if !$hideNav && !$hideLocations}
	<ul id="navigation" class="plain">
		<li>&#187; <a href="#locations">{translate key="schedConf.scheduler.locations"}</a></li>
		<li>&#187; <a href="#schedule">{translate key="schedConf.schedule"}</a></li>
	</ul>
	{/if}

{if !$hideLocations}
<div id="locations">
<h3>{translate key="schedConf.scheduler.locations"}</h3>

<ul>
{foreach from=$buildingsAndRooms item=entry key=buildingId}
	<li>
		<h4>{$entry.building->getBuildingName()|escape}</h4>
		{if $entry.building->getBuildingDescription() != ''}
			<p>{$entry.building->getBuildingDescription()}</p>
		{/if}
		{if !empty($entry.rooms)}
			<ul>
			{foreach from=$entry.rooms item=room}
				<li>
					<strong>{$room->getRoomName()}</strong>
					{if $room->getRoomDescription() != ''}
						 - {$room->getRoomDescription()}
					{/if}
				</li>
			{/foreach}
			</ul>
		{/if}{* !empty($entry.rooms) *}
	</li>
{/foreach}
</ul>

<div class="separator"></div>
</div>
{/if}

<div id="schedule">
<h3>{translate key="schedConf.schedule"}</h3>

<table class="listing" width="100%">
{assign var=lastStartTime value=0}
{assign var=needsTdClose value=0}
{foreach from=$itemsByTime item=list key=startTime}
	{foreach from=$list item=item}
		{assign var=endTime value=$item->getEndTime()}
		{if !$lastStartTime || $lastStartTime|date_format:$dateFormatShort != $startTime|date_format:$dateFormatShort}
			{if $needsTdClose}
				</td>
				{assign var=needsTdClose value=0}
			{/if}
			<tr><td><h5>{$startTime|date_format:$dateFormatShort}</h5></td></tr>
		{/if}
		{if $lastStartTime|date_format:$datetimeFormatShort != $startTime|date_format:$datetimeFormatShort}
			{if $needsTdClose}
				</td>
				{assign var=needsTdClose value=0}
			{/if}
			<tr><td width="15%">{$startTime|date_format:$timeFormat} {if $showEndTime}- {$endTime|date_format:$timeFormat}{/if}</td>
			{assign var=needsTdClose value=1}
		{/if}
		{if (get_class($item) == 'SpecialEvent' || get_class($item) == 'specialevent')}
			<td width="85%">
				<strong>{$item->getSpecialEventName()|escape}</strong>{if $item->getSpecialEventDescription() != ''}:&nbsp;{$item->getSpecialEventDescription()}{/if}
			</td>
		{else}
			<td width="85%">
				{if $showAuthors} {$item->getAuthorString()|escape}, {/if}<a class="action" href="{url page="paper" op="view" path=$item->getBestPaperId()}">{$item->getLocalizedTitle()|escape}</a>
				{assign var=roomId value=$item->getRoomId()}
				{if $roomId && $allRooms[$roomId]}
					{assign var=room value=$allRooms[$roomId]}
					{assign var=buildingId value=$room->getBuildingId()}
					{assign var=building value=$buildingsAndRooms.$buildingId.building}
					{if $building && $buildingsAndRooms|@count != 1}<br/>{translate key="manager.scheduler.building"}:&nbsp;{$building->getBuildingName()|escape}{/if}
					<br/>{translate key="manager.scheduler.room"}:&nbsp;{$room->getRoomName()}
				{/if}
			</td>
		{/if}
		{assign var=lastStartTime value=$startTime}
		</tr>
	{/foreach}
{/foreach}
{if $needsTdClose}
	</td>
{/if}
</table>
</div>
{/if}{* !empty($buildingsAndRooms) *}

{include file="common/footer.tpl"}
