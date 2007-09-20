{**
 * citation.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
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
{foreach from=$paper->getPresenters() item=presenter}
AU  - {$presenter->getFullName(true)|escape}
{/foreach}
PY  - {$paper->getDatePublished()|date_format:"%Y"}
TI  - {$paper->getPaperTitle()|strip_tags}
JF  - {$conference->getTitle()}; {$schedConf->getSchedConfIdentification()}
Y2  - {$paper->getDatePublished()|date_format:"%Y"}
KW  - {$paper->getPaperSubject()|escape}
N2  - {$paper->getPaperAbstract()|strip_tags|replace:"\n":" "|replace:"\r":" "}
UR  - {$paperUrl}

