{**
 * citation.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- Capture Citation
 *
 * $Id$
 *}
<div class="separator"></div>

{assign var=presenters value=$paper->getPresenters()}
{assign var=presenterCount value=$presenters|@count}
{foreach from=$presenters item=presenter name=presenters key=i}
	{assign var=firstName value=$presenter->getFirstName()}
	{$presenter->getLastName()|escape}, {$firstName|escape}{if $i==$presenterCount-2}, {translate key="rt.context.and"} {elseif $i<$presenterCount-1}, {else}.{/if}
{/foreach}

"{$paper->getPaperTitle()|strip_unsafe_html}" <i>{$schedConf->getTitle()|escape}</i> [{translate key="rt.captureCite.online"}],  ({$paper->getDatePublished()|date_format:'%e %B %Y'|trim})

