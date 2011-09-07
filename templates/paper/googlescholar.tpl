{**
 * templates/paper/googlescholar.tpl
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Metadata elements for papers based on preferred types for Google Scholar
 *
 * $Id$
 *}
	<meta name="gs_meta_revision" content="1.1" />
	<meta name="citation_conference_title" content="{$currentSchedConf->getFullTitle()|strip_tags|escape}"/>
	<meta name="citation_authors" content="{foreach name="authors" from=$paper->getAuthors() item=author}{$author->getLastName()|escape}, {$author->getFirstName()|escape}{if $author->getMiddleName() != ""} {$author->getMiddleName()|escape}{/if}{if !$smarty.foreach.authors.last}; {/if}{/foreach}"/>
	<meta name="citation_title" content="{$paper->getLocalizedTitle()|strip_tags|escape}"/>
	<meta name="citation_date" content="{$paper->getDatePublished()|date_format:"%Y/%m/%d"}"/>
	<meta name="citation_abstract_html_url" content="{url page="paper" op="view" path=$paper->getBestPaperId($currentConference)}"/>
{foreach from=$paper->getGalleys() item=dc_galley}
{if $dc_galley->getFileType()=="application/pdf"}
	<meta name="citation_pdf_url" content="{url page="paper" op="download" path=$paper->getBestPaperId($currentConference)|to_array:$dc_galley->getGalleyId()}"/>
{else}
	<meta name="citation_fulltext_html_url" content="{url page="paper" op="view" path=$paper->getBestPaperId($currentConference)|to_array:$dc_galley->getGalleyId()}"/>
{/if}
{/foreach}

