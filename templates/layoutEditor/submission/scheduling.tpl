{**
 * scheduling.tpl
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the scheduling view.
 *
 * $Id$
 *}

<a name="scheduling"></a>
<h3>{translate key="submission.scheduling"}</h3>

{if $event}
	{assign var=eventName value=$event->getEventIdentification()}
{else}
	{translate|assign:"eventName" key="submission.scheduledIn.tba"}
{/if}

{translate key="submission.scheduledIn" eventName=$eventName}

{if $event}
	<a href="{url page="event" op="view" path=$event->getBestEventId()}" class="action">{translate key="event.toc"}</a>
{/if}
