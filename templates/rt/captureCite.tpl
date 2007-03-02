{**
 * captureCite.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Paper reading tools -- Capture Citation
 *
 * $Id$
 *}

{assign var=pageTitle value="rt.captureCite"}

{include file="rt/header.tpl"}

{if $galleyId}
	{url|assign:"paperUrl" page="paper" op="view" path=$paperId|to_array:$galleyId}
{else}
	{url|assign:"paperUrl" page="paper" op="view" path=$paperId}
{/if}

<h3>{$paper->getPaperTitle()|strip_unsafe_html}</h3>

{if $bibFormat == 'MLA'}
	{assign var=presenters value=$paper->getPresenters()}
	{assign var=presenterCount value=$presenters|@count}
	{foreach from=$presenters item=presenter name=presenters key=i}
		{assign var=firstName value=$presenter->getFirstName()}
		{$presenter->getLastName()|escape}, {$firstName|escape}{if $i==$presenterCount-2}, {translate key="rt.context.and"} {elseif $i<$presenterCount-1}, {else}.{/if}
	{/foreach}

	"{$paper->getPaperTitle()|strip_unsafe_html}" <i>{$conference->getTitle()|escape}</i> [{translate key="rt.captureCite.online"}],  {$paper->getDatePublished()|date_format:'%e %b %Y'}

{elseif $bibFormat == 'Turabian'}
	{assign var=presenters value=$paper->getPresenters()}
	{assign var=presenterCount value=$presenters|@count}
	{foreach from=$presenters item=presenter name=presenters key=i}
		{assign var=firstName value=$presenter->getFirstName()}
		{$presenter->getLastName()|escape}, {$firstName|escape}{if $i==$presenterCount-2}, {translate key="rt.context.and"} {elseif $i<$presenterCount-1}, {else}.{/if}
	{/foreach}

	"{$paper->getPaperTitle()|strip_unsafe_html}" <i>{$schedConf->getTitle()|escape}</i> [{translate key="rt.captureCite.online"}],  ({$paper->getDatePublished()|date_format:'%e %B %Y'|trim})

{elseif $bibFormat == 'CBE'}
	{assign var=presenters value=$paper->getPresenters()}
	{assign var=presenterCount value=$presenters|@count}
	{foreach from=$presenters item=presenter name=presenters key=i}
		{assign var=firstName value=$presenter->getFirstName()}
		{$presenter->getLastName()|escape}, {$firstName[0]|escape}.{if $i==$presenterCount-2}, &amp; {elseif $i<$presenterCount-1}, {/if}
	{/foreach}

	{$paper->getDatePublished()|date_format:'%Y %b %e'}. {$paper->getPaperTitle()|strip_unsafe_html}. {$conference->getTitle()|escape}. [{translate key="rt.captureCite.online"}] 

{elseif $bibFormat == 'BibTeX'}

{literal}
<pre style="font-size: 1.5em;">@paper{{{/literal}{$schedConf->getSetting('acronym')|escape}{literal}}{{/literal}{$paperId|escape}{literal}},
	presenter = {{/literal}{assign var=presenters value=$paper->getPresenters()}{foreach from=$presenters item=presenter name=presenters key=i}{$presenter->getLastName()|escape}, {assign var=firstName value=$presenter->getFirstName()}{assign var=presenterCount value=$presenters|@count}{$firstName[0]|escape}.{if $i<$presenterCount-1}, {/if}{/foreach}{literal}},
	title = {{/literal}{$paper->getPaperTitle()|strip_unsafe_html}{literal}},
	conference = {{/literal}{$conference->getTitle()|escape}{literal}},
	year = {{/literal}{$paper->getDatePublished()|date_format:'%Y'}{literal}},
{/literal}{assign var=issn value=$conference->getSetting('issn')|escape}{if $issn}{literal}	issn = {{/literal}{$issn}{literal}},{/literal}{/if}{literal}
	url = {{/literal}{$paperUrl}{literal}}
}
</pre>
{/literal}

{elseif $bibFormat == 'ABNT'}

	{assign var=presenters value=$paper->getPresenters()}
	{assign var=presenterCount value=$presenters|@count}
	{foreach from=$presenters item=presenter name=presenters key=i}
		{assign var=firstName value=$presenter->getFirstName()}
		{$presenter->getLastName()|escape}, {$firstName[0]|escape}.{if $i<$presenterCount-1}; {/if}{/foreach}.
	{$paper->getPaperTitle()|strip_unsafe_html}.
	<b>{$conference->getTitle()|escape}</b>, {translate key="rt.captureCite.acaoLocation"}
	{$paper->getDatePublished()|date_format:'%e %m %Y'}.

{else}
	{assign var=presenters value=$paper->getPresenters()}
	{assign var=presenterCount value=$presenters|@count}
	{foreach from=$presenters item=presenter name=presenters key=i}
		{assign var=firstName value=$presenter->getFirstName()}
		{$presenter->getLastName()|escape}, {$firstName[0]|escape}.{if $i==$presenterCount-2}, &amp; {elseif $i<$presenterCount-1}, {/if}
	{/foreach}

	{$paper->getDatePublished()|date_format:'%Y %b %e'}.
	{$paper->getPaperTitle()|strip_unsafe_html}.
	<i>{$conference->getTitle()|escape}</i> [{translate key="rt.captureCite.online"}].
	{translate key="rt.captureCite.available"} <a target="_new" href="{$paperUrl}">{$paperUrl|escape}</a>
{/if}

<br />
<br />

<div class="separator"></div>

<h3>{translate key="rt.captureCite.capture"}</h3>
<ul>
	{url|assign:"url" op="captureCite" path=$paperId|to_array:$galleyId:"endNote"}
	<li>{translate key="rt.captureCite.capture.endNote" url=$url}</li>

	{url|assign:"url" op="captureCite" path=$paperId|to_array:$galleyId:"referenceManager"}
	<li>{translate key="rt.captureCite.capture.referenceManager" url=$url}</li>

	{url|assign:"url" op="captureCite" path=$paperId|to_array:$galleyId:"proCite"}
	<li>{translate key="rt.captureCite.capture.proCite" url=$url}</li>
</ul>

{include file="rt/footer.tpl"}
