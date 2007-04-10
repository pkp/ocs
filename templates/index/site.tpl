{**
 * index.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * This template is for the site index, which is displayed when neither
 * a conference nor an scheduled conference are specified.
 *
 * $Id$
 *}

{include file="common/header.tpl"}

<br />

{if $intro}
<p>{$intro|nl2br}</p>
{/if}

{iterate from=conferences item=conference}

<h3>{$conference->getTitle()|escape}</h3>

{if $conference->getSetting('conferenceDescription')}
<p>{$conference->getSetting('conferenceDescription')|nl2br}</p>
{/if}

<p><a href="{url conference=$conference->getPath() schedConf=""}" class="action">{translate key="site.conferenceView"}</a></p>
{/iterate}

{include file="common/footer.tpl"}
