{**
 * rss.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RSS feed template
 *
 * $Id$
 *}
<?xml version="1.0" encoding="{$defaultCharset|escape}"?>
<rdf:RDF
	xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
	xmlns="http://purl.org/rss/1.0/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:prism="http://prismstandard.org/namespaces/1.2/basic/">
    
	<channel rdf:about="{if $schedConf}{$schedConf->getUrl()}{else}{$conference->getUrl()}{/if}">
		{* required elements *}
		<title>{if $schedConf}{$schedConf->getSchedConfTitle()|escape:"html"|strip}{else}{$conference->getConferenceTitle()|escape:"html"|strip}{/if}: {translate key="announcement.announcements"}</title>
		<link>{if $schedConf}{$schedConf->getUrl()}{else}{$conference->getUrl()}{/if}</link>
		{if $schedConf && $schedConf->getSchedConfIntroduction()}
			{assign var="description" value=$schedConf->getSchedConfIntroduction()}
		{elseif $conference->getConferenceDescription()}
			{assign var="description" value=$conference->getConferenceDescription()}
		{/if}
		<description>{$description|escape:"html"|strip}</description>

		{* optional elements *}
		{if $conference->getPrimaryLocale()}
		<dc:language>{$conference->getPrimaryLocale()|replace:'_':'-'|escape:"html"|strip}</dc:language>
		{/if}

		<items>
			{foreach from=$announcements item=announcement}
			<rdf:Seq>
				<rdf:li rdf:resource="{url page="announcement" op="view" path=$announcement->getAnnouncementId()}"/>
			</rdf:Seq>
			{/foreach}
		</items>
	</channel>

{foreach from=$announcements item=announcement}
	<item rdf:about="{url page="announcement" op="view" path=$announcement->getAnnouncementId()}">
		{* required elements *}
		<title>{$announcement->getAnnouncementTitleFull()|strip|escape:"html"}</title>
		<link>{url page="announcement" op="view" path=$announcement->getAnnouncementId()}</link>

		{* optional elements *}
		{if $announcement->getAnnouncementDescription()}
		<description>{$announcement->getAnnouncementDescription()|strip|escape:"html"}</description>
		{/if}
		<dc:creator>{if $schedConf}{$schedConf->getSchedConfTitle()|escape:"html"|strip}{else}{$conference->getConferenceTitle()|escape:"html"|strip}{/if}</dc:creator>
		<dc:date>{$announcement->getDatePosted()|date_format:"%Y-%m-%d"}</dc:date>
	</item>
{/foreach}

</rdf:RDF>
