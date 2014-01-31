{**
 * citation.tpl
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * ProCite citation format generator
 *
 * $Id$
 *}
{if $galleyId}
{url|assign:"paperUrl" page="paper" op="view" path=$paperId|to_array:$galleyId}
{else}
{url|assign:"paperUrl" page="paper" op="view" path=$paperId}
{/if}
TY  - JOUR
{foreach from=$paper->getAuthors() item=author}
AU  - {$author->getFullName(true)|escape}
{/foreach}
PY  - {$paper->getDatePublished()|date_format:"%Y"}
TI  - {$paper->getLocalizedTitle()|strip_tags}
JF  - {$conference->getLocalizedTitle()}; {$schedConf->getLocalizedTitle()}
Y2  - {$paper->getDatePublished()|date_format:"%Y"}
KW  - {$paper->getLocalizedSubject()|escape}
N2  - {$paper->getLocalizedAbstract()|strip_tags|replace:"\n":" "|replace:"\r":" "}
UR  - {$paperUrl}
