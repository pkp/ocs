{$body}

{$conference->getConferenceTitle()}
{$schedConf->getSchedConfTitle()}
{translate key="schedConf.presentations"}
{url page="schedConf" op="view" path=$schedConf->getId()}

{foreach name=tracks from=$publishedPapers item=track key=trackId}
{if $track.title}{$track.title}{/if}

--------
{foreach from=$track.papers item=paper}
{$paper->getLocalizedTitle()|strip_tags}{if $paper->getPages()} ({$paper->getPages()}){/if}

{foreach from=$paper->getAuthors() item=author name=authorList}{$author->getFullName()}{if !$smarty.foreach.authorList.last}, {/if}{/foreach}

{/foreach}

{/foreach}
{literal}{$templateSignature}{/literal}
