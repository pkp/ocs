{**
 * index.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Conference index page. Displayed when a conference, but not a scheduled
 * conference, has been selected.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitleTranslated" value=$conferenceTitle}
{include file="common/header.tpl"}
{/strip}
<div id="currentConferences">
{* Display scheduled conferences. *}
<h3>{translate key="conference.currentConferences"}</h3>
{if not $schedConfs->eof()}
	{iterate from=schedConfs item=schedConf}
		<h4><a href="{url schedConf=$schedConf->getPath()}">{$schedConf->getFullTitle()|escape}</a></h4>
		<p>
			{if $schedConf->getSetting('locationName')}{$schedConf->getSetting('locationName')|escape}<br />{/if}
			{if $schedConf->getSetting('locationAddress')}{$schedConf->getSetting('locationAddress')|nl2br}<br />{/if}
			{if $schedConf->getSetting('locationCity')}{$schedConf->getSetting('locationCity')|escape}{assign var="needsComma" value=true}{/if}{if $schedConf->getSetting('locationCountry')}{if $needsComma}, {/if}{$schedConf->getSetting('locationCountry')|escape}{/if}
		</p>
		<p>{$schedConf->getSetting('startDate')|date_format:$dateFormatLong} &ndash; {$schedConf->getSetting('endDate')|date_format:$dateFormatLong}</p>
		{if $schedConf->getLocalizedSetting('introduction')}
			<p>{$schedConf->getLocalizedSetting('introduction')|nl2br}</p>
		{/if}
		<p><a href="{url schedConf=$schedConf->getPath()}" class="action">{translate key="site.schedConfView"}</a></p>
	{/iterate}
{else}
	{translate key="conference.noCurrentConferences"}
{/if}
</div>
{include file="common/footer.tpl"}
