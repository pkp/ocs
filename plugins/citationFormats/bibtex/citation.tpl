{**
 * citation.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Paper reading tools -- Capture Citation BibTeX format
 *
 * $Id$
 *}
<div class="separator"></div>
<div id="citation">
{literal}
<pre style="font-size: 1.5em;">@paper{{/literal}{$schedConf->getLocalizedSetting('acronym')|escape}{$paperId|escape}{literal},
	author = {{/literal}{assign var=authors value=$paper->getAuthors()}{foreach from=$authors item=author name=authors key=i}{assign var=firstName value=$author->getFirstName()}{assign var=authorCount value=$authors|@count}{$firstName|escape} {$author->getLastName()|escape}{if $i<$authorCount-1} and {/if}{/foreach}{literal}},
	title = {{/literal}{$paper->getLocalizedTitle()|strip_unsafe_html}{literal}},
	conference = {{/literal}{$conference->getConferenceTitle()|escape}{literal}},
	year = {{/literal}{$paper->getDatePublished()|date_format:'%Y'}{literal}},
	keywords = {{/literal}{$paper->getLocalizedSubject()|escape}{literal}},
	abstract = {{/literal}{$paper->getLocalizedAbstract()|strip_tags:false}{literal}},
{/literal}{assign var=onlineIssn value=$conference->getSetting('onlineIssn')|escape}
{assign var=issn value=$conference->getSetting('issn')|escape}{if $issn}{literal}	issn = {{/literal}{$issn|escape}{literal}},{/literal}
{elseif $onlineIssn}{literal}  issn = {{/literal}{$onlineIssn|escape}{literal}},{/literal}{/if}

{literal}	url = {{/literal}{$paperUrl}{literal}}
}
</pre>
{/literal}
</div>
