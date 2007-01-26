{**
 * information.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Conference information page.
 *
 * $Id$
 *}

{include file="common/header.tpl"}

{if !empty($conferenceContent)}
	<h2>{$conferenceTitle}</h2>
	<p>{$conferenceContent|nl2br}</p>
{/if}

{if !empty($eventContent)}
	<h2>{$eventTitle}</h2>
	<p>{$eventContent|nl2br}</p>
{/if}

{include file="common/footer.tpl"}
