{**
 * titleIndex.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display published papers by title
 *
 * $Id$
 *}

{assign var=pageTitle value="search.titleIndex"}

{include file="common/header.tpl"}

<br />

{foreach from=$conferenceIndex item=conference key=conferenceId}
	<h3><a href="{url conference=$conference->getPath() page="index"}">{$conference->getTitle()|escape}</a></h3>
	{assign var=conferenceDescription value=$conference->getSetting('conferenceDescription')}
	{if !empty($conferenceDescription)}
		<p>{$conferenceDescription|nl2br}</p>
	{/if}
	{if !empty($schedConfIndex[$conferenceId])}
		<ul>
			{foreach from=$schedConfIndex[$conferenceId] item=schedConf}
				{assign var=schedConfIntroduction value=$schedConf->getSetting('schedConfIntroduction')}
				<li>
					<h4>{$schedConf->getTitle()|escape}</h4>
					{if $schedConf->getSetting('startDate')}{$schedConf->getSetting('startDate')|date_format:$dateFormatLong} &ndash; {$schedConf->getSetting('endDate')|date_format:$dateFormatLong}{/if}
					{if !empty($schedConfIntroduction)}
						<p>{$schedConfIntroduction|nl2br}</p>
					{/if}
				</li>
			{/foreach}
		</ul>
	{/if}
{/foreach}

{include file="common/footer.tpl"}
