{**
 * bio.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Paper reading tools -- presenter bio page.
 *
 * $Id$
 *}
{assign var=pageTitle value="rt.presenterBio"}
{include file="rt/header.tpl"}

<h3>{$paper->getPaperTitle()|strip_unsafe_html}</h3>

{foreach from=$paper->getPresenters() item=presenter name=presenters}
<p>
	<i>{$presenter->getFullName()|escape}</i><br />
	{if $presenter->getUrl()}<a href="{$presenter->getUrl()|escape:"quotes"}">{$presenter->getUrl()|escape}</a><br/>{/if}
	{if $presenter->getAffiliation()}{$presenter->getAffiliation()|escape}{/if}
	{if $presenter->getCountry()}<br/>{$presenter->getCountryLocalized()|escape}{/if}
</p>

<p>{$presenter->getPresenterBiography()|strip_unsafe_html|nl2br}</p>

{if !$smarty.foreach.presenters.last}<div class="separator"></div>{/if}

{/foreach}

{include file="rt/footer.tpl"}
