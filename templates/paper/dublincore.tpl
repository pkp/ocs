{**
 * dublincore.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Dublin Core metadata elements for papers.
 *
 * $Id$
 *}
<link rel="schema.DC" href="http://purl.org/dc/elements/1.1/" />

{* DC.Contributor.PersonalName (reviewer) *}
{if $paper->getSponsor(null)}{foreach from=$paper->getSponsor(null) key=metaLocale item=metaValue}
	<meta name="DC.Contributor.Sponsor" xml:lang="{$metaLocale|String_substr:0:2|escape}" content="{$metaValue|strip_tags|escape}"/>
{/foreach}{/if}
{if $paper->getCoverageSample(null)}{foreach from=$paper->getCoverageSample(null) key=metaLocale item=metaValue}
	<meta name="DC.Coverage" xml:lang="{$metaLocale|String_substr:0:2|escape}" content="{$metaValue|strip_tags|escape}"/>
{/foreach}{/if}
{if $paper->getCoverageGeo(null)}{foreach from=$paper->getCoverageGeo(null) key=metaLocale item=metaValue}
	<meta name="DC.Coverage.spatial" xml:lang="{$metaLocale|String_substr:0:2|escape}" content="{$metaValue|strip_tags|escape}"/>
{/foreach}{/if}
{if $paper->getCoverageChron(null)}{foreach from=$paper->getCoverageChron(null) key=metaLocale item=metaValue}
	<meta name="DC.Coverage.temporal" xml:lang="{$metaLocale|String_substr:0:2|escape}" content="{$metaValue|strip_tags|escape}"/>
{/foreach}{/if}
{foreach from=$paper->getAuthorString()|explode:", " item=dc_author}
	<meta name="DC.Creator.PersonalName" content="{$dc_author|escape}"/>
{/foreach}
{if $currentSchedConf->getSetting('delayOpenAccessDate')}
	<meta name="DC.Date.available" scheme="ISO8601" content="{$currentSchedConf->getSetting('delayOpenAccessDate')|date_format:"%Y-%m-%d"}"/>
{/if}
	<meta name="DC.Date.created" scheme="ISO8601" content="{$paper->getDatePublished()|date_format:"%Y-%m-%d"}"/>
{* DC.Date.dateAccepted (editor submission DAO) *}
{* DC.Date.dateCopyrighted *}
{* DC.Date.dateReveiwed (revised file DAO) *}
	<meta name="DC.Date.dateSubmitted" scheme="ISO8601" content="{$paper->getDateSubmitted()|date_format:"%Y-%m-%d"}"/>
	<meta name="DC.Date.modified" scheme="ISO8601" content="{$paper->getDateStatusModified()|date_format:"%Y-%m-%d"}"/>
{if $paper->getAbstract(null)}{foreach from=$paper->getAbstract(null) key=metaLocale item=metaValue}
	<meta name="DC.Description" xml:lang="{$metaLocale|String_substr:0:2|escape}" content="{$metaValue|strip_tags|escape}"/>
{/foreach}{/if}
{foreach from=$paper->getGalleys() item=dcGalley}
	<meta name="DC.Format" scheme="IMT" content="{$dcGalley->getFileType()|escape}"/>		
{/foreach}
	<meta name="DC.Identifier" content="{$paper->getBestPaperId($currentConference)|escape}"/>
{if $paper->getPages()}
	<meta name="DC.Identifier.pageNumber" content="{$paper->getPages()|escape}"/>
{/if}
	<meta name="DC.Identifier.URI" content="{url page="paper" op="view" path=$paper->getBestPaperId($currentConference)}"/>
	<meta name="DC.Language" scheme="ISO639-1" content="{$paper->getLanguage()|strip_tags|escape}"/>
{* DC.Publisher (publishing institution) *}
{* DC.Publisher.Address (email addr) *}
{if $currentConference->getLocalizedSetting('copyrightNotice')}
	<meta name="DC.Rights" content="{$currentConference->getLocalizedSetting('copyrightNotice')|strip_tags|escape}"/>
{/if}
{* DC.Rights.accessRights *}
	<meta name="DC.Source" content="{$currentSchedConf->getFullTitle()|strip_tags|escape}"/>
	<meta name="DC.Source.URI" content="{url page="index"|strip_tags|escape}"/>
{if $paper->getSubject(null)}{foreach from=$paper->getSubject(null) key=metaLocale item=metaValue}
	{foreach from=$metaValue|explode:"; " item=dcSubject}
		{if $dcSubject}
			<meta name="DC.Subject" xml:lang="{$metaLocale|String_substr:0:2|escape}" content="{$dcSubject|escape}"/>
		{/if}
	{/foreach}
{/foreach}{/if}
	<meta name="DC.Title" content="{$paper->getLocalizedTitle()|strip_tags|escape}"/>
{foreach from=$paper->getTitle(null) item=alternate key=metaLocale}
	{if $alternate != $paper->getLocalizedTitle()}
		<meta name="DC.Title.Alternative" xml:lang="{$metaLocale|String_substr:0:2|escape}" content="{$alternate|strip_tags|escape}"/>
	{/if}
{/foreach}
	<meta name="DC.Type" content="Text.Proceedings"/>
	<meta name="DC.Type.paperType" content="{$paper->getTrackTitle()|strip_tags|escape}"/>	
