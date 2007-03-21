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

{if $enableAnnouncementsHomepage}
	{* Display announcements *}
	<br />
	<center><h3>{translate key="announcement.announcementsHome"}</h3></center>
	{include file="announcement/list.tpl"}	
	<table width="100%">
		<tr>
			<td>&nbsp;</td>
		<tr>
			<td align="right"><a href="{url page="announcement"}">{translate key="announcement.moreAnnouncements"}</a></td>
		</tr>
	</table>
{/if}

{if $homepageImage}
<div align="center"><img src="{$publicFilesDir}/{$homepageImage.uploadName|escape}" width="{$homepageImage.width}" height="{$homepageImage.height}" border="0" alt="" /></div>
{/if}

<br /><br />

{$additionalHomeContent}

{* Display current scheduled conferences. *}
<h3>{translate key="conference.currentConferences"}</h3>
{if not $currentSchedConfs->eof()}
	{iterate from=currentSchedConfs item=schedConf}
		<h4><a href="{url schedConf=$schedConf->getPath()}">{$schedConf->getFullTitle()|escape}</a></h4>
		<p>
			{$schedConf->getSetting('locationName')}<br/>
			{$schedConf->getSetting('locationAddress')|nl2br}<br/>
			{if $schedConf->getSetting('locationCity')}{$schedConf->getSetting('locationCity')|escape}{assign var="needsComma" value=true}{/if}{if $schedConf->getSetting('locationCountry')}{if $needsComma}, {/if}{$schedConf->getSetting('locationCountry')|escape}{/if}
		</p>
		<p>{$schedConf->getSetting('startDate')|date_format:$dateFormatLong} &ndash; {$schedConf->getSetting('endDate')|date_format:$dateFormatLong}</p>
		{if $schedConf->getSetting('schedConfIntroduction')}
			<p>{$schedConf->getSetting('schedConfIntroduction')|nl2br}</p>
		{/if}
		<p><a href="{url schedConf=$schedConf->getPath()}" class="action">{translate key="site.schedConfView"}</a> | <a href="{url schedConf=$schedConf->getPath() page="user" op="account"}" class="action">{translate key="site.conferenceAccount"}</a></p>
	{/iterate}
{else}
	{translate key="conference.noCurrentConferences"}
{/if}


{include file="common/footer.tpl"}
