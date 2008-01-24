{**
 * schedule.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display the conference schedule.
 *
 * $Id$
 *}
{assign var="pageTitle" value="schedConf.schedule"}
{assign var="pageId" value="schedConf.schedule"}
{include file="common/header.tpl"}

<a name="top"></a>

<table id="scheduleTable" class="listing" width="100%" border="0">

{assign var=baseDatesCount value=$baseDates|@count}
<tr valign="top"><td colspan="{$baseDatesCount+1}" class="headseparator">&nbsp;</td></tr>

<tr valign="top" class="heading">
	<td width="10%">&nbsp;</td>
	{foreach from=$baseDates item=baseDate}
		<td width="{math equation="(100 - 10)/x" x=$baseDatesCount}%">{$baseDate|date_format:$dateFormatShort}</td>
	{/foreach}
</tr>

<tr valign="top"><td colspan="{$baseDatesCount+1}" class="headseparator">&nbsp;</td></tr>

{foreach name=boundaryTimes from=$boundaryTimes item=boundaryTime key=boundaryTimeKey}
{assign var=nextBoundaryTimeKey value=$boundaryTimeKey+1}
{assign var=nextBoundaryTime value=$boundaryTimes.$nextBoundaryTimeKey}
<tr valign="top"{if $nextBoundaryTime} style="height: {math equation="max((x - y)/1000, 3)" x=$nextBoundaryTime y=$boundaryTime}em;"{/if}>
	<td class="timeRowLabel">
		{$boundaryTime+$baseDate|date_format:$timeFormat}
	</td>
	{foreach from=$baseDates item=baseDate}
		{assign var=timeBlock value=$timeBlockGrid.$baseDate.$boundaryTime.timeBlockStarts}
		{if $timeBlock}{* This is an existing time block; display it and its contents *}
			{assign var="timeBlockId" value=$timeBlock->getTimeBlockId()}
			{assign var="rowspan" value=$timeBlockGrid.$baseDate.$boundaryTime.rowspan}
			<td id="TIME-{$timeBlockId|escape}" class="borderBox droppable"{if $rowspan} rowspan="{$rowspan|escape}"{/if}>
				{if empty($scheduledEventsByTimeBlockId[$timeBlockId]) && empty($scheduledPresentationsByTimeBlockId[$timeBlockId])}
					{$timeBlock->getTimeBlockName()|escape}
				{/if}
				{foreach from=$scheduledEventsByTimeBlockId.$timeBlockId item=event}
					{assign var="eventExists" value=1}
					<a href="#event-{$event->getSpecialEventId()|escape}">{$event->getSpecialEventName()|escape|truncate:35:"..."}</a><br />
				{/foreach}
				{foreach from=$scheduledPresentationsByTimeBlockId[$timeBlockId] item=presentation}
					{assign var="paperExists" value=1}
					<a href="#paper-{$presentation->getPaperId()|escape}">{$presentation->getPaperTitle()|escape|truncate:35:"..."}</a><br />
				{/foreach}
			</td>
		{elseif !$gridSlotUsed.$baseDate.$boundaryTime}{* This is a "hole" in the schedule *}
			<td class="hole">&nbsp</td>
		{else}{* This is a rowspanned part of a time block; do nothing. *}
		{/if}
	{/foreach}
</tr>

{/foreach}{* timeBlocks *}

</table>

{if $paperExists}
<h3>{translate key="schedConf.presentations.short"}</h3>

{foreach from=$scheduledPresentations item=paper}
<a name="paper-{$paper->getPaperId()|escape}"></a>
<h4>{$paper->getPaperTitle()}</h4>
<a href="#top">{translate key="manager.scheduler.returnToSchedule"}</a>
{/foreach}{* scheduledPresentations *}

{/if}{* paperExists *}

{if $eventExists}
<h3>{translate key="manager.scheduler.specialEvents"}</h3>

{foreach from=$scheduledEvents item=event}
<a name="event-{$event->getSpecialEventId()|escape}"></a>
<h4>{$event->getSpecialEventName()}</h4>
<a href="#top">{translate key="manager.scheduler.returnToSchedule"}</a>
{if $event->getSpecialEventDescription() != ''}<p>{$event->getSpecialEventDescription()}</p>{/if}
{/foreach}{* scheduledEvents *}

{/if}{* eventExists *}

{include file="common/footer.tpl"}
