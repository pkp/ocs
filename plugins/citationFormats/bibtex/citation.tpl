{**
 * plugins/citationFormats/bibtex/citation.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Paper reading tools -- Capture Citation BibTeX format
 *
 *}
<div class="separator"></div>
<div id="citation">
{literal}
<pre style="font-size: 1.5em; white-space: pre-wrap; white-space: -moz-pre-wrap !important; white-space: -pre-wrap; white-space: -o-pre-wrap; word-wrap: break-word;">@paper{{/literal}{$schedConf->getLocalizedAcronym()|bibtex_escape}{$paperId|bibtex_escape}{literal},
	author = {{/literal}{assign var=authors value=$paper->getAuthors()}{foreach from=$authors item=author name=authors key=i}{assign var=firstName value=$author->getFirstName()}{assign var=authorCount value=$authors|@count}{$firstName|bibtex_escape} {$author->getLastName()|bibtex_escape}{if $i<$authorCount-1} {translate key="common.and"} {/if}{/foreach}{literal}},
	title = {{/literal}{$paper->getLocalizedTitle()|strip_unsafe_html}{literal}},
	conference = {{/literal}{$conference->getLocalizedName()|bibtex_escape}{literal}},
	year = {{/literal}{$paper->getDatePublished()|date_format:'%Y'}{literal}},
	keywords = {{/literal}{$paper->getLocalizedSubject()|bibtex_escape}{literal}},
	abstract = {{/literal}{$paper->getLocalizedAbstract()|strip_tags:false}{literal}},
{/literal}{assign var=onlineIssn value=$conference->getSetting('onlineIssn')|bibtex_escape}
{assign var=issn value=$conference->getSetting('issn')|bibtex_escape}{if $issn}{literal}	issn = {{/literal}{$issn|bibtex_escape}{literal}},{/literal}
{elseif $onlineIssn}{literal}  issn = {{/literal}{$onlineIssn|bibtex_escape}{literal}},{/literal}{/if}

{literal}	url = {{/literal}{url|bibtex_escape page="paper" op="view" path=$paper->getBestPaperId()}{literal}}
}
</pre>
{/literal}
</div>
