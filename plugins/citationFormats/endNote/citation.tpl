{**
 * citation.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * EndNote citation format generator
 *
 * $Id$
 *}
{if $galleyId}
{url|assign:"paperUrl" page="paper" op="view" path=$paperId|to_array:$galleyId}
{else}
{url|assign:"paperUrl" page="paper" op="view" path=$paperId}
{/if}
{foreach from=$paper->getAuthors() item=author}
%A {$author->getFullName(true)|escape}
{/foreach}
%D {$paper->getDatePublished()|date_format:"%Y"}
%T {$paper->getLocalizedTitle()|strip_tags}
%B {$paper->getDatePublished()|date_format:"%Y"}
%9 {$paper->getLocalizedSubject()|escape}
%! {$paper->getLocalizedTitle()|strip_tags}
%K {$paper->getLocalizedSubject()|escape}
%X {$paper->getLocalizedAbstract()|strip_tags|replace:"\n":" "|replace:"\r":" "}
%U {$paperUrl}
