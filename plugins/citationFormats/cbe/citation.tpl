{**
 * citation.tpl
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Paper reading tools -- Capture Citation CBE format
 *
 * $Id$
 *}
<div class="separator"></div>

{assign var=presenters value=$paper->getPresenters()}
{assign var=presenterCount value=$presenters|@count}
{foreach from=$presenters item=presenter name=presenters key=i}
	{assign var=firstName value=$presenter->getFirstName()}
	{$presenter->getLastName()|escape}, {$firstName[0]|escape}.{if $i==$presenterCount-2}, &amp; {elseif $i<$presenterCount-1}, {/if}
{/foreach}

{$paper->getDatePublished()|date_format:'%Y %b %e'}. {$paper->getPaperTitle()|strip_unsafe_html}. {$conference->getConferenceTitle()|escape}. [{translate key="rt.captureCite.online"}] 

