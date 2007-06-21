{**
 * citation.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- Capture Citation for ABNT
 *
 * $Id$
 *}

<div class="separator"></div>

{assign var=presenters value=$paper->getPresenters()}
{assign var=presenterCount value=$presenters|@count}
{foreach from=$presenters item=presenter name=presenters key=i}
	{assign var=firstName value=$presenter->getFirstName()}
	{$presenter->getLastName()|escape}, {$firstName[0]|escape}.{if $i<$presenterCount-1}; {/if}{/foreach}.
{$paper->getPaperTitle()|strip_unsafe_html}.
<b>{$conference->getTitle()|escape}</b>, {translate key="plugins.citationFormat.acao.location"}
{$paper->getDatePublished()|date_format:'%e %m %Y'}.

