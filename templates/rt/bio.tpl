{**
 * bio.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Paper reading tools -- author bio page.
 *
 * $Id$
 *}
{strip}
{assign var=pageTitle value="rt.authorBio"}
{include file="rt/header.tpl"}
{/strip}

<div id="authorBio">
<h3>{$paper->getLocalizedTitle()|strip_unsafe_html}</h3>

{foreach from=$paper->getAuthors() item=author name=authors}
<div id="author">
<p>
	<em>{$author->getFullName()|escape}</em><br />
	{if $author->getUrl()}<a href="{$author->getUrl()|escape:"quotes"}">{$author->getUrl()|escape}</a><br/>{/if}
	{if $author->getAffiliation()}{$author->getAffiliation()|escape|nl2br}{/if}
	{if $author->getCountry()}<br/>{$author->getCountryLocalized()|escape}{/if}
</p>

<p>{$author->getAuthorBiography()|strip_unsafe_html|nl2br}</p>
</author>
{if !$smarty.foreach.authors.last}<div class="separator"></div>{/if}

{/foreach}
</div>

{include file="rt/footer.tpl"}
