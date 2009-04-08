{**
 * presenterIndex.tpl
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Index of published papers by presenter.
 *
 * $Id$
 *}
{assign var="pageTitle" value="search.presenterIndex"}
{include file="common/header.tpl"}

<p>{foreach from=$alphaList item=letter}<a href="{url op="presenters" searchInitial=$letter}">{if $letter == $searchInitial}<strong>{$letter|escape}</strong>{else}{$letter|escape}{/if}</a> {/foreach}<a href="{url op="presenters"}">{if $searchInitial==''}<strong>{translate key="common.all"}</strong>{else}{translate key="common.all"}{/if}</a></p>

<a name="presenters"></a>

{iterate from=presenters item=presenter}
	{assign var=lastFirstLetter value=$firstLetter}
	{assign var=firstLetter value=$presenter->getLastName()|String_substr:0:1}

	{if $lastFirstLetter != $firstLetter}
		<a name="{$firstLetter|escape}"></a>
		<h3>{$firstLetter|escape}</h3>
	{/if}

	{assign var=lastPresenterName value=$presenterName}
	{assign var=lastPresenterCountry value=$presenterCountry}

	{assign var=presenterAffiliation value=$presenter->getAffiliation()}
	{assign var=presenterCountry value=$presenter->getCountry()}

	{assign var=presenterFirstName value=$presenter->getFirstName()}
	{assign var=presenterMiddleName value=$presenter->getMiddleName()}
	{assign var=presenterLastName value=$presenter->getLastName()}
	{assign var=presenterName value="$presenterLastName, $presenterFirstName"}

	{if $presenterMiddleName != ''}{assign var=presenterName value="$presenterName $presenterMiddleName"}{/if}
	{strip}
		<a href="{url op="presenters" path="view" firstName=$presenterFirstName middleName=$presenterMiddleName lastName=$presenterLastName affiliation=$presenterAffiliation country=$presenterCountry}">{$presenterName|escape}</a>
		{if $presenterAffiliation}, {$presenterAffiliation|escape}{/if}
		{if $lastPresenterName == $presenterName && $lastPresenterCountry != $presenterCountry}
			{* Disambiguate with country if necessary (i.e. if names are the same otherwise) *}
			{if $presenterCountry} ({$presenter->getCountryLocalized()}){/if}
		{/if}
	{/strip}
	<br/>
{/iterate}
{if !$presenters->wasEmpty()}
	<br />
	{page_info iterator=$presenters}&nbsp;&nbsp;&nbsp;&nbsp;{page_links anchor="presenters" iterator=$presenters name="presenters" searchInitial=$searchInitial}
{else}
{/if}

{include file="common/footer.tpl"}
