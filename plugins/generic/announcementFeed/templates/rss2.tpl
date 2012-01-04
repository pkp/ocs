{**
 * rss2.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RSS 2 feed template
 *
 * $Id$
 *}
<?xml version="1.0" encoding="{$defaultCharset|escape}"?>
<rss version="2.0">
	<channel>
		{* required elements *}
		<title>{if $schedConf}{$schedConf->getSchedConfTitle()|escape:"html"|strip}{else}{$conference->getConferenceTitle()|escape:"html"|strip}{/if}: {translate key="announcement.announcements"}</title>
		<link>{if $schedConf}{$schedConf->getUrl()|escape}{else}{$conference->getUrl()|escape}{/if}</link>
		{if $schedConf && $schedConf->getSchedConfIntroduction()}
			{assign var="description" value=$schedConf->getSchedConfIntroduction()}
		{elseif $conference->getConferenceDescription()}
			{assign var="description" value=$conference->getConferenceDescription()}
		{/if}
		<description>{$description|escape:"html"|strip}</description>

		{* optional elements *}
	    {if $conference->getPrimaryLocale()}
	    <language>{$conference->getPrimaryLocale()|replace:'_':'-'|strip|escape:"html"}</language>
	    {/if}
		<pubDate>{$dateUpdated|date_format:"%a, %d %b %Y %T %z"}</pubDate>
		<generator>OCS {$ocsVersion|escape}</generator>
		<docs>http://blogs.law.harvard.edu/tech/rss</docs>
		<ttl>60</ttl>

		{foreach from=$announcements item=announcement}
			<item>
				{* required elements *}
				<title>{$announcement->getLocalizedTitleFull()|strip|escape:"html"}</title>
				<link>{url page="announcement" op="view" path=$announcement->getId()}</link>
				<description>{$announcement->getLocalizedDescription()|strip|escape:"html"}</description>

				{* optional elements *}
				<guid isPermaLink="true">{url page="announcement" op="view" path=$announcement->getId()}</guid>
				<pubDate>{$announcement->getDatetimePosted()|date_format:"%a, %d %b %Y %T %z"}</pubDate>
			</item>
		{/foreach}
	</channel>
</rss>
