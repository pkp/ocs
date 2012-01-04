{**
 * titleIndex.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display published papers by title
 *
 * $Id$
 *}
{strip}
{assign var=pageTitle value="search.titleIndex"}
{include file="common/header.tpl"}
{/strip}

<br />

{foreach from=$conferenceIndex item=conference key=conferenceId}
	<div id="conference">
	<h3><a href="{url conference=$conference->getPath() page="index"}">{$conference->getConferenceTitle()|escape}</a></h3>
	{assign var=description value=$conference->getLocalizedSetting('description')}
	{if !empty($description)}
		<p>{$description|nl2br}</p>
	{/if}
	{if !empty($schedConfIndex[$conferenceId])}
		<ul>
			{foreach from=$schedConfIndex[$conferenceId] item=schedConf}
				{assign var=introduction value=$schedConf->getLocalizedSetting('introduction')}
				<li>
					<h4>{$schedConf->getSchedConfTitle()|escape}</h4>
					{if $schedConf->getSetting('startDate')}{$schedConf->getSetting('startDate')|date_format:$dateFormatLong} &ndash; {$schedConf->getSetting('endDate')|date_format:$dateFormatLong}{/if}
					{if !empty($introduction)}
						<p>{$introduction|nl2br}</p>
					{/if}
				</li>
			{/foreach}
		</ul>
	{/if}
	</div>
{/foreach}

{include file="common/footer.tpl"}
