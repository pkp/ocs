{**
 * citation.tpl
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Paper reading tools -- Capture Citation for ABNT
 *
 * $Id$
 *}
<div class="separator"></div>

{assign var=presenters value=$paper->getPresenters()}
{assign var=presenterCount value=$presenters|@count}
{foreach from=$presenters item=presenter name=presenters key=i}
	{assign var=firstName value=$presenter->getFirstName()}
	{$presenter->getLastName()|escape|upper}, {$firstName[0]|escape}.{if $i<$presenterCount-1}; {/if}{/foreach}.
{$paper->getPaperTitle()|strip_unsafe_html}.
<strong>{$conference->getConferenceTitle()|escape}</strong>, {translate key="plugins.citationFormat.abnt.location"},
{$paper->getDatePublished()|date_format:'%b. %Y'|lower}. {translate key="plugins.citationFormats.abnt.retrieved" retrievedDate=$smarty.now|date_format:'%d %b. %Y' url=$paperUrl}.
