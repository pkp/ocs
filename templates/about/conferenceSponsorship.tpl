{**
 * conferenceSponsorship.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Conference / Conference Sponsorship.
 *
 * $Id$
 *}
<div id="conferenceSponsorship">
{if not(empty($publisher.note) && empty($publisher.institution))}
<div class="separator"></div>

<h3>{translate key="common.publisher"}</h3>

{if $publisher.note}<p>{$publisher.note|nl2br}</p>{/if}

<p><a href="{$publisher.url}">{$publisher.institution|escape}</a></p>

{/if}

{if not (empty($sponsorNote) && empty($sponsors))}
<div class="separator"></div>

<h3>{translate key="about.sponsors"}</h3>

{if $sponsorNote}<p>{$sponsorNote|nl2br}</p>{/if}

<ul>
	{foreach from=$sponsors item=sponsor}
	{if $sponsor.institution}
		{if $sponsor.url}
			<li><a href="{$sponsor.url|escape}">{$sponsor.institution|escape}</a></li>
		{else}
			<li>{$sponsor.institution|escape}</li>
		{/if}
	{/if}
	{/foreach}
</ul>

{/if}

{if !empty($contributorNote) || (!empty($contributors) && !empty($contributors[0].name))}
<div class="separator"></div>

<h3>{translate key="about.contributors"}</h3>

{if $contributorNote}<p>{$contributorNote|nl2br}</p>{/if}

<ul>
	{foreach from=$contributors item=contributor}
	{if $contributor.name}
		{if $contributor.url}
			<li><a href="{$contributor.url|escape}">{$contributor.name|escape}</a></li>
		{else}
			<li>{$contributor.name|escape}</li>
		{/if}
	{/if}
	{/foreach}
</ul>
{/if}
</div>
