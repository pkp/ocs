{**
 * citation.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Paper reading tools -- Capture Citation CBE format
 *
 * $Id$
 *}
<div class="separator"></div>

<div id="citation">
{assign var=authors value=$paper->getAuthors()}
{assign var=authorCount value=$authors|@count}
{foreach from=$authors item=author name=authors key=i}
	{assign var=firstName value=$author->getFirstName()}
	{$author->getLastName()|escape}, {$firstName|escape|truncate:1:"":true}.{if $i==$authorCount-2}, &amp; {elseif $i<$authorCount-1}, {/if}
{/foreach}

{$paper->getDatePublished()|date_format:'%Y %b %e'}. {$paper->getLocalizedTitle()|strip_unsafe_html}. {$conference->getConferenceTitle()|escape}. [{translate key="rt.captureCite.online"}]
</div>
