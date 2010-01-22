{**
 * atom.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Atom feed template
 *
 * $Id$
 *}
<?xml version="1.0" encoding="{$defaultCharset|escape}"?>
<feed xmlns="http://www.w3.org/2005/Atom">
	{* required elements *}
	<id>{$selfUrl}</id>
	<title>{if $schedConf}{$schedConf->getSchedConfTitle()|escape:"html"|strip}{else}{$conference->getConferenceTitle()|escape:"html"|strip}{/if}: {translate key="announcement.announcements"}</title>
	<updated>{$dateUpdated|date_format:"%Y-%m-%dT%T%z"|regex_replace:"/00$/":":00"}</updated>

	{* recommended elements *}
	{* <author/> *}
	<link rel="alternate" href="{if $schedConf}{$schedConf->getUrl()}{else}{$conference->getUrl()}{/if}" />
	<link rel="self" type="application/atom+xml" href="{$selfUrl}" />

	{* optional elements *}
	{* <category/> *}
	{* <contributor/> *}
	<generator uri="http://pkp.sfu.ca/ocs/" version="{$ocsVersion|escape}">Open Conference Systems</generator>
	{if $schedConf && $schedConf->getSchedConfIntroduction()}
		{assign var="description" value=$schedConf->getSchedConfIntroduction()}
	{elseif $conference->getConferenceDescription()}
		{assign var="description" value=$conference->getConferenceDescription()}
	{/if}
	{if $description}
	<subtitle>{$description|strip|escape:"html"}</subtitle>
	{/if}

{foreach from=$announcements item=announcement}
	<entry>
		{* required elements *}
		<id>{url page="announcement" op="view" path=$announcement->getAnnouncementId()}</id>
		<title>{$announcement->getAnnouncementTitleFull()|strip|escape:"html"}</title>
		<updated>{$announcement->getDatetimePosted()|date_format:"%Y-%m-%dT%T%z"|regex_replace:"/00$/":":00"}</updated>
	  	<author>
			<name>{$conference->getConferenceTitle()|escape:"html"|strip}</name>
        </author>
		<link rel="alternate" href="{url page="announcement" op="view" path=$announcement->getAnnouncementId()}" />
        {if $announcement->getAnnouncementDescription()}
		<summary type="html" xml:base="{url page="announcement" op="view" path=$announcement->getAnnouncementId()}">{$announcement->getAnnouncementDescription()|strip|escape:"html"}</summary>
        {/if}

		{* optional elements *}
		{* <category/> *}
		{* <contributor/> *}
		<published>{$announcement->getDatetimePosted()|date_format:"%Y-%m-%dT%T%z"|regex_replace:"/00$/":":00"}</published>
		{* <source/> *}
		{* <rights/> *}
	</entry>
{/foreach}
</feed>
