{**
 * presenterIndex.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Index of published papers by presenter.
 *
 * $Id$
 *}
{assign var="pageTitle" value="search.presenterIndex"}
{include file="common/header.tpl"}

<p>{foreach from=$alphaList item=letter}<a href="{url op="presenters" searchInitial=$letter}">{if $letter == $searchInitial}<strong>{$letter}</strong>{else}{$letter}{/if}</a> {/foreach}<a href="{url op="presenters"}">{if $searchInitial==''}<strong>{translate key="common.all"}</strong>{else}{translate key="common.all"}{/if}</a></p>

<a name="presenters"></a>

{iterate from=presenters item=presenter}
	{assign var=lastFirstLetter value=$firstLetter}
	{assign var=firstLetter value=$presenter->getLastName()|String_substr:0:1}

	{if $lastFirstLetter != $firstLetter}
		<a name="{$firstLetter|escape}"></a>
		<h3>{$firstLetter|escape}</h3>
	{/if}

	<a href="{url op="presenters" path="view" firstName=$presenter->getFirstName() middleName=$presenter->getMiddleName() lastName=$presenter->getLastName() affiliation=$presenter->getAffiliation()}">
		{$presenter->getLastName(true)|escape},
		{$presenter->getFirstName()|escape}{if $presenter->getMiddleName()} {$presenter->getMiddleName()|escape}{/if}{if $presenter->getAffiliation()}, {$presenter->getAffiliation()|escape}{/if}
	</a>
	<br/>
{/iterate}
{if !$presenters->wasEmpty()}
	<br />
	{page_info iterator=$presenters}&nbsp;&nbsp;&nbsp;&nbsp;{page_links anchor="presenters" iterator=$presenters name="presenters" searchInitial=$searchInitial}
{else}
{/if}

{include file="common/footer.tpl"}
