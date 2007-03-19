{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Conference index page. Displayed when a conference, but not a scheduled
 * conference, has been selected.
 *
 * $Id$
 *}

{assign var="pageTitleTranslated" value=$conferenceTitle}
{include file="common/header.tpl"}

{* Display scheduled conferences. *}
<h3>{translate key="conference.archive"}</h3>
{if not $schedConfs->eof()}
	{iterate from=schedConfs item=schedConf}
		<h4><a href="{url schedConf=$schedConf->getPath()}">{$schedConf->getFullTitle()|escape}</a></h4>
		<p>
			{$schedConf->getSetting('locationName')|escape}<br/>
			{$schedConf->getSetting('locationAddress')|nl2br}<br/>
			{if $schedConf->getSetting('locationCity')}{$schedConf->getSetting('locationCity')|escape}{assign var="needsComma" value=true}{/if}{if $schedConf->getSetting('locationCountry')}{if $needsComma}, {/if}{$schedConf->getSetting('locationCountry')|escape}{/if}
		</p>
		<p>{$schedConf->getSetting('startDate')|date_format:$dateFormatLong} &ndash; {$schedConf->getSetting('endDate')|date_format:$dateFormatLong}</p>
		{if $schedConf->getSetting('schedConfIntroduction')}
			<p>{$schedConf->getSetting('schedConfIntroduction')|nl2br}</p>
		{/if}
		<p><a href="{url schedConf=$schedConf->getPath()}" class="action">{translate key="site.schedConfView"}</a> | <a href="{url schedConf=$schedConf->getPath() page="user" op="register"}" class="action">{translate key="site.conferenceRegister"}</a></p>
	{/iterate}
{else}
	{translate key="conference.noCurrentConferences"}
{/if}


{include file="common/footer.tpl"}
