{**
 * citation.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Paper reading tools -- Capture Citation MLA format
 *
 * $Id$
 *}
<div class="separator"></div>

<div id="citation">
{assign var=authors value=$paper->getAuthors()}
{assign var=authorCount value=$authors|@count}
{foreach from=$authors item=author name=authors key=i}
	{assign var=firstName value=$author->getFirstName()}
	{$author->getLastName()|escape}, {$firstName|escape}{if $i==$authorCount-2}, {translate key="rt.context.and"} {elseif $i<$authorCount-1}, {else}.{/if}
{/foreach}

"{$paper->getLocalizedTitle()|strip_unsafe_html}" <em>{$conference->getConferenceTitle()|escape}</em> ({$paper->getDatePublished()|date_format:'%Y'}):{if $paper->getPages()} {$paper->getPages()}{else} {translate key="plugins.citationFormats.mla.noPages"}{/if} {translate key="rt.captureCite.web"}. {$smarty.now|date_format:'%e %b. %Y'}

</div>
