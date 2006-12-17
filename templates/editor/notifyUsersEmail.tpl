{$body}

{$conference->getTitle()}
{$issue->getIssueIdentification()}
{translate key="issue.toc"}
{url page="issue" op="view" path=$issue->getBestIssueId()}

{foreach name=tracks from=$publishedPapers item=track key=trackId}
{if $track.title}{$track.title}{/if}

--------
{foreach from=$track.papers item=paper}
{$paper->getPaperTitle()|strip_tags}{if $paper->getPages()} ({$paper->getPages()}){/if}

{foreach from=$paper->getAuthors() item=author name=authorList}
	{$author->getFullName()}{if !$smarty.foreach.authorList.last},{/if}{/foreach}


{/foreach}

{/foreach}
{literal}{$templateSignature}{/literal}
