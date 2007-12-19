{**
 * citation.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Paper reading tools -- Capture Citation BibTeX format
 *
 * $Id$
 *}
<div class="separator"></div>

{literal}
<pre style="font-size: 1.5em;">@paper{{/literal}{$schedConf->getLocalizedSetting('acronym')|escape}{$paperId|escape}{literal},
	author = {{/literal}{assign var=presenters value=$paper->getPresenters()}{foreach from=$presenters item=presenter name=presenters key=i}{assign var=firstName value=$presenter->getFirstName()}{assign var=presenterCount value=$presenters|@count}{$firstName|escape} {$presenter->getLastName()|escape}{if $i<$presenterCount-1} and {/if}{/foreach}{literal}},
	title = {{/literal}{$paper->getPaperTitle()|strip_unsafe_html}{literal}},
	conference = {{/literal}{$conference->getConferenceTitle()|escape}{literal}},
	year = {{/literal}{$paper->getDatePublished()|date_format:'%Y'}{literal}},
	keywords = {{/literal}{$paper->getPaperSubject()|escape}{literal}},
	abstract = {{/literal}{$paper->getPaperAbstract()|escape}{literal}},
{/literal}{assign var=onlineIssn value=$conference->getSetting('onlineIssn')|escape}
{assign var=issn value=$conference->getSetting('issn')|escape}{if $issn}{literal}	issn = {{/literal}{$issn}{literal}},{/literal}
{elseif $onlineIssn}{literal}  issn = {{/literal}{$onlineIssn}{literal}},{/literal}{/if}

{literal}	url = {{/literal}{$paperUrl}{literal}}
}
</pre>
{/literal}

