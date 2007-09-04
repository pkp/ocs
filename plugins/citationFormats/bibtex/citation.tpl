{**
 * citation.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- Capture Citation BibTeX format
 *
 * $Id$
 *}
<div class="separator"></div>

{literal}
<pre style="font-size: 1.5em;">@paper{{{/literal}{$schedConf->getSetting('acronym')|escape}{literal}}{{/literal}{$paperId|escape}{literal}},
	author = {{/literal}{assign var=presenters value=$paper->getPresenters()}{foreach from=$presenters item=presenter name=presenters key=i}{$presenter->getLastName()|escape}, {assign var=firstName value=$presenter->getFirstName()}{assign var=presenterCount value=$presenters|@count}{$firstName[0]|escape}.{if $i<$presenterCount-1}, {/if}{/foreach}{literal}},
	title = {{/literal}{$paper->getPaperTitle()|strip_unsafe_html}{literal}},
	conference = {{/literal}{$conference->getTitle()|escape}{literal}},
	year = {{/literal}{$paper->getDatePublished()|date_format:'%Y'}{literal}},
{/literal}{assign var=issn value=$conference->getSetting('issn')|escape}{if $issn}{literal}	issn = {{/literal}{$issn}{literal}},{/literal}{/if}

{literal}	url = {{/literal}{$paperUrl}{literal}}
}
</pre>
{/literal}

