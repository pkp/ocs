{**
 * citation.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Paper reading tools -- Capture Citation for ABNT
 *
 * $Id$
 *}
<div class="separator"></div>
<div id="citation">
{assign var=authors value=$paper->getAuthors()}
{assign var=authorCount value=$authors|@count}
{foreach from=$authors item=author name=authors key=i}
	{assign var=firstName value=$author->getFirstName()}
	{$author->getLastName()|escape|upper}, {$firstName|escape|truncate:1:"":true}.{if $i<$authorCount-1}; {/if}{/foreach}.
{$paper->getLocalizedTitle()|strip_unsafe_html}.
<strong>{$conference->getConferenceTitle()|escape}</strong>, {translate key="plugins.citationFormat.abnt.location"},
{$paper->getDatePublished()|date_format:'%b. %Y'|lower}. {translate key="plugins.citationFormats.abnt.retrieved" retrievedDate=$smarty.now|date_format:'%d %b. %Y' url=$paperUrl}.
</div>