{**
 * plugins/generic/announcementFeed/templates/rss.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * RSS feed template
 *
 *}
<?xml version="1.0" encoding="{$defaultCharset|escape}"?>
<rdf:RDF
	xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
	xmlns="http://purl.org/rss/1.0/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:prism="http://prismstandard.org/namespaces/1.2/basic/">
    
	<channel rdf:about="{if $schedConf}{url conference=$conference->getPath() schedConf=$schedConf->getPath()}{else}{url conference=$conference->getPath()}{/if}">
		{* required elements *}
		<title>{if $schedConf}{$schedConf->getLocalizedName()|strip|escape:"html"}{else}{$conference->getLocalizedName()|strip|escape:"html"}{/if}: {translate key="announcement.announcements"}</title>
		<link>{if $schedConf}{url conference=$conference->getPath() schedConf=$schedConf->getPath()}{else}{url conference=$conference->getPath()}{/if}</link>
		{if $schedConf && $schedConf->getLocalizedIntroduction()}
			{assign var="description" value=$schedConf->getLocalizedIntroduction()}
		{elseif $conference->getLocalizedDescription()}
			{assign var="description" value=$conference->getLocalizedDescription()}
		{/if}
		<description>{$description|strip|escape:"html"}</description>

		{* optional elements *}
		{if $conference->getPrimaryLocale()}
		<dc:language>{$conference->getPrimaryLocale()|replace:'_':'-'|strip|escape:"html"}</dc:language>
		{/if}

		<items>
			{foreach from=$announcements item=announcement}
			<rdf:Seq>
				<rdf:li rdf:resource="{url page="announcement" op="view" path=$announcement->getId()}"/>
			</rdf:Seq>
			{/foreach}
		</items>
	</channel>

{foreach from=$announcements item=announcement}
	<item rdf:about="{url page="announcement" op="view" path=$announcement->getId()}">
		{* required elements *}
		<title>{$announcement->getLocalizedTitleFull()|strip|escape:"html"}</title>
		<link>{url page="announcement" op="view" path=$announcement->getId()}</link>

		{* optional elements *}
		{if $announcement->getLocalizedDescription()}
		<description>{$announcement->getLocalizedDescription()|strip|escape:"html"}</description>
		{/if}
		<dc:creator>{if $schedConf}{$schedConf->getLocalizedName()|strip|escape:"html"}{else}{$conference->getLocalizedName()|strip|escape:"html"}{/if}</dc:creator>
		<dc:date>{$announcement->getDatePosted()|date_format:"%Y-%m-%d"}</dc:date>
	</item>
{/foreach}

</rdf:RDF>
